<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Legacy\Classes\L10n;

function _s(string $msg, $args = null)
{
    $msg = L10n::gettext($msg);
    if ($msg && !is_null($args)) {
        $fn = is_array($args) ? 'strtr' : 'sprintf';
        $msg = $fn($msg, $args);
    }

    return $msg;
}

function _se(string $msg, $args = null)
{
    echo _s($msg, $args);
}

function _n(string $msg, string $msg_plural, string|int $count)
{
    return L10n::ngettext($msg, $msg_plural, (int) $count);
}

function _ne(string $msg, string $msg_plural, string|int $count)
{
    echo _n($msg, $msg_plural, (int) $count);
}
