<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function Chevereto\Legacy\getSetting;

if (getSetting('chevereto_version_installed') === null) {
    echo "[ERROR] Chevereto is not installed, try with the install command.\n";
    die(255);
}
require_once PATH_APP_LEGACY_INSTALL . 'installer.php';
