<?php

/*
 * This file is part of Chevereto.
 *
 * (c) Rodolfo Berrios <rodolfo@chevereto.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chevereto\Legacy\G;

use function Chevere\Message\message;
use Chevere\Throwable\Exceptions\LogicException;
use Chevereto\Config\Config;
use function Chevereto\Legacy\getSetting;
use function Chevereto\Vars\get;
use function Chevereto\Vars\post;
use function Chevereto\Vars\request;
use function Chevereto\Vars\server;
use function Chevereto\Vars\session;
use function Chevereto\Vars\sessionVar;
use Closure;
use Exception;

/** @deprecate V4 */
class Handler
{
    private array $hook_template = [];

    private static string|array $route;

    private static array $route_request = [];

    private static string $route_name = '';

    private static string $base_request = '';

    private static array $vars = [];

    private static array $conds = [];

    private static array $routes = [];

    private static string $template_used = '';

    private static bool $prevented_route = false;

    private static $mapped_args = [];

    private array $request_array;

    private string $relative_root;

    private string $base_url;

    private string $path_theme;

    private string $request_uri;

    private string $script_name;

    private string $valid_request;

    private string $canonical_request;

    private string $handled_request;

    private string $template;

    private array $request;

    public function __construct(bool $loadTemplate, ?Closure $before = null, ?Closure $after = null)
    {
        $this->relative_root = Config::host()->hostnamePath();
        $this->base_url = get_root_url();
        $this->path_theme = PATH_PUBLIC_LEGACY_THEME;
        $this->request_uri = server()['REQUEST_URI'] ?? '/';
        $this->script_name = server()['SCRIPT_NAME'] ?? '';
        $query_string = '?' . (server()['QUERY_STRING'] ?? '');
        if (!empty(server()['QUERY_STRING'])) {
            $this->request_uri = str_replace($query_string, '/', $this->request_uri);
        }
        $this->valid_request = '/' . ltrim(rtrim(sanitize_path_slashes($this->request_uri), '/'), '/');
        if (!empty(server()['QUERY_STRING'])) {
            $this->request_uri = server()['REQUEST_URI'];
            $this->valid_request .= $query_string;
        }
        $this->canonical_request = $this->valid_request;
        if ((is_dir(PATH_PUBLIC . $this->valid_request)
            || is_dir(dirname(PATH_PUBLIC) . $this->valid_request))
            && $this->valid_request !== '/'
        ) {
            $this->canonical_request .= '/';
        }
        $this->handled_request = strtok($this->relative_root == '/' ? $this->valid_request : preg_replace('#' . $this->relative_root . '#', '/', $this->request_uri, 1), '?');
        $this->request_array = explode('/', rtrim(str_replace('//', '/', ltrim($this->handled_request, '/')), '/'));
        if ($this->request_array[0] == '') {
            $this->request_array[0] = '/';
        }
        $this->request_array = array_values(array_filter($this->request_array, 'strlen'));
        self::$base_request = $this->request_array[0];
        if (self::$base_request == 'index') {
            redirect('/', 301);
        }
        if (self::$base_request !== '' && !empty(server()['QUERY_STRING'])) {
            $fixed_qs_request = $this->handled_request;
            parse_str(server()['QUERY_STRING'], $parse);
            if ($parse !== []) {
                $fixed_qs_request = rtrim($fixed_qs_request, '/') . '/';
                $index = -1;
                foreach ($parse as $k => $v) {
                    $index++;
                    $fixed_qs_request .= $index === 0 ? '?' : '&';
                    $fixed_qs_request .= urlencode($k);
                    if (is_string($v)) {
                        $fixed_qs_request .= '=' . urlencode($v);
                    }
                }
            }
            $this->canonical_request = $fixed_qs_request;
        }
        if (self::$base_request == 'index.php') {
            $this->canonical_request = rtrim($this->canonical_request, '/');
            redirect((sanitize_path_slashes(str_replace('index.php', '', $this->canonical_request))), 301);
        }

        if ($this->relative_root !== $this->request_uri
            && $this->canonical_request !== $this->request_uri
        ) {
            $this->baseRedirection($this->canonical_request);
        }
        if (in_array(self::$base_request, ['', 'index.php', '/'])) {
            self::$base_request = 'index';
        }
        $this->template = self::$base_request;
        $this->request = $this->request_array;
        self::$route_request = $this->request_array;
        self::$route = $this->template !== '404'
            ? ($this->request_array[0] == '/'
                ? 'index'
                : $this->request_array)
            : '404';
        unset($this->request[0]);
        $this->request = array_values($this->request);
        if (is_callable($before)) {
            $before($this);
        }
        if ($this->isIndex()) {
            $this->processRequest();
        }
        if (is_callable($after)) {
            $after($this);
        }
        if ($loadTemplate) {
            $this->loadTemplate();
        }
    }

