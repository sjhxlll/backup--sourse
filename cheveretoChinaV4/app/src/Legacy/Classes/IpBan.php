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

use function Chevereto\Legacy\G\datetimegmt;
use function Chevereto\Legacy\G\get_client_ip;
use function Chevereto\Legacy\G\is_valid_ip;
use Exception;

class IpBan
{
    public static function getSingle(array $args = []): array|bool
    {
        $args = array_merge([
            'ip' => get_client_ip()
        ], $args);
        $db = DB::getInstance();
        $query = 'SELECT * FROM ' . DB::getTable('ip_bans') . ' WHERE ';
        if (isset($args['id'])) {
            $query .= 'ip_ban_id = :id;';
        } else {
            $query .= ':ip LIKE ip_ban_ip AND (ip_ban_expires_gmt > :now OR ip_ban_expires_gmt IS NULL) ORDER BY ip_ban_id DESC;'; // wildcard are stored as % but displayed as *
        }
        $db->query($query);
        if (isset($args['id'])) {
            $db->bind(':id', $args['id']);
        } else {
            $db->bind(':ip', $args['ip']);
            $db->bind(':now', datetimegmt());
        }
        $ip_ban = $db->fetchSingle();
        if ($ip_ban) {
            $ip_ban = DB::formatRow($ip_ban, 'ip_ban');
            self::fill($ip_ban);

            return $ip_ban;
        } else {
            return false;
        }
    }

    public static function getAll(): array
    {
        $ip_bans_raw = DB::get('ip_bans', 'all');
        $ip_bans = [];
        if ($ip_bans_raw !== []) {
            foreach ($ip_bans_raw as $ip_ban) {
                $idx = $ip_ban['ip_ban_id'];
                $ip_bans[$idx] = DB::formatRow($ip_ban, 'ip_ban');
                self::fill($ip_bans[$idx]);
            }
        }

        return $ip_bans;
    }

    public static function delete(array $args = []): int
    {
        return DB::delete('ip_bans', $args);
    }

    public static function update(array $where = [], array $values = []): int
    {
        if ($values['ip'] ?? false) {
            $values['ip'] = str_replace('*', '%', $values['ip']);
        }

        return DB::update('ip_bans', $values, $where);
    }

    public static function insert(array $args = []): int
    {
        $args['ip'] = str_replace('*', '%', $args['ip']);

        return DB::insert('ip_bans', $args);
    }

    public static function fill(array &$ip_ban): void
    {
        $ip_ban['ip'] = str_replace('%', '*', $ip_ban['ip']);
    }

    public static function validateIP(string $ip, bool $wildcards = true): bool
    {
        $validate = true;
        if ($wildcards) {
            $base_ip = str_replace('*', '0', $ip);
            if (!is_valid_ip($ip) && !is_valid_ip($base_ip)) {
                $validate = false;
            }
        } elseif (!is_valid_ip($ip)) {
            $validate = false;
        }
        if (!$validate) {
            throw new Exception('Invalid IP address', 100);
        }

        return true;
    }
}
