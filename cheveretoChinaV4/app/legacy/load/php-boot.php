<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    if (PHP_SAPI !== 'cli') {
        http_response_code(503);
    }
    echo 'This server is currently running PHP ' . PHP_VERSION . ' and CheveretoChina needs at least PHP 8.0.0 to run.' . PHP_EOL;
    die(255);
}

require_once __DIR__ . '/loader.php';
