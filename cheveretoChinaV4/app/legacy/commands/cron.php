<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Config\Config;
use function Chevereto\Legacy\checkUpdates;
use Chevereto\Legacy\Classes\DB;
use Chevereto\Legacy\Classes\Image;
use Chevereto\Legacy\Classes\L10n;
use Chevereto\Legacy\Classes\Lock;
use Chevereto\Legacy\Classes\Queue;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\Classes\User;
use function Chevereto\Legacy\feedbackAlert;
use function Chevereto\Legacy\feedbackStep;
use function Chevereto\Legacy\G\datetime_add;
use function Chevereto\Legacy\G\datetime_sub;
use function Chevereto\Legacy\G\datetimegmt;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Legacy\isSafeToExecute;
use function Chevereto\Legacy\updateCheveretoNews;
use function Chevereto\Vars\env;

if (getSetting('maintenance')) {
    echo "[!] Chevereto is in maintenance mode.\n";
    die(255);
}

$jobs = ['storageDelete', 'deleteExpiredImages', 'cleanUnconfirmedUsers', 'removeDeleteLog'];
if ((bool) env()['CHEVERETO_ENABLE_CHECK_UPDATES']) {
    //$jobs[] = 'checkForUpdates';
}
if (Config::enabled()->htaccessCheck()) {
    $jobs[] = 'checkHtaccess';
}
shuffle($jobs);
foreach ($jobs as $job) {
    if (!isSafeToExecute()) {
        echo "[OK] Exit - (time is up)\n";
        writeLastRan();
    }
    feedbackStep('Job:', $job);
    $job();
}
writeLastRan();

function writeLastRan(): void
{
    $datetimegmt = datetimegmt();
    Settings::update(['cron_last_ran' => datetimegmt()]);
    echo "--\n✅ [DONE] Cron tasks ran @ $datetimegmt\n";
}

function echoLocked(string $job): void
{
    echo "* [!] Job $job is locked ~ skipping\n";
}

function storageDelete(): void
{
    $job = 'storage-delete';
    $lock = new Lock($job);
    if ($lock->create()) {
        Queue::process(['type' => $job]);
        $lock->destroy();

        return;
    }
    echoLocked($job);
}

function deleteExpiredImages(): void
{
    $job = 'delete-expired-images';
    $lock = new Lock($job);
    if ($lock->create()) {
        Image::deleteExpired(50);
        $lock->destroy();

        return;
    }
    echoLocked($job);
}

function cleanUnconfirmedUsers(): void
{
    $job = 'clean-unconfirmed-users';
    $lock = new Lock($job);
    if ($lock->create()) {
        User::cleanUnconfirmed(5);
        $lock->destroy();

        return;
    }
    echoLocked($job);
}

function removeDeleteLog(): void
{
    $job = 'remove-delete-log';
    $lock = new Lock($job);
    if ($lock->create()) {
        $db = DB::getInstance();
        $db->query('DELETE FROM ' . DB::getTable('deletions') . ' WHERE deleted_date_gmt <= :time;');
        $db->bind(':time', datetime_sub(datetimegmt(), 'P3M'));
        $db->exec();
        $lock->destroy();

        return;
    }
    echoLocked($job);
}

function checkForNews(): void
{
    if (!checkoutUpdate('news_check_datetimegmt')) {
        feedbackAlert('Skipping news check');

        return;
    }
    L10n::setLocale(Settings::get('default_language'));
    $job = 'check-news';
    $lock = new Lock($job);
    if ($lock->create()) {
        updateCheveretoNews();
        $lock->destroy();

        return;
    }
    echoLocked($job);
}

function checkForUpdates(): void
{
    if (!checkoutUpdate('update_check_datetimegmt')) {
        feedbackAlert('Skipping updates check');

        return;
    }
    L10n::setLocale(Settings::get('default_language'));
    $job = 'check-updates';
    $lock = new Lock($job);
    if ($lock->create()) {
        //checkUpdates();
        $lock->destroy();

        return;
    }
    echoLocked($job);
}

function checkoutUpdate(string $datetimeSetting): bool
{
    return is_null(Settings::get($datetimeSetting))
    || datetime_add(Settings::get($datetimeSetting), 'P1D') < datetimegmt();
}

function checkHtaccess()
{
    include __DIR__ . '/htaccess-enforce.php';
}