    public function request_array(): array
    {
        return $this->request_array;
    }

    public function template(): string
    {
        return $this->template;
    }

    public function setTemplate(string $template): void
    {
        $this->template = $template;
    }

    public function setPathTheme(string $path): void
    {
        $this->path_theme = $path;
    }

    public static function baseRequest(): string
    {
        return self::$base_request;
    }

    public static function isPreventedRoute(): bool
    {
        return self::$prevented_route;
    }

    public static function mappedArgs(): array
    {
        return self::$mapped_args;
    }

    public function requestArray(): array
    {
        return $this->request_array;
    }

    public function request(): array
    {
        return $this->request;
    }

    private function processRequest(): void
    {
        $route = $this->getRouteFn(self::$base_request);
        if (is_callable($route)) {
            $routes[self::$base_request] = $route;
        }
        if (is_array($routes) && array_key_exists(self::$base_request, $routes)) {
            $magic = [
                'post' => post() ? post() : null,
                'get' => get() ? get() : null,
                'request' => request() ? request() : null,
                'safe_post' => post() ? safe_html(post()) : null,
                'safe_get' => get() ? safe_html(get()) : null,
                'safe_request' => request() ? safe_html(request()) : null,
                'auth_token' => self::getAuthToken()
            ];

            self::$vars = self::$vars !== []
                    ? array_merge(self::$vars, $magic)
                    : $magic;
            if (!self::$prevented_route && is_callable($routes[self::$base_request])) {
                $routes[self::$base_request]($this);
            }
        } else {
            $this->issueError(404);
            $this->request = $this->request_array;
        }
        if ($this->template == 404) {
            self::$route = '404';
        }
        self::setCond('404', $this->template == '404');
        if (isset(self::$vars['pre_doctitle'])) {
            $stock_doctitle = self::$vars['doctitle'];
            self::$vars['doctitle'] = self::$vars['pre_doctitle'];
            if ($stock_doctitle) {
                self::$vars['doctitle'] .= ' - ' . $stock_doctitle;
            }
        }
        self::$template_used = $this->template;
    }

    public function issueError(int $status): void
    {
        set_status_header($status);
        $name = strval($status);
        if ($this->cond('mapped_route')) {
            self::$base_request = self::$route_request[0];
            self::$route_name = $name;
        }
        $this->template = $name;
    }

    public function preventRoute(?string $tpl = null): void
    {
        if ($tpl !== null) {
            $this->template = $tpl;
        }
        self::$prevented_route = true;
    }

    public function getRouteFn(string $route_name): callable
    {
        if (array_key_exists($route_name, self::$routes)) {
            return self::$routes[$route_name];
        }
        $filename = $route_name . '.php';
        $route_file = PATH_APP_LEGACY_ROUTES . $filename;
        $route_override_file = PATH_APP_LEGACY_ROUTES_OVERRIDES . $filename;
        if (file_exists($route_override_file)) {
            $route_file = $route_override_file;
        }
        if (!file_exists($route_file)) {
            $route_name = getSetting('root_route');
            $route_file = PATH_APP_LEGACY_ROUTES . $route_name . ".php";
            $this->template = $route_name;
        }
        if (file_exists($route_file)) {
            /** @var callable $route */
            $route = require $route_file;
            self::$routes[$route_name] = $route;
            self::$route_name = $route_name;

            return $route;
        }

        throw new LogicException(
            message('Missing route file %file%')
                ->withCode('%file%', $route_file)
        );
    }

    public function mapRoute(string $route_name, array $args = null): callable
    {
        $this->template = $route_name;
        self::$base_request = $route_name;
        self::setCond('mapped_route', true);
        if (!is_null($args)) {
            self::$mapped_args = $args;
        }

        return $this->getRouteFn($route_name);
    }

    public function isRequestLevel(int $level): bool
    {
        return isset($this->request_array[$level - 1]);
    }

