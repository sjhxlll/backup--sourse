<?php

/*
 * This file is part of CheveretoChina.
 *
 * (c) MoeIDC <noreply@itxe.net>
 *
 * For the full CheveretoChina and update information, please view the MoeBBS
 * file that was distributed on https://bbs.idc.moe
 */

use Chevereto\Config\AssetConfig;
use Chevereto\Config\Config;
use Chevereto\Config\EnabledConfig;
use Chevereto\Config\HostConfig;
use Chevereto\Config\LimitConfig;
use Chevereto\Config\SystemConfig;
use function Chevereto\Vars\env;

new Config(
    asset: new AssetConfig(
        accountId: env()['CHEVERETO_ASSET_STORAGE_ACCOUNT_ID'],
        accountName: env()['CHEVERETO_ASSET_STORAGE_ACCOUNT_NAME'],
        bucket: env()['CHEVERETO_ASSET_STORAGE_BUCKET'],
        key: env()['CHEVERETO_ASSET_STORAGE_KEY'],
        name: env()['CHEVERETO_ASSET_STORAGE_NAME'],
        region: env()['CHEVERETO_ASSET_STORAGE_REGION'],
        secret: env()['CHEVERETO_ASSET_STORAGE_SECRET'],
        server: env()['CHEVERETO_ASSET_STORAGE_SERVER'],
        service: env()['CHEVERETO_ASSET_STORAGE_SERVICE'],
        type: env()['CHEVERETO_ASSET_STORAGE_TYPE'],
        url: env()['CHEVERETO_ASSET_STORAGE_URL'],
    ),
    enabled: new EnabledConfig(
        phpPages: (bool) env()['CHEVERETO_ENABLE_PHP_PAGES'],
        updateCli: (bool) env()['CHEVERETO_ENABLE_UPDATE_CLI'],
        updateHttp: false,
        htaccessCheck: (bool) env()['CHEVERETO_ENABLE_HTACCESS_CHECK']
    ),
    host: new HostConfig(
        hostnamePath: env()['CHEVERETO_HOSTNAME_PATH'],
        hostname: env()['CHEVERETO_HOSTNAME'],
        isHttps: (bool) env()['CHEVERETO_HTTPS'],
    ),
    system: new SystemConfig(
        debugLevel: (int) env()['CHEVERETO_DEBUG_LEVEL'],
        errorLog: env()['CHEVERETO_ERROR_LOG'],
        imageFormatsAvailable: json_decode(
            env()['CHEVERETO_IMAGE_FORMATS_AVAILABLE'],
            true
        ),
        imageLibrary: env()['CHEVERETO_IMAGE_LIBRARY'],
        sessionSaveHandler: env()['CHEVERETO_SESSION_SAVE_HANDLER'],
        sessionSavePath: env()['CHEVERETO_SESSION_SAVE_PATH'],
    ),
    limit: new LimitConfig(
        invalidRequestsPerDay: 25
    )
);
