<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Vars\request;

return function (Handler $handler) {
    if (!$handler::checkAuthToken(request()['auth_token'] ?? '')) {
        $handler->issueError(403);

        return;
    }
    if (Login::isLoggedUser()) {
        Login::logout();
        $access_token = $handler::getAuthToken();
        $handler::setVar('auth_token', $access_token);
    }
};
