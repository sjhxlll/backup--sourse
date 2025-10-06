<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function Chevereto\Legacy\loaderHandler;

if (PHP_SAPI !== 'cli') {
    header('HTTP/1.0 403 Forbidden');
    die("403 Forbidden\n");
}
$opts = getopt('C:') ?: [];
if ($opts === []) {
    echo "Missing -C command\n";
    die(255);
} else {
    $access = $opts['C'];
    $options = [
        'cron',
        'update',
        'encrypt-secrets',
        'decrypt-secrets',
        'htaccess-checksum',
        'htaccess-enforce',
        'bulk-importer',
        'install',
        'langs',
        'password-reset',
        'setting-get',
        'setting-update',
    ];
    if (!in_array($access, $options)) {
        echo "Invalid command\n";
        die(255);
    }
}
define('ACCESS', $access);
require_once __DIR__ . '/../load/php-boot.php';
require_once loaderHandler(
    $_COOKIE,
    $_ENV,
    $_FILES,
    $_GET,
    $_POST,
    $_REQUEST,
    $_SERVER,
    $_SESSION ?? []
);
