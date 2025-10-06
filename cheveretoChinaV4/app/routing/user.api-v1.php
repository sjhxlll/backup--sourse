<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function Chevere\Router\route;
use function Chevere\Router\routes;
use Chevereto\Controllers\Api\V1\Upload\UploadPostController;

return routes(
    route(
        path: '/api/1/upload/',
        POST: new UploadPostController()
    ),
);