    public function baseRedirection(string $request): void
    {
        $request = trim(sanitize_path_slashes($request), '/');
        $url = preg_replace('{' . $this->relative_root . '}', '/', $this->base_url, 1) . $request;
        redirect($url, 301);
    }

    private function isIndex(): bool
    {
        return (bool) preg_match('{index\.php$}', ltrim($this->script_name, '/'));
    }

    public function hookTemplate(array $args = []): void
    {
        if (in_array($args['where'], ['before', 'after']) && $args['code']) {
            $this->hook_template[$args['where']] = $args['code'];
        }
    }

    private function loadTemplate(string $template = null): void
    {
        if (!is_null($template)) {
            $this->template = $template;
        }
        $functions_basename = 'functions.php';
        $template_functions = [
            $this->path_theme . 'overrides/' . $functions_basename,
            $this->path_theme . $functions_basename
        ];
        foreach ($template_functions as $file) {
            if (file_exists($file)) {
                require $file;

                break;
            }
        }
        $view_basename = $this->template;
        $view_extension = get_file_extension($this->template);
        if ($view_extension === '' || $view_extension === '0') {
            $view_extension = 'php';
            if (str_ends_with($this->path_theme, '/pages/')) {
                $view_extension = Config::enabled()->phpPages()
                    ? 'php'
                    : 'html';
            }
            $view_basename .= '.' . $view_extension;
        }
        $template_file = [
            $this->path_theme . 'overrides/views/' . $view_basename,
            $this->path_theme . 'overrides/' . $view_basename,
            $this->path_theme . 'views/' . $view_basename,
            $this->path_theme . $view_basename,
        ];
        foreach ($template_file as $file) {
            if (file_exists($file)) {
                if ($view_extension == 'html') {
                    include_theme_header();
                }
                if (isset($this->hook_template['before'])) {
                    echo $this->hook_template['before'];
                }
                if ($view_extension == 'php') {
                    require $file;
                } else {
                    echo file_get_contents($file);
                }
                if (isset($this->hook_template['after'])) {
                    echo $this->hook_template['after'];
                }
                if ($view_extension == 'html') {
                    include_theme_footer();
                }

                return;
            }
        }
        $end = end($template_file);
        $key = key($template_file);

        throw new Exception('Missing ' . absolute_to_relative($template_file[$key]) . ' template file');
    }

    public static function getAuthToken(): string
    {
        $token = isset(session()['G_auth_token'])
            ? session()['G_auth_token']
            : random_string(40);
        sessionVar()->put('G_auth_token', $token);

        return $token;
    }

    public static function checkAuthToken(string $token): bool
    {
        if (strlen($token) < 40) {
            return false;
        }

        return timing_safe_compare(session()['G_auth_token'], $token);
    }

    public static function setVar(string $var, mixed $value): void
    {
        self::$vars[$var] = $value;
    }

    public static function setVars(array $array = []): void
    {
        foreach ((array) $array as $var => $value) {
            self::$vars[$var] = $value;
        }
    }

    public static function setCond(string $cond, bool $bool): void
    {
        self::$conds[$cond] = $bool;
    }

    public static function setConds(array $array = []): void
    {
        foreach ((array) $array as $conds => $bool) {
            self::$conds[$conds] = (bool) $bool;
        }
    }

    public static function hasVar(string $var): bool
    {
        return array_key_exists($var, self::vars());
    }

    public static function var($var): mixed
    {
        return self::vars()[$var] ?? null;
    }

    public static function vars(): array
    {
        return self::$vars;
    }

    public static function hasCond(string $cond): bool
    {
        return array_key_exists($cond, self::conds());
    }

    public static function cond(string $cond): bool
    {
        return self::conds()[$cond] ?? false;
    }

    public static function conds(): array
    {
        return self::$conds;
    }

    public static function updateVar(string $var, mixed $value)
    {
        if (is_array(self::$vars[$var]) && is_array($value)) {
            $value += self::$vars[$var]; // replacement + replaced
            ksort($value);
        }
        self::$vars[$var] = $value;
    }

    public static function unsetVar(string $var): void
    {
        unset(self::$vars[$var]);
    }

    public static function getTemplateUsed(): string
    {
        return self::$template_used;
    }

    public static function getRoutePath(bool $full = true): string
    {
        if (is_array(self::$route)) {
            return $full ? implode('/', self::$route) : self::$route[0];
        } else {
            return self::$route;
        }
    }

    public static function getRouteName(): string
    {
        return self::$route_name;
    }
}
