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
use Chevereto\Legacy\Classes\Listing;
use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\Classes\RequestLog;
use Chevereto\Legacy\Classes\User;
use function Chevereto\Legacy\G\get_current_url;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\redirect;
use function Chevereto\Legacy\G\safe_html;
use function Chevereto\Legacy\G\starts_with;
use function Chevereto\Legacy\G\url_to_relative;
use function Chevereto\Legacy\get_recaptcha_component;
use function Chevereto\Legacy\get_share_links;
use function Chevereto\Legacy\getIdFromURLComponent;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Legacy\getSettings;
use function Chevereto\Legacy\is_max_invalid_request;
use function Chevereto\Legacy\isShowEmbedContent;
use function Chevereto\Legacy\must_use_recaptcha;
use function Chevereto\Legacy\recaptcha_check;
use function Chevereto\Legacy\redirectIfRouting;
use function Chevereto\Vars\get;
use function Chevereto\Vars\post;
use function Chevereto\Vars\request;
use function Chevereto\Vars\server;
use function Chevereto\Vars\session;
use function Chevereto\Vars\sessionVar;

return function (Handler $handler) {
    redirectIfRouting('album', $handler->requestArray()[0]);
    $albumIndex = getSetting('root_route') === 'album'
        ? 0
        : 1;
    $request_handle = $albumIndex === 0
        ? $handler->requestArray()
        : $handler->request();
    if (($request_handle[0] ?? null) === null) {
        $handler->issueError(404);

        return;
    }
    $id = getIdFromURLComponent($request_handle[0]);
    if ($id == 0) {
        $handler->issueError(404);

        return;
    }
    if ($handler->isRequestLevel(4)) {
        $handler->issueError(404);

        return;
    }
    if (isset($request_handle[1]) && !in_array($request_handle[1], ['embeds', 'sub', 'info'])) {
        $handler->issueError(404);

        return;
    }
    $logged_user = Login::getUser();
    User::statusRedirect($logged_user['status'] ?? null);
    if (!isset(session()['album_view_stock'])) {
        sessionVar()->put('album_view_stock', []);
    }
    $album = Album::getSingle(
        id: $id,
        sumview: !in_array($id, session()['album_view_stock']),
        requester: $logged_user
    );
    if ($album === []) {
        $handler->issueError(404);

        return;
    }
    if (!starts_with($album['url'], get_current_url())) {
        if (server()['QUERY_STRING'] ?? false) {
            $redirect = rtrim($album['url'], '/') . '/?' . server()['QUERY_STRING'];
        } else {
            $redirect = $album['url'];
        }
        redirect($redirect);
    }
    $handler::setVar(
        'canonical',
        isset(get()['page']) ? null : $album['url']
    );
    $banned = isset($album['user']['status']) && $album['user']['status'] === 'banned';
    if (!$handler::cond('content_manager') && ($album == false || $banned)) {
        $handler->issueError(404);

        return;
    }
    $is_owner = $album['user']['id'] && $album['user']['id'] == ($logged_user['id'] ?? 0);
    if (getSetting('website_privacy_mode') == 'private') {
        if ($handler::cond('forced_private_mode')) {
            $album['privacy'] = getSetting('website_content_privacy_mode');
        }
        if (!Login::getUser() && $album['privacy'] != 'private_but_link') {
            redirect('login');
        }
    }
    if (!$handler::cond('content_manager') && !$is_owner && $album['privacy'] == 'password' && isset($album['password'])) {
        $is_error = false;
        $error_message = null;
        $failed_access_requests = RequestLog::getCounts('content-password', 'fail');
        if (is_max_invalid_request($failed_access_requests['day'])) {
            $handler->issueError(403);

            return;
        }
        $captcha_needed = $handler::cond('captcha_needed');
        if ($captcha_needed && (post()['content-password'] ?? false)) {
            $captcha = recaptcha_check();
            if (!$captcha->is_valid) {
                $is_error = true;
                $error_message = _s('%s says you are a robot', 'reCAPTCHA');
            }
        }
        if (!$is_error) {
            if (isset(post()['content-password']) && Album::checkPassword($album['password'], post()['content-password'])) {
                Album::storeUserPasswordHash($album['id'], post()['content-password']);
            } elseif (!Album::checkSessionPassword($album)) {
                $is_error = true;
                if (isset(post()['content-password'])) {
                    RequestLog::insert([
                        'type' => 'content-password',
                        'user_id' => ($logged_user['id'] ?? null),
                        'content_id' => $album['id'], 'result' => 'fail'
                    ]);
                    $error_message = _s('Invalid password');
                }
            }
        }
        $handler::setCond('error', $is_error);
        $handler::setVar('error', $error_message);
        if ($is_error) {
            if (getSettings()['recaptcha'] && must_use_recaptcha($failed_access_requests['day'] + 1)) {
                $captcha_needed = true;
            }
            if ($captcha_needed) {
                $handler::setCond('captcha_show', true);
                $handler::setVar(...get_recaptcha_component());
            }
            $handler::setCond('captcha_needed', $captcha_needed);
            $handler->setTemplate('password-gate');
            $handler::setVar('pre_doctitle', _s('Password required'));

            return;
        } else {
            $redirect_password = session()['redirect_password_to'] ?? null;
            if (isset($redirect_password)) {
                sessionVar()->remove('redirect_password_to');
                redirect($redirect_password);
            }
        }
    }
    if ($album['user']['is_private'] == 1
        && !$handler::cond('content_manager')
        && $album["user"]["id"] != ($logged_user['id'] ?? null)
    ) {
        unset($album['user']);
        $album['user'] = User::getPrivate();
    }
    if (!$handler::cond('content_manager') && in_array($album['privacy'], ['private', 'custom']) && !$is_owner) {
        $handler->issueError(404);

        return;
    }
    $safe_html_album = safe_html($album);
    $safe_html_album['description'] = preg_replace('/[ \t]+/', ' ', preg_replace('/\s*$^\s*/m', "\n", $safe_html_album['description'] ?? ''));
    $getParams = Listing::getParams(request());
    $handler::setVar('list_params', $getParams);
    $type = 'images';
    $where = 'WHERE image_album_id=:image_album_id';
    $output_tpl = 'album/image';
    if (isset($request_handle[1]) && $request_handle[1] == 'sub') {
        $type = 'albums';
        $where = 'WHERE album_parent_id=:image_album_id';
        $output_tpl = 'user/album';
    }
    $listing = new Listing();
    $listing->setType($type); // images | users | albums
    if (isset($getParams['reverse'])) {
        $listing->setReverse($getParams['reverse']);
    }
    if (isset($getParams['seek'])) {
        $listing->setSeek($getParams['seek']);
    }
    $listing->setOffset($getParams['offset']);
    $listing->setLimit($getParams['limit']); // how many results?
    $listing->setSortType($getParams['sort'][0]); // date | size | views
    $listing->setSortOrder($getParams['sort'][1]); // asc | desc
    $listing->setOwner((int) $album["user"]["id"]);
    $listing->setRequester(Login::getUser());
    $listing->setWhere($where);
    $listing->setPrivacy($album["privacy"]);
    $listing->bind(":image_album_id", $album["id"]);
    $listing->setOutputTpl($output_tpl);
    if ($is_owner || $handler::cond('content_manager')) {
        $listing->setTools(true);
    }
    $listing->exec();
    $handler::setVar('listing', $listing);
    $baseUrl = url_to_relative($album['url']);
    $tabs = Listing::getTabs([
        'listing' => 'images',
        'basename' => $baseUrl,
        'params_hidden' => ['list' => 'images', 'from' => 'album', 'albumid' => $album['id_encoded']],
        'tools_available' => $album['user']['id'] ? [] : ['album' => false]
    ]);
    if (isShowEmbedContent()) {
        $tabs[] = [
            'icon' => 'fas fa-code',
            'list' => false,
            'tools' => false,
            'label' => _s('Embed codes'),
            'url' => $baseUrl . '/embeds',
            'id' => 'tab-embeds',
        ];
    }

    $tabsSubAlbum = Listing::getTabs([
        'listing' => 'albums',
        'basename' => $baseUrl . '/sub',
        'params_hidden' => ['list' => 'albums', 'from' => 'album', 'albumid' => $album['id_encoded']],
        'tools_available' => $album['user']['id'] ? [] : ['album' => false]
    ], $getParams);
    foreach ($tabsSubAlbum as $array) {
        if ($array['label'] == 'AZ') {
            $array['label'] = _s('Sub albums');
            $array['id'] = 'tab-sub';
            $array['url'] = $album['url'] . '/sub';
            $tabs[] = $array;

            break;
        }
    }
    if (Login::isAdmin()) {
        $tabs[] = [
            'icon' => 'fas fa-info-circle',
            'list' => false,
            'tools' => false,
            'label' => _s('Info'),
            'id' => 'tab-info',
            'url' => $album['url'] . '/info'
        ];
    }
    $handler::setVar('current_tab', 0);
    foreach ($tabs as $k => &$v) {
        if (isset($request_handle[1])) {
            $v['current'] = $v['id'] == ('tab-' . $request_handle[1]);
        }
        if (isset($v['current']) && $v['current'] === true) {
            $handler::setVar('current_tab', $v['id']);
        }
        if (!isset($v['params'])) {
            continue;
        }
        $class_tabs[$k]['disabled'] = $album['image_count'] == 0 ? !$v['current'] : false;
    }
    $handler::setCond('owner', $is_owner);
    $handler::setVars([
        'pre_doctitle' => strip_tags($album['name']),
        'album' => $album,
        'album_safe_html' => $safe_html_album,
        'tabs' => $tabs,
        'list' => $listing,
        'owner' => $album['user']
    ]);
    if (isset($album['description'])) {
        $meta_description = $album['description'];
    } else {
        $meta_description = _s('%a album hosted in %w', ['%a' => $album['name'], '%w' => getSetting('website_name')]);
    }
    $handler::setVar('meta_description', htmlspecialchars($meta_description));
    if ($handler::cond('content_manager') || $is_owner) {
        $handler::setVar('user_items_editor', [
            "user_albums" => User::getAlbums((int) $album["user"]["id"]),
            "type" => "images"
        ]);
    }
    $share_element = [
        "HTML" => '<a href="__url__" title="__title__">__title__ (' . $album['image_count'] . ' ' . _n('image', 'images', $album['user']['image_count_display']) . ')</a>'
    ];
    $share_links_array = get_share_links($share_element);
    $handler::setVar('share_links_array', $share_links_array);
    $handler::setVar('privacy', $album['privacy']);
    $addValue = session()['album_view_stock'];
    $addValue[] = $id;
    sessionVar()->put('album_view_stock', $addValue);
};
