<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function Chevereto\Encryption\assertEncryption;
use function Chevereto\Encryption\encryption;
use Chevereto\Encryption\NullEncryption;
use function Chevereto\Legacy\feedback;
use function Chevereto\Legacy\feedbackAlert;

try {
    assertEncryption();
} catch (Throwable $e) {
    feedbackAlert($e->getMessage());
    die(255);
}

$doing = 'Decrypting';
$fromEncryption = encryption();
$toEncryption = new NullEncryption();
feedbackAlert('🔐 Assuming database encrypted');
require __DIR__ . '/cipher.php';

feedback('🔓 Secrets decrypted');
