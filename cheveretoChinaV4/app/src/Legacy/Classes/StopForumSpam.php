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

use function Chevereto\Legacy\G\fetch_url;

class StopForumSpam
{
    public const THRESHOLD = 3;

    final public function __construct(
        private string $ip,
        private string $email,
        private string $username
    ) {
    }

    final public function isSpam(): bool
    {
        $json = $this->fetch();

        return $json->ip->frequency >= static::THRESHOLD || $json->email->frequency >= static::THRESHOLD || $json->username->frequency >= static::THRESHOLD;
    }

    private function fetch(): object
    {
        $url = 'http://api.stopforumspam.org/api?ip=' . $this->ip . '&email=' . $this->email . '&username=' . $this->username . '&json';
        $json = fetch_url($url);

        return json_decode($json);
    }
}
