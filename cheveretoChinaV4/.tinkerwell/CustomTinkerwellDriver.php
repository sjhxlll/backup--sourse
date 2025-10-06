<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function Chevereto\Legacy\loaderHandler;

class CustomTinkerwellDriver extends TinkerwellDriver
{
    public function canBootstrap($projectPath)
    {
        return file_exists($projectPath . '/app/legacy/load/loader.php');
    }

    public function bootstrap($projectPath)
    {
        define('ACCESS', 'web');
        define('REPL', true);
        require $projectPath . '/app/legacy/load/loader.php';
        include loaderHandler(
            _cookie: [],
            _env: $_ENV,
            _files: [],
            _get: [],
            _post: [],
            _request: [],
            _server: [],
            _session: [
                'G_auth_token' => str_repeat('a', 40),
            ],
        );
    }

    public function contextMenu()
    {
        return [
            Label::create('Detected Chevereto v4'),
            OpenURL::create('Chevereto Docs', 'https://v4-docs.chevereto.com/'),
        ];
    }
}
