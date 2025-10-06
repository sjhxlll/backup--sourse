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

use Chevereto\Config\Config;
use function Chevereto\Legacy\G\get_base_url;
use function Chevereto\Legacy\G\get_file_extension;
use function Chevereto\Legacy\G\is_url;
use function Chevereto\Legacy\G\str_replace_last;
use function Chevereto\Legacy\G\unlinkIfExists;
use function Chevereto\Vars\get;
use function Chevereto\Vars\post;
use Exception;

class Page
{
    public static array $table_fields = [
            'url_key',
            'type',
            'file_path',
            'link_url',
            'icon',
            'title',
            'description',
            'keywords',
            'is_active',
            'is_link_visible',
            'attr_target',
            'attr_rel',
            'sort_display',
            'internal',
            'code'
        ];

    public static function getSingle(string $var, $by = 'url_key'): ?array
    {
        return self::get([$by => $var], [], 1);
    }

    public static function getAll(array $args = [], array $sort = []): ?array
    {
        $page = self::get($args, $sort, null);

        return $page ?? null;
    }

    public static function get(array $values, array $sort = [], int $limit = null): ?array
    {
        $get = DB::get('pages', $values, 'AND', $sort, $limit);
        if (is_bool($get)) {
            $get = null;
        }
        if (isset($get[0]) && is_array($get[0])) {
            foreach ($get as $k => $v) {
                self::formatRowValues($get[$k], $v);
            }
        } elseif (is_array($get) && $get !== []) {
            self::formatRowValues($get);
        }

        return $get;
    }

    public static function getPath(?string $var = null): string
    {
        return PATH_PUBLIC_CONTENT_PAGES . (is_string($var) ? $var : '');
    }

    public static function getFields(): array
    {
        return self::$table_fields;
    }

    public static function update(int $id, array $values): int
    {
        return DB::update('pages', $values, ['id' => $id]);
    }

    public static function writePage(array $args = []): bool
    {
        if (!$args['file_path']) {
            throw new Exception("Missing file_path argument", 600);
        }
        $file_path = self::getPath($args['file_path']);
        $file_dirname = dirname($file_path);
        $code = empty($args['code']) ? null : $args['code'];
        if (!is_dir($file_dirname)) {
            $base_perms = fileperms(self::getPath());
            $old_umask = umask(0);
            if (mkdir($file_dirname, $base_perms, true)) {
                chmod($file_dirname, $base_perms);
                umask($old_umask);
            } else {
                throw new Exception(_s("Can't create %s destination dir", $file_dirname), 600);
            }
        }
        if (file_exists($file_path) && $code == null && filesize($file_path) == 0) {
            return true;
        }
        $fh = fopen($file_path, 'w');
        $st = !$fh || fwrite($fh, $code ?? '') === false ? false : true;
        fclose($fh);
        if (!$st) {
            throw new Exception(_s("Can't open %s for writing", $file_path), 601);
        }

        return true;
    }

    public static function fill(array &$page): void
    {
        $page['title_html'] = $page['title'] ?? '';
        $type_tr = [
            'internal' => _s('Internal'),
            'link' => _s('Link')
        ];
        $page['type_tr'] = $type_tr[$page['type']];

        switch ($page['type']) {
            case 'internal':
                $page['url'] = get_base_url('page/' . $page['url_key']);
                if (empty($page['file_path'])) {
                    $filepaths = [
                        'default' => 'default/',
                        'user' => null // base
                    ];
                    $file_basename = $page['url_key'] . '.php';
                    foreach ($filepaths as $k => $v) {
                        if (is_readable(self::getPath($v) . $file_basename)) {
                            $page['file_path'] = $v . $file_basename;
                        }
                    }
                } else {
                    $page_extension = get_file_extension($page['file_path']);
                    if (!Config::enabled()->phpPages() && $page_extension == 'php') {
                        $page['file_path'] = str_replace_last($page_extension, 'html', $page['file_path']);
                    }
                    if ($page['internal'] === 'contact'
                    && (post() !== [] || (get()['sent'] ?? '0' == '1'))) {
                        $page_extension = 'php';
                        $page['file_path'] = 'default/contact.php';
                    }
                }
                $page['file_path_absolute'] = self::getPath($page['file_path']);
                if (!file_exists($page['file_path_absolute'])) {
                    self::writePage(['file_path' => $page['file_path'], 'code' => $page['code']]);
                }

            break;
            case 'link':
                $page['url'] = is_url($page['link_url']) ? $page['link_url'] : null;

            break;
        }
        $page['link_attr'] = 'href="' . $page['url'] . '"';
        if ($page['attr_target'] !== '_self') {
            $page['link_attr'] .= ' target="' . $page['attr_target'] . '"';
        }
        if (!empty($page['attr_rel'])) {
            $page['link_attr'] .= ' rel="' . $page['attr_rel'] . '"';
        }
        if (!empty($page['icon'])) {
            $page['title_html'] = '<span class="btn-icon ' . $page['icon'] . '"></span> ' . $page['title_html'];
        }
    }

    public static function formatRowValues(mixed &$values, mixed $row = []): void
    {
        $values = DB::formatRow($row !== [] ? $row : $values, 'page');
        if (is_array($values)) {
            self::fill($values);
        }
    }

    public static function insert(array $values = []): int
    {
        return DB::insert('pages', $values);
    }

    public static function delete(array|int $page): int
    {
        if (!is_array($page)) {
            $page = self::getSingle((string) $page, 'id');
        }
        if ($page['type'] == 'internal' && file_exists($page['file_path_absolute'])) {
            unlinkIfExists($page['file_path_absolute']);
        }

        return DB::delete('pages', ['id' => $page['id']]);
    }
}
