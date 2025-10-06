<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Legacy\Classes\Settings;

$opts = getopt('C:v:k:') ?: [];
$missing = [];
foreach (['k', 'v'] as $opt) {
    if (!isset($opts[$opt])) {
        $missing[] = $opt;
    }
}
if ($missing !== []) {
    echo "[Error] Missing -" . implode(' -', $missing) . "\n";
    die(255);
}
if (!Settings::hasKey($opts['k'])) {
    echo "[ERROR] Setting key doesn't exists\n";
    die(255);
}
/** @var int|string|null $value */
$value = $opts['v'] ?? null;
$typeset = Settings::getTypeset($opts['k']);
if ($typeset === 'bool') {
    $value = (int) ($value == 1 || strtolower($value) === 'true');
}
Settings::update([$opts['k'] => $value]);
require 'setting-get.php';
die(0);
