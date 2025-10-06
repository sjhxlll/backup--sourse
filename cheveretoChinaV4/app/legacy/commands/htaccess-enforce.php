<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

echo "* Enforce .htaccess files\n";
$apacheDir = PATH_APP . 'apache/';
$checksumFile = $apacheDir . 'checksums.php';
$checksums = include $checksumFile;
$changed = false;
foreach ($checksums as $file => $md5) {
    $absoluteFile = PATH_PUBLIC . $file;
    $md5File = file_exists($absoluteFile)
        ? md5_file($absoluteFile)
        : null;
    if ($md5File != $md5) {
        if (file_exists($absoluteFile) && !is_writable($absoluteFile)) {
            echo "Unable to write $absoluteFile file\n";
            die(255);
        }
        file_put_contents($absoluteFile, file_get_contents($apacheDir . $md5));
        $changed = true;
        echo '  - Checksum enforced for ' . $file . "\n";
    }
}
$changedMessage = !$changed ? ' (everything OK)' : '';
echo "✅ [DONE] Enforce completed$changedMessage\n";
