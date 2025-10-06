<?php

/*
 * This file is part of Chevereto.
 *
 * (c) Rodolfo Berrios <rodolfo@chevereto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chevereto\Legacy;

use Chevereto\Config\Config;
use Chevereto\Legacy\Classes\Album;
use Chevereto\Legacy\Classes\Login;
use Chevereto\Legacy\Classes\Settings;
use Chevereto\Legacy\Classes\User;
use function Chevereto\Legacy\G\absolute_to_url;
use function Chevereto\Legacy\G\add_trailing_slashes;
use function Chevereto\Legacy\G\array_filter_array;
use function Chevereto\Legacy\G\check_value;
use function Chevereto\Legacy\G\dsq_hmacsha1;
use function Chevereto\Legacy\G\fraction_to_decimal;
use function Chevereto\Legacy\G\get_base_url;
use function Chevereto\Legacy\G\get_current_url;
use function Chevereto\Legacy\G\get_route_name;
use function Chevereto\Legacy\G\get_route_path;
use function Chevereto\Legacy\G\get_set_status_header_desc;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\include_theme_file;
use function Chevereto\Legacy\G\json_output;
use function Chevereto\Legacy\G\json_prepare;
use function Chevereto\Legacy\G\safe_html;
use function Chevereto\Legacy\G\sanitize_path_slashes;
use function Chevereto\Legacy\G\set_status_header;
use function Chevereto\Legacy\G\str_replace_first;
use function Chevereto\Vars\cookie;
use function Chevereto\Vars\server;

function get_email_body_str($file)
{
    ob_start();
    include_theme_file($file);
    $mail_body = ob_get_contents();
    ob_end_clean();

    return $mail_body;
}

function get_theme_inline_code($file, $type = null)
{
    if (!isset($type)) {
        $type = pathinfo(rtrim($file, '.php'), PATHINFO_EXTENSION);
    }
    include_theme_file($file);
}
function show_theme_inline_code($file, $type = null)
{
    include_theme_file($file);
}

function get_theme_file_url($file, $options = [])
{
    $filepath = PATH_PUBLIC_LEGACY_THEME . $file;
    $filepath_override = PATH_PUBLIC_LEGACY_THEME . 'overrides/' . $file;
    if (file_exists($filepath_override)) {
        $filepath = $filepath_override;
    }

    return get_static_url($filepath, $options);
}
function get_static_url($filepath, $options = [])
{
    $options = array_merge(['versionize' => true], $options);
    $return = absolute_to_url($filepath, defined('CHV_ROOT_URL_STATIC') ? URL_APP_PUBLIC_STATIC : null);
    if ($options['versionize']) {
        $return = versionize_src($return);
    }

    return $return;
}
function theme_file_exists($var)
{
    return file_exists(PATH_PUBLIC_LEGACY_THEME . $var);
}

function get_html_tags()
{
    $palette = Handler::var('theme_palette_handle');
    $device = 'device-' . (Handler::cond('mobile_device') ? 'mobile' : 'nonmobile');
    $nsfwBlur = 'unsafe-blur-' . (getSetting('theme_nsfw_blur') ? 'on' : 'off');
    $classes = strtr(
        '%device %palette %nsfwBlur',
        [
            '%device' => $device,
            '%palette' => 'palette-' . $palette,
            '%nsfwBlur' => $nsfwBlur,
        ]
    );
    if (getSetting('recaptcha')) {
        $classes .= ' recaptcha recaptcha--' . getSetting('recaptcha_version');
    }

    return get_lang_html_tags() . ' class="' . $classes . '" data-palette="' . $palette . '"';
}

function get_lang_html_tags()
{
    $lang = get_language_used();

    return 'xml:lang="' . $lang['base'] . '" lang="' . $lang['base'] . '" dir="' . $lang['dir'] . '"';
}

function get_select_options_html($arr, $selected)
{
    $html = '';
    foreach ($arr as $k => $v) {
        $selected = is_bool($selected) ? ($selected ? 1 : 0) : $selected;
        $html .= '<option value="' . $k . '"' . ($selected == $k ? ' selected' : '') . '>' . $v . '</option>' . "\n";
    }

    return $html;
}
function get_checkbox_html($options = [])
{
    if (!array_key_exists('name', $options)) {
        return 'ERR:CHECKBOX_NAME_MISSING';
    }
    $options = array_merge([
        'value_checked' => 1,
        'value_unchecked' => 0,
        'label' => $options['name'],
        'checked' => false,
    ], $options);
    $tooltip = isset($options['tooltip']) ? (' rel="tooltip" title="' . $options['tooltip'] . '"') : null;

    return '<div class="checkbox-label">' . "\n" .
        '	<label for="' . $options['name'] . '"' . $tooltip . '>' . "\n" .
        '		<input type="hidden" name="' . $options['name'] . '" value="' . $options['value_unchecked'] . '">' . "\n" .
        '		<input type="checkbox" name="' . $options['name'] . '" id="' . $options['name'] . '" ' . ((bool) $options['checked'] ? ' checked' : null) . ' value="' . $options['value_checked'] . '">' . $options['label'] . "\n" .
        '	</label>' . "\n" .
        '</div>';
}
function get_recaptcha_component($id = 'g-recaptcha')
{
    if (getSetting('recaptcha_version') == 3) {
        return ['recaptcha_invisible_html', get_recaptcha_invisible_html()];
    }

    return ['recaptcha_html', get_recaptcha_html($id)];
}
function get_recaptcha_invisible_html()
{
    return '<script>
    const recaptchaAction = "' . get_route_name() . '";
    const recaptchaLocal = "' . get_base_url('recaptcha-verify') . '";
    grecaptcha.ready(function() {
        grecaptcha.execute("' . getSetting('recaptcha_public_key') . '", {action: recaptchaAction})
        .then(function(token) {
            fetch(recaptchaLocal + "/?action=" + recaptchaAction + "&token="+token).then(function(response) {
                response.json().then(function(data) {
                    console.log(data);
                });
            });
        });
    });
    </script>';
}
function get_recaptcha_html($id = 'g-recaptcha')
{
    return strtr('<div id="%id" data-recaptcha-element class="g-recaptcha"></div>', ['%id' => $id]);
}

function get_share_links(array $share_element = [])
{
    if (function_exists('get_share_links')) {
        return \get_share_links($share_element);
    }
    $share_element = array_merge([
        'HTML' => '<a href="__url__" title="__title__"><img src="__image__" /></a>',
        'referer' => get_base_url(),
        'url' => '__url__',
        'image' => '__image__',
        'title' => '__title__',
    ], $share_element);
    if (!isset($share_element['twitter'])) {
        $share_element['twitter'] = getSetting('twitter_account');
    }
    $elements = [];
    foreach ($share_element as $key => $value) {
        if (is_null($value)) {
            continue;
        }
        $elements[$key] = rawurlencode($value);
    }
    global $share_links_networks;
    include_theme_file('custom_hooks/share_links');
    if (!isset($share_links_networks)) {
        $share_links_networks = [
            'share' => [
                'url' => 'share:title=%TITLE%&url=%URL%',
                'label' => _s('Share'),
                'mobileonly' => true,
            ],
            'mail' => [
                'url' => 'mailto:?subject=%TITLE%&body=%URL%',
                'label' => 'Email',
            ],
            'facebook' => [
                'url' => 'http://www.facebook.com/share.php?u=%URL%',
                'label' => 'Facebook',
            ],
            'twitter' => [
                'url' => 'https://twitter.com/intent/tweet?original_referer=%URL%&url=%URL%&text=%TITLE%' . ($share_element['twitter'] ? '&via=%TWITTER%' : null),
                'label' => 'Twitter',
            ],
            'whatsapp' => [
                'url' => 'whatsapp://send?text=%TITLE% - ' . _s('view on %s', safe_html(getSetting('website_name'))) . ': %URL%',
                'label' => 'WhatsApp',
                'mobileonly' => true,
            ],
            'telegram' => [
                'url' => 'https://t.me/share/url?url=%URL%&text=%TITLE%',
                'label' => 'Telegram',
                'mobileonly' => true,
            ],
            'weixin' => [
                'url' => 'https://api.qrserver.com/v1/create-qr-code/?size=154x154&data=%URL%',
                'label' => '分享到微信',
            ],
            'weibo' => [
                'url' => 'https://service.weibo.com/share/share.php?url=%URL%&title=%TITLE%&pic=%PHOTO_URL%&searchPic=true',
                'label' => '分享到微博',
            ],
            'qzone' => [
                'url' => 'https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url=%URL%&pics=%PHOTO_URL%&title=%TITLE%',
                'label' => '分享到QQ空间',
                'icon' => 'star'
            ],
            'qq' => [
                'url' => 'https://connect.qq.com/widget/shareqq/index.html?url=%URL%&summary=%DESCRIPTION%&title=%TITLE%&pics=%PHOTO_URL%',
                'label' => '分享到QQ',
            ],
            'reddit' => [
                'url' => 'http://reddit.com/submit?url=%URL%',
                'label' => 'reddit',
            ],
            'vk' => [
                'url' => 'http://vk.com/share.php?url=%URL%',
                'label' => 'VK',
            ],
            'blogger' => [
                'url' => 'http://www.blogger.com/blog-this.g?n=%TITLE%&source=&b=%HTML%',
                'label' => 'Blogger',
            ],
            'tumblr' => [
                'url' => 'http://www.tumblr.com/share/photo?source=%PHOTO_URL%&caption=%TITLE%&clickthru=%URL%&title=%TITLE%',
                'label' => 'Tumblr.',
            ],
            'pinterest' => [
                'url' => 'http://www.pinterest.com/pin/create/bookmarklet/?media=%PHOTO_URL%&url=%URL%&is_video=false&description=%DESCRIPTION%&title=%TITLE%',
                'label' => 'Pinterest',
            ],
        ];
    }
    $return = [];
    $search = ['%URL%', '%TITLE%', '%DESCRIPTION%', '%HTML%', '%PHOTO_URL%', '%TWITTER%'];
    $replace = ['url', 'title', 'description', 'HTML', 'image', 'twitter'];
    foreach ($share_links_networks as $key => $value) {
        for ($i = 0; $i < count($replace); ++$i) {
            if (array_key_exists($replace[$i], $elements)) {
                $replace[$i] = $elements[$replace[$i]];
            }
        }
        $value['url'] = str_replace($search, $replace, $value['url']);
        $icon = "fab";
        switch ($key) {
            case 'share':
                $icon = 'fas';
                $key = 'share';

                break;
            case 'mail':
                $icon = 'fas';
                $key = 'at';

                break;
            case 'qzone':
                $icon = 'fas';

                break;
        }
        $iconKey = $value['icon'] ?? $key;
        $return[] = '<li' . (isset($value['mobileonly']) ? ' class="hidden phone-display-inline-block"' : null) . '><a data-href="' . $value['url'] . '" class="popup-link btn-32 btn-social btn-' . $key . '" rel="tooltip" data-tiptip="top" title="' . $value['label'] . '"><span class="btn-icon ' . $icon . ' fa-' . $iconKey . '"></span></a></li>';
    }

    return $return;
}

function include_peafowl_head()
{
    echo    '<meta name="generator" content="' . APP_NAME . ' 4">' . "\n" .
        '<link rel="stylesheet" href="' . get_static_url(PATH_PUBLIC_CONTENT_LEGACY_THEMES_PEAFOWL_LIB . 'peafowl.min.css') . '">' . "\n" .
        '<link rel="stylesheet" href="' . get_theme_file_url('style.min.css') . '">' . "\n\n" .
        '<link rel="stylesheet" href="' . get_static_url(PATH_PUBLIC_CONTENT_LEGACY_THEMES_PEAFOWL_LIB . 'font-awesome-6/css/all.min.css') . '">' . "\n" .
        '<script data-cfasync="false">document.documentElement.className+=" js";var devices=["phone","phablet","tablet","laptop","desktop","largescreen"],window_to_device=function(){for(var e=[480,768,992,1200,1880,2180],t=[],n="",d=document.documentElement.clientWidth||document.getElementsByTagName("body")[0].clientWidth||window.innerWidth,c=0;c<devices.length;++c)d>=e[c]&&t.push(devices[c]);for(0==t.length&&t.push(devices[0]),n=t[t.length-1],c=0;c<devices.length;++c)document.documentElement.className=document.documentElement.className.replace(devices[c],""),c==devices.length-1&&(document.documentElement.className+=" "+n),document.documentElement.className=document.documentElement.className.replace(/\s+/g," ");if("laptop"==n||"desktop"==n){var o=document.getElementById("pop-box-mask");null!==o&&o.parentNode.removeChild(o)}};window_to_device(),window.onresize=window_to_device;function jQueryLoaded(){!function(n,d){n.each(readyQ,function(d,e){n(e)}),n.each(bindReadyQ,function(e,i){n(d).bind("ready",i)})}(jQuery,document)}!function(n,d,e){function i(d,e){"ready"==d?n.bindReadyQ.push(e):n.readyQ.push(d)}n.readyQ=[],n.bindReadyQ=[];var u={ready:i,bind:i};n.$=n.jQuery=function(n){return n===d||void 0===n?u:void i(n)}}(window,document);
            </script>' . "\n\n";
    if (Handler::cond('captcha_show') && getSetting('recaptcha_version') == 3) {
        echo '<script src="https://www.recaptcha.net/recaptcha/api.js?render=' . getSetting('recaptcha_public_key') . '"></script>';
    }
}
function get_cookie_law_banner()
{
    return '<div id="cookie-law-banner" data-cookie="CHV_COOKIE_LAW_DISPLAY"><div class="c24 center-box position-relative"><p class="">' . _s('We use our own and third party cookies to improve your browsing experience and our services. If you continue using our website is understood that you accept this %cookie_policy_link.', ['%cookie_policy_link' => '<a href="' . get_base_url('page/privacy') . '">' . _s('cookie policy') . '</a>']) . '</p><a data-action="cookie-law-close" title="' . _s('I understand') . '" class="cookie-law-close"><span class="icon fas fa-times"></span></a></div></div>' . "\n\n";
}
// Sensitive Cookie law display
function display_cookie_law_banner()
{
    if (!getSetting('enable_cookie_law') || Login::isLoggedUser()) {
        return;
    }
    // No user logged in and cookie law has not been accepted
    if (!isset(cookie()['CHV_COOKIE_LAW_DISPLAY']) || (bool) cookie()['CHV_COOKIE_LAW_DISPLAY'] !== false) {
        echo get_cookie_law_banner();
    }
}
function include_peafowl_foot()
{
    display_cookie_law_banner();
    $resources = [
        'peafowl' => PATH_PUBLIC_CONTENT_LEGACY_THEMES_PEAFOWL_LIB . 'peafowl.min.js',
        'chevereto' => PATH_PUBLIC_CONTENT_LEGACY_THEMES_PEAFOWL_LIB . 'chevereto.min.js',
    ];
    foreach ($resources as $k => &$v) {
        $v = get_static_url($v);
    }
    $resources['scripts'] = get_static_url(PATH_PUBLIC_CONTENT_LEGACY_THEMES_PEAFOWL_LIB . 'js/scripts.min.js');
    $echo = [
        '<script defer data-cfasync="false" src="' . $resources['scripts'] . '" id="jquery-js" onload="jQueryLoaded(this, event)"></script>',
        '<script defer data-cfasync="false" src="' . $resources['peafowl'] . '" id="peafowl-js"></script>',
        '<script defer data-cfasync="false" src="' . $resources['chevereto'] . '" id="chevereto-js"></script>',
    ];
    if (Handler::cond('captcha_needed') && getSetting('recaptcha_version') != 3) {
        $echo[] = strtr('<script>
		var PFrecaptchaCallback = function() {
			$("[data-recaptcha-element]:empty:visible").each(function() {
				var $this = $(this);
				grecaptcha.render($this.attr("id"), {
					sitekey: "%k",
					theme: "%t"
				});
			});
		};
		</script>', [
            '%k' => getSetting('recaptcha_public_key'),
            '%t' => in_array(Handler::var('theme_palette_handle'), ['dark', 'imgur', 'deviantart']) ? 'dark' : 'light',
        ]);
        $echo[] = '<script defer src="https://www.recaptcha.net/recaptcha/api.js?onload=PFrecaptchaCallback&render=explicit"></script>';
    }
    if (method_exists(Settings::class, 'getChevereto')) {
        $echo[] = '<script data-cfasync="false">var CHEVERETO = ' . json_encode(Settings::getChevereto()) . '</script>';
    }
    echo implode("\n", $echo);
}
function get_peafowl_item_list($item, $template, $tools, $tpl = 'image', array $requester = [])
{
    $isRequesterAdmin = (bool) ($requester['is_admin'] ?? false);
    $isRequesterManager = (bool) ($requester['is_manager'] ?? false);
    $stock_tpl = 'IMAGE';
    if ($tpl == 'album' || $tpl == 'user/album') {
        $stock_tpl = 'ALBUM';
    }
    if ($tpl == 'user' || $tpl == 'user/user') {
        $stock_tpl = 'USER';
        if ($item['is_private'] == 1) {
            if ($isRequesterAdmin || $isRequesterManager) {
                $item['name'] = '🔒 ' . $item['name'];
            } else {
                unset($item);
                $item = User::getPrivate();
            }
        }
    } else {
        if (array_key_exists('user', $item)) {
            User::fill($item['user']);
        }
    }
    if (in_array($stock_tpl, ['IMAGE', 'ALBUM'])) {
        $item['liked'] = !isset($item['like']['user_id'])
            ? 0
            : (($requester['id'] ?? null) == $item['like']['user_id'] ? 1 : 0);
    }
    if ($stock_tpl == 'IMAGE') {
        if (!$item['is_animated'] || !isset($item['file_resource']['chain']['image'])) {
            $conditional_replaces['tpl_list_item/item_image_play_gif'] = null;
        }
    } elseif (!isset($item['images_slice'][0]['is_animated']) || $item['images_slice'][0]['is_animated'] == false) {
        $conditional_replaces['tpl_list_item/item_image_play_gif'] = null;
    }
    $fill_tpl = $tpl;
    if ($stock_tpl == 'ALBUM' && $isRequesterAdmin == false && $item['privacy'] == 'password' && !($item['user']['id'] && $item['user']['id'] == ($requester['id'] ?? null)) && Album::checkSessionPassword($item) == false) {
        $fill_tpl = 'album_password';
    }
    $filled_template = $template["tpl_list_item/$fill_tpl"];
    if (!getSetting('enable_likes') || (bool) ($requester['is_private'] ?? false)) {
        $conditional_replaces['tpl_list_item/item_like'] = null;
    }
    if (!getSetting('theme_show_social_share')) {
        $conditional_replaces['tpl_list_item/item_share'] = null;
    }
    if (isset($item['user'])
        && ($item['user']['is_private'] ?? false) == 1
        && !$isRequesterAdmin
        && $item['user']['id'] != ($requester['id'] ?? null)
        ) {
        unset($item['user']);
        $item['user'] = User::getPrivate();
        $conditional_replaces['tpl_list_item/image_description_user'] = null;
        $conditional_replaces['tpl_list_item/image_description_guest'] = null;
    } else {
        $conditional_replaces['tpl_list_item/image_description_private'] = null;
    }
    if (isset($item['user']) && ($item['user']['is_private'] ?? false) == 1 && ($isRequesterAdmin || ($requester['is_manager'] ?? false))) {
        $item['user']['name'] = '🔒 ' . $item['user']['name'];
    }
    $conditional_replaces[!isset($item['user'], $item['user']['id']) ? 'tpl_list_item/image_description_user' : 'tpl_list_item/image_description_guest'] = null;
    $conditional_replaces[!isset($item['user'], $item['user']['avatar']) ? 'tpl_list_item/image_description_user_avatar' : 'tpl_list_item/image_description_user_no_avatar'] = null;
    if ($stock_tpl == 'IMAGE') {
        $hasImageCover = isset($item['file_resource']['chain']['image']);
        $conditional_replaces['tpl_list_item/item_cover_type'] = $hasImageCover ? '--media' : '--bodyEmpty';
        $conditional_replaces['tpl_list_item/' . (!$hasImageCover ? 'image_cover_image' : 'image_cover_empty')] = null;
    }
    if ($stock_tpl == 'ALBUM') {
        if ($item['privacy'] !== 'password'
            || (!$isRequesterAdmin || ($item['user']['id'] ?? null) != ($requester['id'] ?? false))
        ) {
            $item['password'] = null;
        }
        if ($fill_tpl == 'album_password') {
            unset($item['images_slice']);
        }
        $hasImageCoverSlice = isset($item['images_slice'][0]['file_resource']);
        $conditional_replaces['tpl_list_item/item_cover_type'] = $hasImageCoverSlice
            ? '--media'
            : '--bodyEmpty';
        $conditional_replaces[
            'tpl_list_item/'
            . (
                (($item['image_count'] ?? 0) == 0 || !$hasImageCoverSlice)
                    ? 'album_cover_image'
                    : 'album_cover_empty'
            )
        ] = null;
        if (isset($item['images_slice'])) {
            for ($i = 1; $i < count((array) $item['images_slice']); ++$i) {
                if (!$item['images_slice'][$i]['file_resource']['chain']['thumb']) {
                    continue;
                }
                $template['tpl_list_item/album_thumbs'] = str_replace("%$i", '', $template['tpl_list_item/album_thumbs']);
            }
        }
        $template['tpl_list_item/album_thumbs'] = preg_replace('/%[0-9]+(.*)%[0-9]+/', '', $template['tpl_list_item/album_thumbs']);
    }
    if ($stock_tpl == 'USER') {
        $conditional_replaces[($item['avatar'] ?? false) ? 'tpl_list_item/user_no_avatar' : 'tpl_list_item/user_avatar'] = null;
        foreach (['twitter', 'facebook', 'website'] as $social) {
            if (!isset($item[$social])) {
                $conditional_replaces['tpl_list_item/user_' . $social] = null;
            }
        }
        $conditional_replaces[empty($item['avatar']['url']) ? 'tpl_list_item/user_cover_image' : 'tpl_list_item/user_cover_empty'] = null;
        $conditional_replaces[empty($item['background']['url']) ? 'tpl_list_item/user_background_image' : 'tpl_list_item/user_background_empty'] = null;
    }
    $show_item_edit_tools = false;
    $show_item_public_tools = false;
    $show_admin_tools = false;
    if ($requester !== []) {
        if (!is_null($tools)) {
            $show_item_edit_tools = !is_array($tools);
            $show_item_public_tools = is_array($tools);
        }
        if ($isRequesterAdmin || ($requester['is_manager'] ?? false)) {
            $show_item_edit_tools = true;
            $show_item_public_tools = false;
            $show_admin_tools = true;
        }
    }
    if (!$show_item_public_tools) {
        $template['tpl_list_item/item_' . strtolower($stock_tpl) . '_public_tools'] = null;
    }
    if (!$show_item_edit_tools) {
        $template['tpl_list_item/item_' . strtolower($stock_tpl) . '_edit_tools'] = null;
    }
    if (!$show_admin_tools) {
        $template['tpl_list_item/item_' . strtolower($stock_tpl) . '_admin_tools'] = null;
    }
    foreach ($conditional_replaces as $k => $v) {
        $template[$k] = $v;
    }
    preg_match_all('#%(tpl_list_item/.*)%#', $filled_template, $matches);
    if (is_array($matches[1])) {
        foreach ($matches[1] as $k => $v) {
            $filled_template = replace_tpl_string($v, $template[$v], $filled_template);
        }
    }
    foreach ($template as $k => $v) {
        $filled_template = replace_tpl_string($k, $v, $filled_template);
    }
    // Get rid of the useless keys
    unset($item['original_exifdata']);
    // Now stock the item values
    $replacements = array_change_key_case(flatten_array($item, $stock_tpl . '_'), CASE_UPPER);
    unset($replacements['IMAGE_ORIGINAL_EXIFDATA']);
    if ($stock_tpl == 'IMAGE' or $stock_tpl == 'ALBUM') {
        $replacements['ITEM_URL_EDIT'] = ($stock_tpl == 'IMAGE' ? $item['url_viewer'] : $item['url']) . '#edit';
    }
    // Public for the guest
    if (!array_key_exists('user', $item)) {
        $replacements['IMAGE_ALBUM_PRIVACY'] = 'public';
    }
    if (in_array($stock_tpl, ['IMAGE', 'ALBUM'])) {
        $nsfw = $stock_tpl == 'IMAGE' ? $item['nsfw'] : (isset($item['images_slice'][0]['nsfw']) ? $item['images_slice'][0]['nsfw'] : '');
        $placeholder = $stock_tpl == 'IMAGE' ? 'IMAGE_FLAG' : 'ALBUM_COVER_FLAG';
        $replacements[$placeholder] = $nsfw ? 'unsafe' : 'safe';
    }
    $object = array_filter_array($item, ['id_encoded', 'image', 'medium', 'thumb', 'name', 'title', 'display_url', 'extension', 'filename', 'height', 'how_long_ago', 'size_formatted', 'url', 'url_viewer', 'url_short', 'width', 'is_360']);
    if (isset($item['user'])) {
        $object['user'] = [];
        foreach (['avatar', 'url', 'username', 'name_short_html'] as $k) {
            $object['user'][$k] = $item['user'][$k] ?? '';
        }
    }
    $replacements['DATA_OBJECT'] = "data-object='" . rawurlencode(json_encode(G\array_utf8encode($object))) . "'";
    if ($stock_tpl == 'IMAGE') {
        $replacements['SIZE_TYPE'] = getSetting('theme_image_listing_sizing') . '-size';
    }
    foreach ($replacements as $k => $v) {
        $filled_template = replace_tpl_string($k, $v, $filled_template);
    }
    $column_sizes = [
        'image' => 8,
        'album' => 8,
        'user' => 8,
    ];
    foreach ($column_sizes as $k => $v) {
        $filled_template = replace_tpl_string('COLUMN_SIZE_' . strtoupper($k), (string) $v, $filled_template);
    }

    return $filled_template;
}
function replace_tpl_string(string $search, $replace, string $subject)
{
    return str_replace('%' . $search . '%', is_null($replace) ? '' : (string) $replace, $subject);
}
// http://stackoverflow.com/a/9546215
function flatten_array($array, $prefix = '')
{
    $result = [];
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $result = $result + flatten_array($value, $prefix . $key . '_');
        } else {
            $result[$prefix . $key] = $value;
        }
    }

    return $result;
}
function chevereto_die(array|string $error_msg, $paragraph = null, $title = null)
{
    if (!is_array($error_msg) && check_value($error_msg)) {
        $error_msg = [$error_msg];
    }
    if (is_null($paragraph)) {
        $paragraph = "The system has encountered errors <b>in your server setup</b> that must be fixed to use Chevereto:";
    }
    $solution = 'Need help? Check our <a href="https://chevereto.com" target="_blank">Support</a>.';
    $title = (!is_null($title)) ? $title : 'System error';
    $handled_request = PATH_PUBLIC == '/' // @phpstan-ignore-line
        ? sanitize_path_slashes(server()['REQUEST_URI'] ?? '')
        : str_ireplace(
            Config::host()->hostnamePath(),
            '',
            add_trailing_slashes(server()['REQUEST_URI'] ?? '')
        );
    $base_request = explode('/', rtrim(str_replace('//', '/', str_replace('?', '/', $handled_request)), '/'))[0];
    if ($base_request == 'json' || $base_request == 'api') {
        $output = [
            'status_code' => 500,
            'status_txt' => get_set_status_header_desc(500),
            'error' => $title,
            'errors' => $error_msg,
        ];
        set_status_header(500);
        json_prepare();
        json_output($output);
        die(255);
    }
    $html = [
        '<h1>' . $title . '</h1>',
        '<p>' . $paragraph . '</p>',
    ];
    if (is_array($error_msg)) {
        $html[] = '<ul class="errors">';
        foreach ($error_msg as $error) {
            $html[] = '<li>' . $error . '</li>';
        }
        $html[] = '</ul>';
    }
    $html[] = '<p class="margin-bottom-0">' . $solution . '</p>';
    $html = join('', $html);
    $template = PATH_PUBLIC_CONTENT_LEGACY_SYSTEM . 'template.php';
    require_once $template;
    die(255);
}
function getFriendlyExif($Exif)
{
    if (gettype($Exif) == 'string') {
        $Exif = json_decode($Exif);
    }
    if ($Exif->Make ?? false) {
        $exif_one_line = [];
        if ($Exif->ExposureTime ?? false) {
            $Exposure = $Exif->ExposureTime . 's';
            $exif_one_line[] = $Exposure;
        }
        if ($Exif->FNumber ?? false or $Exif->COMPUTED->ApertureFNumber ?? false) {
            $Aperture = 'ƒ/' .
                (
                    isset($Exif->FNumber)
                    ? fraction_to_decimal((string) $Exif->FNumber)
                    : explode('/', $Exif->COMPUTED->ApertureFNumber)[1]
                );
            $exif_one_line[] = $Aperture;
        }
        if ($Exif->ISOSpeedRatings ?? false) {
            $ISO = 'ISO' . (is_array($Exif->ISOSpeedRatings) ? $Exif->ISOSpeedRatings[0] : $Exif->ISOSpeedRatings);
            $exif_one_line[] = $ISO;
        }
        if ($Exif->FocalLength ?? false) {
            $FocalLength = fraction_to_decimal($Exif->FocalLength) . 'mm';
            $exif_one_line[] = $FocalLength;
        }
        $exif_relevant = [
            'XResolution',
            'YResolution',
            'ResolutionUnit',
            'ColorSpace',
            'Orientation',
            'Software',
            'BrightnessValue',
            'SensingMethod',
            'SceneCaptureType',
            'GainControl',
            'ExposureBiasValue',
            'MaxApertureValue',
            'ExposureProgram',
            'ExposureMode',
            'MeteringMode',
            'LightSource',
            'Flash',
            'WhiteBalance',
            'DigitalZoomRatio',
            'Contrast',
            'Saturation',
            'Sharpness',
            'ExifVersion',
            'DateTimeModified',
            'DateTimeOriginal',
            'DateTimeDigitized',
        ];
        $ExifRelevant = [];
        foreach ($exif_relevant as $k) {
            if (property_exists($Exif, $k)) {
                $exifReadableValue = exifReadableValue($Exif, $k);
                if ($exifReadableValue !== null && !is_array($exifReadableValue)) { // Just make sure to avoid this array
                    $ExifRelevant[$k] = $exifReadableValue;
                }
            }
        }
        $camera = $Exif->Make;
        if ($Exif->Model ?? false) {
            $camera .= ' ' . $Exif->Model;
        }
        $return = [
            'Simple' => [
                'Camera' => $camera,
                'Capture' => implode(' ', $exif_one_line),
            ],
            'Full' => array_merge([
                'Manufacturer' => $Exif->Make,
                'Model' => $Exif->Model ?? '',
                'ExposureTime' => $Exposure ?? '',
                'Aperture' => $Aperture ?? '',
                'ISO' => preg_replace('/iso/i', '', $ISO ?? ''),
                'FocalLength' => $FocalLength ?? '',
            ], $ExifRelevant),
        ];
        foreach ($return as $k => &$v) {
            if ($k == 'Full') {
                $v = array_filter((array) $v, 'strlen');
            }
            foreach ($v as $kk => $vv) {
                $return[$k][$kk] = safe_html(strip_tags((string) $vv));
            }
        }

        return json_decode(json_encode($return), false);
    }

    return null;
}
function exifReadableValue($Exif, $key)
{
    $table = [
        'PhotometricInterpretation' => [
            0 => 'WhiteIsZero',
            1 => 'BlackIsZero',
            2 => 'RGB',
            3 => 'RGB Palette',
            4 => 'Transparency Mask',
            5 => 'CMYK',
            6 => 'YCbCr',
            8 => 'CIELab',
            9 => 'ICCLab',
            10 => 'ITULab',
            32803 => 'Color Filter Array',
            32844 => 'Pixar LogL',
            32845 => 'Pixar LogLuv',
            34892 => 'Linear Raw',
        ],
        'ColorSpace' => [
            1 => 'sRGB',
            2 => 'Adobe RGB',
            65533 => 'Wide Gamut RGB',
            65534 => 'ICC Profile',
            65535 => 'Uncalibrated',
        ],
        'Orientation' => [
            1 => 'Horizontal (normal)',
            2 => 'Mirror horizontal',
            3 => 'Rotate 180',
            4 => 'Mirror vertical',
            5 => 'Mirror horizontal and rotate 270 CW',
            6 => 'Rotate 90 CW',
            7 => 'Mirror horizontal and rotate 90 CW',
            8 => 'Rotate 270 CW',
        ],
        'ResolutionUnit' => [
            1 => 'None',
            2 => 'inches',
            3 => 'cm',
        ],
        'ExposureProgram' => [
            0 => 'Not Defined',
            1 => 'Manual',
            2 => 'Program AE',
            3 => 'Aperture-priority AE',
            4 => 'Shutter speed priority AE',
            5 => 'Creative (Slow speed)',
            6 => 'Action (High speed)',
            7 => 'Portrait',
            8 => 'Landscape',
            9 => 'Bulb',
        ],
        'MeteringMode' => [
            0 => 'Unknown',
            1 => 'Average',
            2 => 'Center-weighted average',
            3 => 'Spot',
            4 => 'Multi-spot',
            5 => 'Multi-segment',
            6 => 'Partial',
            255 => 'Other',
        ],
        'ExposureMode' => [
            0 => 'Auto',
            1 => 'Manual',
            2 => 'Auto bracket',
        ],
        'SensingMethod' => [
            1 => 'Monochrome area',
            2 => 'One-chip color area',
            3 => 'Two-chip color area',
            4 => 'Three-chip color area',
            5 => 'Color sequential area',
            6 => 'Monochrome linear',
            7 => 'Trilinear',
            8 => 'Color sequential linear',
        ],
        'SceneCaptureType' => [
            0 => 'Standard',
            1 => 'Landscape',
            2 => 'Portrait',
            3 => 'Night',
        ],
        'GainControl' => [
            0 => 'None',
            1 => 'Low gain up',
            2 => 'High gain up',
            3 => 'Low gain down',
            4 => 'High gain down',
        ],
        'Saturation' => [
            0 => 'Normal',
            1 => 'Low',
            2 => 'High',
        ],
        'Sharpness' => [
            0 => 'Normal',
            1 => 'Soft',
            2 => 'Hard',
        ],
        'Flash' => [
            0 => 'No Flash',
            1 => 'Fired',
            5 => 'Fired, Return not detected',
            7 => 'Fired, Return detected',
            8 => 'On, Did not fire',
            9 => 'On, Fired',
            13 => 'On, Return not detected',
            15 => 'On, Return detected',
            16 => 'Off, Did not fire',
            20 => 'Off, Did not fire, Return not detected',
            24 => 'Auto, Did not fire',
            25 => 'Auto, Fired',
            29 => 'Auto, Fired, Return not detected',
            31 => 'Auto, Fired, Return detected',
            32 => 'No flash function',
            48 => 'Off, No flash function',
            65 => 'Fired, Red-eye reduction',
            69 => 'Fired, Red-eye reduction, Return not detected',
            71 => 'Fired, Red-eye reduction, Return detected',
            73 => 'On, Red-eye reduction',
            77 => 'On, Red-eye reduction, Return not detected',
            79 => 'On, Red-eye reduction, Return detected',
            80 => 'Off, Red-eye reduction',
            88 => 'Auto, Did not fire, Red-eye reduction',
            89 => 'Auto, Fired, Red-eye reduction',
            93 => 'Auto, Fired, Red-eye reduction, Return not detected',
            95 => 'Auto, Fired, Red-eye reduction, Return detected',
        ],
        'LightSource' => [
            0 => 'Unknown',
            1 => 'Daylight',
            2 => 'Fluorescent',
            3 => 'Tungsten (Incandescent)',
            4 => 'Flash',
            9 => 'Fine Weather',
            10 => 'Cloudy',
            11 => 'Shade',
            12 => 'Daylight Fluorescent',
            13 => 'Day White Fluorescent',
            14 => 'Cool White Fluorescent',
            15 => 'White Fluorescent',
            16 => 'Warm White Fluorescent',
            17 => 'Standard Light A',
            18 => 'Standard Light B',
            19 => 'Standard Light C',
            20 => 'D55',
            21 => 'D65',
            22 => 'D75',
            23 => 'D50',
            24 => 'ISO Studio Tungsten',
            255 => 'Other',
        ],
    ];
    $table['Contrast'] = $table['Saturation'];
    if (is_object($Exif) and is_array($Exif->$key)) {
        $value_arr = [];
        foreach ($Exif->$key as $k) {
            $value_arr[] = $table[$key][$k];
        }
        $value = implode(', ', $value_arr);
    } else {
        $value = $table[$key][$Exif->$key] ?? $Exif->$key;
    }
    switch ($key) {
        case 'DateTime':
        case 'DateTimeOriginal':
        case 'DateTimeDigitized':
        case 'DateTimeModified':
            $value = $value !== null ? preg_replace('/(\d{4})(:)(\d{2})(:)(\d{2})/', '$1-$3-$5', $value) : null;

            break;
        case 'WhiteBalance':
            $value = $value == 0 ? 'Auto' : $value;

            break;
        case 'BrightnessValue':
        case 'MaxApertureValue':
            $value = $value ? fraction_to_decimal($value) : null;

            break;
        case 'XResolution':
        case 'YResolution':
            $value = $value ? (floor(fraction_to_decimal($value)) . ' dpi') : null;

            break;
    }

    return $value ?? null;
}
function arr_printer($arr, $tpl = '', $wrap = [])
{
    ksort($arr);
    $rtn = '';
    $rtn .= $wrap[0];
    foreach ($arr as $k => $v) {
        if (is_array($v)) {
            $rtn .= strtr($tpl, ['%K' => $k, '%V' => arr_printer($v, $tpl, $wrap)]);
        } else {
            $rtn .= strtr($tpl, ['%K' => $k, '%V' => $v]);
        }
    }
    $rtn .= $wrap[1];

    return $rtn;
}
function versionize_src($src)
{
    return $src . '?' . md5(get_chevereto_version());
}
function show_banner($banner, $sfw = true)
{
    if (!$sfw) {
        $banner .= '_nsfw';
    }
    $banner_code = trim(get_banner_code($banner, false));
    if ($banner_code !== '') {
        echo '<div id="' . $banner . '" class="ad-banner">' . $banner_code . '</div>';
    }
}
function showComments()
{
    $html = '';
    switch (getSetting('comments_api')) {
        case 'js':
            $html = getSetting('comment_code');

            break;
        case 'disqus':
            $disqus_secret = getSetting('disqus_secret_key');
            $disqus_public = getSetting('disqus_public_key');
            if (!empty($disqus_secret) && !empty($disqus_public)) {
                $logged_user = Login::getUser();
                $data = [
                    'id' => $logged_user['id_encoded'],
                    'username' => $logged_user['name'],
                    'email' => $logged_user['email'],
                    'avatar' => $logged_user['avatar']['url'],
                    'url' => $logged_user['url'],
                ];

                $message = base64_encode(json_encode($data));
                $timestamp = time();
                $hmac = dsq_hmacsha1($message . ' ' . $timestamp, $disqus_secret);
                $auth = $message . ' ' . $hmac . ' ' . $timestamp;
            }
            $html = strtr('<div id="disqus_thread"></div>
<script>
var disqus_config = function() {
	this.page.url = "%page_url";
	this.page.identifier = "%page_id";
};
(function() {
	var d = document, s = d.createElement("script");
	s.src = "//%shortname.disqus.com/embed.js";
	s.setAttribute("data-timestamp", +new Date());
	(d.head || d.body).appendChild(s);
})();
var disqus_config = function () {
	this.language = "%language_code";
	this.page.remote_auth_s3 = "%auth";
	this.page.api_key = "%api_key";
};
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript">comments powered by Disqus.</a></noscript>', [
                '%page_url' => get_current_url(),
                '%page_id' => str_replace_first(get_route_path(), get_route_name(), get_route_path(true)), // image.ID
                '%shortname' => getSetting('disqus_shortname'),
                '%language_code' => get_language_used()['base'],
                '%auth' => isset($auth) ? $auth : null,
                '%api_key' => $disqus_public,
            ]);

            break;
    }
    echo $html;
}

function getThemeLogo(): string
{
    $logo = getSetting('logo_' . getSetting('logo_type'));
    if (getSetting('logo_type') !== 'text') {
        return '<img src="' . get_system_image_url($logo) . '" alt="' . getSetting('website_name') . '">';
    } else {
        return Handler::var('safe_html_website_name');
    }
}
