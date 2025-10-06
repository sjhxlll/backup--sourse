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
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Vars\env;

return function (Handler $handler) {
    if (!(bool) env()['CHEVERETO_ENABLE_UPDATE_HTTP']
        || Settings::get('chevereto_version_installed') === null
    ) {
        $handler->issueError(404);

        return;
    }
    if (!Login::isAdmin()) {
        $handler->issueError(403);

        return;
    }
    require_once PATH_APP_LEGACY_INSTALL . 'installer.php';
};
