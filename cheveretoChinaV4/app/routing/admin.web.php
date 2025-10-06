<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use function App\Controllers\legacyController;
use function Chevere\Router\route;
use function Chevere\Router\routes;

return routes(
    route(
        name: 'dashboard',
        path: '/dashboard/',
        GET: legacyController('route.dashboard.php'),
        POST: legacyController('route.dashboard.php'),
    ),
    route(
        name: 'importer-jobs',
        path: '/importer-jobs/',
        GET: legacyController('route.importer-jobs.php'),
    ),
    route(
        name: 'install',
        path: '/install/',
        GET: legacyController('route.install.php'),
        POST: legacyController('route.install.php'),
    ),
    route(
        name: 'update',
        path: '/update/',
        POST: legacyController('route.update.php'),
    ),
);
