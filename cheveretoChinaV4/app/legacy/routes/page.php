<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Legacy\Classes\Page;
use function Chevereto\Legacy\G\add_ending_slash;
use Chevereto\Legacy\G\Handler;

return function (Handler $handler) {
    $request_url_key = implode('/', $handler->request());
    $page = Page::getSingle($request_url_key);
    if (!$page || !$page['is_active'] || $page['type'] !== 'internal') {
        $handler->issueError(404);

        return;
    }
    if (!$page['file_path_absolute']) {
        $handler->issueError(404);

        return;
    }
    if (!file_exists($page['file_path_absolute'])) {
        $handler->issueError(404);

        return;
    }
    $pathinfo = pathinfo($page['file_path_absolute']);
    $handler->setPathTheme(add_ending_slash($pathinfo['dirname']));
    $handler->setTemplate($pathinfo['filename']);
    $page_metas = [
        'pre_doctitle' => $page['title'],
        'meta_description' => htmlspecialchars($page['description'] ?? ''),
        'meta_keywords' => htmlspecialchars($page['keywords'] ?? '')
    ];
    foreach ($page_metas as $k => $v) {
        if ($v === null) {
            continue;
        }
        $handler->setVar($k, $v);
    }
};
