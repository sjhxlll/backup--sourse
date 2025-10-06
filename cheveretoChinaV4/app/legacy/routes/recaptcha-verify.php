<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 * 
 * Fixed reCaptcha in China
 */

use function Chevereto\Legacy\G\fetch_url;
use function Chevereto\Legacy\G\get_client_ip;
use Chevereto\Legacy\G\Handler;
use function Chevereto\Legacy\G\redirect;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Vars\get;
use function Chevereto\Vars\sessionVar;

return function (Handler $handler) {
    $key = getSetting('recaptcha_private_key') ?? '';
    if ($key === '') {
        redirect('');
    }

    try {
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . 'GMT');
        header('Cache-Control: no-cache, must-revalidate');
        header('Pragma: no-cache');
        header('Content-type: application/json; charset=UTF-8');
        $endpoint = 'https://recaptcha.google.cn/recaptcha/api/siteverify';
        $params = [
            'secret' => getSetting('recaptcha_private_key'),
            'response' => get()['token'] ?? '',
            'remoteip' => get_client_ip()
        ];
        $endpoint .= '?' . http_build_query($params);
        $fetch = fetch_url($endpoint);
        $json = json_decode($fetch);
        $isSuccess = (bool) $json->success;
        sessionVar()->put('isHuman', $isSuccess);
        sessionVar()->put('isBot', !$isSuccess);
        die($fetch);
    } catch (Exception $e) {
    }
    die();
};
