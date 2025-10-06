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
use Exception;

class Notification
{
    public static array $content_types = ['image', 'album', 'like', 'follow'];

    public static function get(array $args = []): array
    {
        $tables = DB::getTables();
        $db = DB::getInstance();
        $db->query('SELECT * FROM ' . $tables['notifications'] . '
            LEFT JOIN ' . $tables['likes'] . ' ON notification_type = "like" AND notification_type_id = like_id AND notification_type_id > 0
            LEFT JOIN ' . $tables['follows'] . ' ON notification_type = "follow" AND notification_type_id = follow_id
            LEFT JOIN ' . $tables['images'] . ' ON notification_content_type = "image" AND like_content_type = "image" AND like_content_id = image_id
            LEFT JOIN ' . $tables['albums'] . ' ON notification_content_type = "album" AND like_content_type = "album" AND like_content_id = album_id
            LEFT JOIN ' . $tables['users'] . ' ON user_id = (
                CASE notification_type
                    WHEN "like" THEN like_user_id
                    WHEN "follow" THEN follow_user_id
                    ELSE NULL
                END
            )
        WHERE notification_user_id = :user_id AND notification_type_id > 0 ORDER BY notification_id DESC LIMIT 50;');
        $db->bind(':user_id', $args['user_id']);
        $get = $db->fetchAll();
        if ($get === false) {
            return [];
        }
        if (isset($get[0])) {
            foreach ($get as $k => $v) {
                DB::formatRowValues($get[$k], $v);
                self::fill($get[$k]);
            }
        } else {
            DB::formatRowValues($get);
            self::fill($get);
        }

        return $get;
    }

    public static function insert(array $args = []): void
    {
        foreach (['user_id', 'trigger_user_id', 'type_id'] as $v) {
            if (empty($args[$v])) {
                throw new Exception('Missing ' . $v . ' value', 601);
            }
        }
        $tables = DB::getTables();
        $sql_tpl = 'INSERT INTO `%table_notifications` (notification_date_gmt, notification_user_id, notification_trigger_user_id, notification_type, notification_content_type, notification_type_id) VALUES ("%date_gmt", %user_id, %trigger_user_id, "%action", "%content_type", %type_id) ON DUPLICATE KEY UPDATE notification_is_read = 0;';
        switch ($args['table']) {
            case 'likes':
                $action = 'like';
                $content_type = $args['content_type'];

            break;
            case 'follows':
                $action = 'follow';
                $content_type = 'user';

            break;
        }
        $sql_tpl .= "\n" . 'UPDATE `%table_users` SET user_notifications_unread = user_notifications_unread + 1 WHERE user_id = %user_id;';
        $sql = strtr($sql_tpl, [
            '%date_gmt' => datetimegmt(),
            '%action' => $action ?? '',
            '%content_type' => $content_type ?? '',
            '%user_id' => $args['user_id'],
            '%trigger_user_id' => $args['trigger_user_id'],
            '%type_id' => $args['type_id'],
            '%table_users' => $tables['users'],
            '%table_notifications' => $tables['notifications'],
        ]);
        DB::queryExecute($sql);
    }

    public static function delete(array $args = []): void
    {
        $tables = DB::getTables();
        $sql_tpl = '';
        switch ($args['table']) {
            case 'images':
                $sql_tpl = 'DELETE IGNORE `%table_notifications` FROM `%table_notifications` INNER JOIN `%table_likes` ON like_content_id = %image_id WHERE notification_type = "like" AND notification_content_type = "image" AND notification_type_id = like_id;';

            break;
            case 'users':
                $sql_tpl =
                    'UPDATE IGNORE `%table_users` AS U
                        INNER JOIN (
                            SELECT notification_user_id, COUNT(*) AS cnt
                            FROM `%table_notifications`
                                WHERE notification_trigger_user_id = %user_id AND notification_is_read = 0
                            GROUP BY notification_user_id
                        ) AS N ON U.user_id = N.notification_user_id
                    SET U.user_notifications_unread = GREATEST(U.user_notifications_unread - COALESCE(N.cnt, "0"), 0);'
                    . "\n"
                    . 'DELETE IGNORE `%table_notifications` FROM `%table_notifications`
                        LEFT JOIN `%table_follows` ON notification_type_id = follow_id AND follow_user_id = %user_id
                        LEFT JOIN `%table_likes` ON notification_type_id = like_id AND like_user_id = %user_id
                    WHERE (notification_type = "follow" AND notification_type_id = follow_id) OR (notification_type = "like" AND notification_type_id = like_id);'
                    . "\n";
                $sql_tpl .=
                    'DELETE IGNORE FROM `%table_notifications` WHERE notification_user_id = %user_id;';

            break;
            default: // likes, follows
                if (isset($args['user_id'])) {
                    $sql_tpl = 'DELETE IGNORE FROM `%table_notifications` WHERE notification_user_id = %user_id AND notification_type = "%type" AND notification_type_id = %type_id;';
                }

            break;
        }
        if (isset($args['user_id']) && $args['table'] !== 'users') {
            $sql_tpl .= "\n" . 'UPDATE `%table_users` SET user_notifications_unread = COALESCE((SELECT COUNT(*) FROM `%table_notifications` WHERE notification_user_id = %user_id AND notification_is_read = 0), 0) WHERE user_id = %user_id;';
        }
        $table_to_types = [
            'likes' => 'like',
            'follows' => 'follow'
        ];
        $sql = strtr($sql_tpl, [
            '%table_notifications' => $tables['notifications'],
            '%table_likes' => $tables['likes'],
            '%table_users' => $tables['users'],
            '%table_follows' => $tables['follows'],
            '%image_id' => $args['image_id'] ?? '',
            '%user_id' => $args['user_id'] ?? '',
            '%type' => $table_to_types[$args['table']] ?? '',
            '%type_id' => $args['type_id'] ?? '',
        ]);
        if (!empty($sql)) {
            DB::queryExecute($sql);
        }
    }

    public static function markAsRead(array $args = []): void
    {
        if ($args === []) {
            throw new Exception('Empty args', 600);
        }
        DB::update('notifications', ['is_read' => 1], $args);
        DB::update('users', ['notifications_unread' => 0], ['id' => $args['user_id']]);
    }

    protected static function fill(array &$row): void
    {
        foreach (self::$content_types as $k) {
            if (!isset($row[$k]['id'])) {
                unset($row[$k]);
            } elseif (in_array($k, ['image', 'album'])) {
                $formatfn = 'Chevereto\Legacy\Classes\\' . ucfirst($k);
                $formatfn::fill($row[$k]);
            }
        }
        if (isset($row['user']['id'])) {
            User::fill($row['user']);
        }
    }
}
