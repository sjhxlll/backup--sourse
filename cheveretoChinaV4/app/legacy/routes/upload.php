<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Legacy\Classes\Album;
use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\Classes\User;
use function Chevereto\Legacy\decodeID;
use function Chevereto\Legacy\G\get_base_url;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\redirect;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Vars\get;

return function (Handler $handler) {
    if (!$handler::cond('upload_allowed')) {
        if (Login::isLoggedUser()) {
            $handler->issueError(403);

            return;
        } else {
            redirect('login');
        }
    }
    $logged_user = Login::getUser();
    User::statusRedirect($logged_user['status'] ?? null);
    $album = null;
    if (isset(get()['toAlbum'])) {
        $toAlbumId = decodeID(get()['toAlbum']);
        $album = Album::getSingle(id: $toAlbumId, requester: $logged_user);
        $is_owner = isset($album['user']['id']) && $album['user']['id'] == $logged_user['id'];
        if (!$is_owner) {
            $album = [];
        }
    }
    $handler::setVar('album', $album);
    $handler::setVar('pre_doctitle', _s('Upload'));
    if (getSetting('homepage_style') == 'route_upload') {
        if ($handler->requestArray()[0] === '/') {
            $handler::setVar('doctitle', Settings::get('website_doctitle'));
            $handler::setVar('pre_doctitle', Settings::get('website_name'));
        }
        $handler::setVar('canonical', get_base_url(''));
    }
};
