<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function Chevere\Message\message;
use Chevere\Throwable\Exceptions\RuntimeException;
use function Chevere\VarDump\varDumpHtml;
use Chevere\VarDump\VarDumpInstance;
use function Chevere\Writer\streamFor;
use Chevere\Writer\StreamWriter;
use Chevere\Writer\Writers;
use Chevere\Writer\WritersInstance;

define('TIME_EXECUTION_START', microtime(true));
if (!defined('REPL')) {
    define('REPL', false);
}

require_once __DIR__ . '/../../vendor/autoload.php';

new WritersInstance(
    (new Writers())
        ->withOutput(
            new StreamWriter(
                streamFor('php://output', 'w')
            )
        )
        ->withError(
            new StreamWriter(
                streamFor('php://stderr', 'a')
            )
        )
);
if (PHP_SAPI !== 'cli') {
    new VarDumpInstance(varDumpHtml());
}
require_once __DIR__ . '/register-handlers.php';

$posix_getuid = function_exists('posix_getuid')
    ? posix_getuid()
    : 'unknown';
if ($posix_getuid === 0
    && !REPL) { // @phpstan-ignore-line
    $message = 'Unable to run as root. Please run as a regular user.';
    if (PHP_SAPI === 'cli') {
        echo "[ERROR] $message\n";
        die(255);
    }

    throw new RuntimeException(
        message($message)
    );
}
