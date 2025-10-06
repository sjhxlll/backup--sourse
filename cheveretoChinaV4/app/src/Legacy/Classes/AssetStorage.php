<?php

/*
 * This file is part of Chevereto.
 *
 * (c) Rodolfo Berrios <rodolfo@chevereto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chevereto\Legacy\Classes;

use Chevereto\Traits\Instance\AssertNoInstanceTrait;

final class AssetStorage
{
    use AssertNoInstanceTrait;

    protected static array $storage = [];

    protected static bool $isLocalLegacy;

    public function __construct(array $storage)
    {
        $this->assertNoInstance();
        self::$storage = $storage;
        self::$isLocalLegacy = Storage::getApiType((int) $storage['api_id']) == 'local'
            && PATH_PUBLIC === $storage['bucket'];
    }

    public static function getStorage(): array
    {
        return self::$storage;
    }

    public static function isLocalLegacy(): bool
    {
        return self::$isLocalLegacy;
    }
}
