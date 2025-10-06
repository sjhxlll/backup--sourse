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

use function Chevereto\Legacy\G\datetime;
use function Chevereto\Legacy\G\datetimegmt;
use function Chevereto\Legacy\G\get_client_ip;
use Exception;

class Like
{
    public static array $table_fields = [
        'user_id',
        'date',
        'date_gmt',
        'content_id',
        'content_type',
        'content_user_id',
        'ip',
    ];

    public static function insert($args = []): array|bool
    {
        self::validateInput($args);
        if (empty($args['ip'])) {
            $args['ip'] = get_client_ip();
        }
        $args = array_merge($args, [
            'date' => datetime(),
            'date_gmt' => datetimegmt(),
        ]);
        $type = $args['content_type']; // image | album
        $table = $type . 's';
        $content_db = DB::get($table, ['id' => $args['content_id']])[0];
        if (!$content_db) {
            throw new Exception('Content not found', 600);
        }
        if ($table == 'images' && !$content_db['image_is_approved']) {
            throw new Exception('Content is not approved', 601);
        }
        $args['content_user_id'] = $content_db[$type . '_user_id'];
        ksort($args);
        $db_table_fields = [];
        asort(static::$table_fields);
        foreach (static::$table_fields as $k) {
            $db_table_fields[] = 'like_' . $k;
        }
        $db_insert_values = [];
        foreach ($args as $k => $v) {
            $value = is_null($v) ? "NULL" : "'$v'";
            $db_insert_values[] = $value . " as $k";
        }
        $db = DB::getInstance();
        $insert_query = "INSERT INTO " . DB::getTable('likes') . " (" . implode(', ', $db_table_fields) . ") SELECT * FROM (SELECT " . implode(', ', $db_insert_values) . ") AS tmp WHERE NOT EXISTS (SELECT * FROM " . DB::getTable('likes') . " WHERE like_user_id = :user_id AND like_content_id = :content_id AND like_content_type = :content_type) LIMIT 1;";
        $db->query($insert_query);
        foreach (['user_id', 'content_id', 'content_type'] as $k) {
            $db->bind(':' . $k, $args[$k]);
        }
        $exec = $db->exec();
        $like_id = $db->lastInsertId();
        if ($exec) {
            $tables = DB::getTables();
            $sql_tpl = [
                'UPDATE `%table_' . $table . '` SET ' . $type . '_likes = ' . $type . '_likes + 1 WHERE ' . $type . '_id = %' . $type . '_id;',
                'UPDATE `%table_users` SET user_liked = user_liked + 1 WHERE user_id = %like_user_id;'
            ];
            if ((isset($args['user_id']) && isset($args['content_user_id'])) && $args['user_id'] !== $args['content_user_id']) {
                $sql_tpl[] = 'UPDATE `%table_users` SET user_likes = user_likes + 1 WHERE user_id = %content_user_id;';
                Notification::insert([
                    'table' => 'likes',
                    'content_type' => $type,
                    'user_id' => $args['content_user_id'],
                    'trigger_user_id' => $args['user_id'],
                    'type_id' => $like_id,
                ]);
            }
            $sql_tpl = implode("\n", $sql_tpl);
            $sql = strtr($sql_tpl, [
                '%table_images' => $tables['images'],
                '%table_albums' => $tables['albums'],
                '%table_users' => $tables['users'],
                '%image_id' => $args['content_id'],
                '%album_id' => $args['content_id'],
                '%like_user_id' => $args['user_id'],
                '%content_user_id' => $args['content_user_id'],
            ]);
            DB::queryExecute($sql);
            Stat::track([
                'action' => 'insert',
                'table' => 'likes',
                'content_type' => $type,
                'value' => '+1',
                'date_gmt' => $args['date_gmt']
            ]);

            return [
                'id' => $args['content_id'],
                'type' => $args['content_type'],
                'likes' => self::getContentLikesCount($args),
            ];
        } else {
            return false;
        }
    }

    public static function delete(array $args = []): array|bool
    {
        if (!is_array($args)) {
            $args = ['id' => $args['id']];
        }
        $type = $args['content_type']; // image | album
        $table = $type . 's';
        $like = self::getSingle($args);
        if ($like === []) {
            return false;
        }
        $delete = DB::delete('likes', $args);
        if ($delete !== 0) {
            $content = DB::get($table, ['id' => $args['content_id']])[0];
            Stat::track([
                'action' => 'delete',
                'table' => 'likes',
                'content_type' => $type,
                'value' => '-1',
                'date_gmt' => $like['date_gmt']
            ]);
            Notification::delete([
                'table' => 'likes',
                'user_id' => $content[$type . '_user_id'],
                'type_id' => $like['id'],
            ]);
            $sql_tpl =
                'UPDATE `%table_' . $table . '` SET ' . $type . '_likes = ' . $type . '_likes - 1 WHERE ' . $type . '_id = %content_id AND ' . $type . '_likes > 0;' . "\n" .
                'UPDATE `%table_users` SET user_liked = user_liked - 1 WHERE user_id = %like_user_id AND user_liked > 0;';

            if (isset($content['image_user_id']) && $args['user_id'] !== $content['image_user_id']) {
                $sql_tpl .= "\n" . 'UPDATE `%table_users` SET user_likes = user_likes - 1 WHERE user_id = %content_user_id AND user_likes > 0;';
            }
            $sql = strtr($sql_tpl, [
                '%table_images' => DB::getTable('images'),
                '%table_albums' => DB::getTable('albums'),
                '%table_users' => DB::getTable('users'),
                '%table_notifications' => DB::getTable('notifications'),
                '%content_id' => $args['content_id'] ?? '',
                '%content_type' => $args['content_type'] ?? '',
                '%content_user_id' => $content[$type . '_user_id'] ?? '',
                '%like_id' => $like['id'],
                '%like_user_id' => $args['user_id'] ?? ''
            ]);
            DB::queryExecute($sql);

            return [
                'id' => $args['content_id'],
                'type' => $args['content_type'],
                'likes' => self::getContentLikesCount($args),
            ];
        } else {
            return false;
        }
    }

    public static function getContentLikesCount(array $args = []): int
    {
        self::validateInput($args);
        $type = $args['content_type'];
        $table = $type . 's';

        return (int) DB::get($table, ['id' => $args['content_id']])[0][$type . '_likes'];
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
        $get = DB::get('likes', $args, 'AND', $sort, $limit);

        return $get === false
            ? []
            : DB::formatRows($get, 'like');
    }

    protected static function validateInput(array $args = []): void
    {
        if (empty($args['user_id'])) {
            throw new Exception('Missing user_id', 601);
        } else {
            $user_db = User::getSingle($args['user_id'], 'id', false);
            if ($user_db === []) {
                throw new Exception('Invalid user_id', 602);
            }
        }
        if (empty($args['content_id'])) {
            throw new Exception('Missing content_id', 603);
        }
        if (!in_array($args['content_type'], ['image', 'album'])) {
            throw new Exception('Invalid content_type', 604);
        }
    }
}
