<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Encryption\EncryptionInstance;
use Chevereto\Encryption\Interfaces\EncryptionInterface;
use Chevereto\Legacy\Classes\DB;
use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\Classes\Storage;
use Chevereto\Legacy\Classes\TwoFactor;
use function Chevereto\Legacy\feedback;
use function Chevereto\Legacy\feedbackAlert;
use function Chevereto\Legacy\feedbackSeparator;
use function Chevereto\Legacy\feedbackStep;

/**
 * @var EncryptionInterface $fromEncryption
 * @var EncryptionInterface $toEncryption
 * @var string $doing
 */

feedbackAlert('👀 Secrets will be shown decrypted');
feedbackStep($doing, 'settings');
new EncryptionInstance($fromEncryption);
$settings = Settings::get();
new EncryptionInstance($toEncryption);
foreach (Settings::ENCRYPTED_NAMES as $key) {
    $value = $settings[$key] ?? '';
    feedback("- $key: $value");
    Settings::update([$key => $value]);
}
feedbackSeparator();
feedbackStep($doing, 'storages');
new EncryptionInstance($fromEncryption);
$storages = Storage::get();
new EncryptionInstance($toEncryption);
foreach ($storages as $storage) {
    feedback('> Storage #' . $storage['id']);
    $values = [];
    foreach (Storage::ENCRYPTED_NAMES as $key) {
        $value = $storage[$key] ?? '';
        feedback("  $key: $value");
        $values[$key] = $value;
    }
    Storage::update(
        id: $storage['id'],
        values: $values,
        checkCredentials: false
    );
}
feedbackSeparator();
feedbackStep($doing, 'two-factor secrets');
$twoFactors = DB::get('two_factors', [], 'AND', ['field' => 'id', 'order' => 'desc']);
foreach ($twoFactors as $twoFactor) {
    new EncryptionInstance($fromEncryption);
    $twoFactor = TwoFactor::get($twoFactor['two_factor_id']);
    feedback('> Two-factor #' . $twoFactor['id']);
    new EncryptionInstance($toEncryption);
    $secret = $twoFactor['secret'];
    $values = [
        'secret' => $secret,
    ];
    feedback("- secret: $secret");
    TwoFactor::update(
        id: $twoFactor['id'],
        values: $values,
    );
}
feedbackSeparator();
feedbackStep($doing, 'login provider secrets');
new EncryptionInstance($fromEncryption);
$loginProviders = Login::getProviders('all');
new EncryptionInstance($toEncryption);
foreach ($loginProviders as $name => $loginProvider) {
    feedback('> ' . $loginProvider['label']);
    $values = [];
    foreach (Login::ENCRYPTED_PROVIDER_NAMES as $key) {
        $value = $loginProvider[$key] ?? '';
        feedback("  $key: $value");
        $values[$key] = $value;
    }
    Login::updateProvider(
        provider: $name,
        values: $values,
    );
}
feedbackSeparator();
feedbackStep($doing, 'login connection tokens');
$connections = DB::get(table: 'login_connections', values: 'all', sort: ['field' => 'id', 'order' => 'desc']);
foreach ($connections as $connection) {
    new EncryptionInstance($fromEncryption);
    $connection = Login::getConnection($connection['login_connection_id']);
    feedback("> Login connection #" . $connection['id']);
    new EncryptionInstance($toEncryption);
    $token = $connection['token'];
    $values = [
        'token' => $token,
    ];
    Login::updateConnection(
        id: (int) $connection['id'],
        values: $values,
    );
    $tokenString = serialize($token);
    feedback("- token: $tokenString");
}
