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

use function Chevereto\Legacy\G\array_filter_array;
use function Chevereto\Legacy\G\datetime;
use function Chevereto\Legacy\G\datetimegmt;
use function Chevereto\Legacy\G\get_client_ip;
use Exception;

class Follow
{
    public static array $table_fields = [
        'date',
        'date_gmt',
        'user_id',
        'followed_user_id',
        'ip',
    ];

    public static function insert(array $args = []): int|array
    {
        self::validateInput($args);
        if (empty($args['ip'])) {
            $args['ip'] = get_client_ip();
        }
        $args = array_merge($args, [
            'date' => datetime(),
            'date_gmt' => datetimegmt(),
        ]);
        if ($args['user_id'] == $args['followed_user_id']) {
            throw new Exception("Can't auto follow yourself", 403);
        }
        $user_db = User::getSingle($args['user_id'], 'id', false);
        if ($user_db === []) {
            throw new Exception('User not found', 404);
        }
        if (self::doesFollow((int) $args['user_id'], (int) $args['followed_user_id'])) {
            throw new Exception("User already being followed", 404);
        }
        $db_insert_handle = [];
        foreach (self::$table_fields as $k) {
            $db_insert_handle['fields'][] = DB::getFieldPrefix('follows') . '_' . $k;
            $db_insert_handle['values'][] = '"' . $args[$k] . '"';
        }
        $db = DB::getInstance();
        $insert_query = "INSERT INTO " . DB::getTable('follows') . " (" . implode(', ', $db_insert_handle['fields']) . ") VALUES (" . implode(', ', $db_insert_handle['values']) . ");";
        $db->query($insert_query);
        $exec = $db->exec();
        $follow_id = $db->lastInsertId();
        if ($exec) {
            $sql_tpl =
                'UPDATE `%table_users` SET user_following = user_following + 1 WHERE user_id = %user_id;' . "\n" .
                'UPDATE `%table_users` SET user_followers = user_followers + 1 WHERE user_id = %followed_user_id;';
            $sql = strtr($sql_tpl, [
                '%table_users' => DB::getTable('users'),
                '%user_id' => $args['user_id'],
                '%followed_user_id' => $args['followed_user_id'],
            ]);
            DB::queryExecute($sql);
            Notification::insert([
                'table' => 'follows',
                'user_id' => $args['followed_user_id'],
                'trigger_user_id' => $args['user_id'],
                'type_id' => $follow_id,
            ]);

            return self::getFollowersCount($args);
        } else {
            return 0;
        }
    }

    public static function delete(array|string $args = []): bool|array
    {
        if (!is_array($args)) {
            $args = ['id' => $args];
        }
        $follow = self::getSingle($args);
        if ($follow === []) {
            return false;
        }
        $delete = DB::delete('follows', $args);
        if ($delete === 0) {
            return false;
        }
        $sql_tpl =
                'UPDATE `%table_users` SET user_following = user_following - 1 WHERE user_id = %user_id AND user_following > 0;' . "\n" .
                'UPDATE `%table_users` SET user_followers = user_followers - 1 WHERE user_id = %followed_user_id AND user_followers > 0;';
        $sql = strtr($sql_tpl, [
                '%table_users' => DB::getTable('users'),
                '%user_id' => $args['user_id'],
                '%followed_user_id' => $args['followed_user_id'],
            ]);
        DB::queryExecute($sql);
        Notification::delete([
                'table' => 'follows',
                'user_id' => $follow['followed_user_id'],
                'type_id' => $follow['id'],
            ]);

        return self::getFollowersCount($args);
    }

    public static function doesFollow(int $user_id, int $followed_id): bool
    {
        if ($user_id === 0) {
            return false;
        }
        $follow = DB::get('follows', ['user_id' => $user_id, 'followed_user_id' => $followed_id])[0] ?? [];

        return $follow !== []; // DB::formatRow($follow)
    }

    public static function getFollowersCount(array $args = []): array
    {
        self::validateInput($args);
        $user = User::getSingle($args['followed_user_id']);

        return array_filter_array($user, ['id', 'id_encoded', 'username', 'following', 'followers'], 'exclusion');
    }

    public static function getSingle(array $args = []): array
    {
        return self::get($args, [], 1);
    }

    public static function getAll(array $args = [], array $sort = []): array
    {
        return self::get($args, $sort, null);
    }

    public static function get(array $args, array $sort = [], int $limit = null): array
    {
        $get = DB::get('follows', $args, 'AND', $sort, $limit);

        return $get === false
            ? []
            : DB::formatRows($get, 'follow');
    }

    protected static function validateInput(array $args): void
    {
        if (empty($args['user_id'])) {
            throw new Exception('Missing user_id', 601);
        }
        if (empty($args['followed_user_id'])) {
            throw new Exception('Missing followed_user_id', 602);
        }
    }
}
