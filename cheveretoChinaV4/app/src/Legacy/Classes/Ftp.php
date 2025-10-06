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

use Exception;
use Throwable;

class Ftp
{
    private $ftp;

    public function __construct(array $args = [])
    {
        if (!function_exists('ftp_connect')) {
            throw new Exception("ftp_connect function doesn't exists in this setup. You must enable PHP FTP support to interact with FTP servers.", 600);
        }
        foreach (['server', 'user', 'password'] as $v) {
            if (!array_key_exists($v, $args)) {
                throw new Exception("Missing $v value", 600);
            }
        }
        $parsed_server = parse_url($args['server']);
        $host = $parsed_server['host'] ?? $args['server'];
        $port = $parsed_server['port'] ?? 21;

        try {
            $this->ftp = ftp_connect($host, $port);
        } catch (Throwable $e) {
            throw new Exception("Can't connect to " . $args['server'] . " server", 600, $e);
        }

        try {
            ftp_login($this->ftp, $args['user'], $args['password']);
        } catch (Throwable $e) {
            throw new Exception("Can't FTP login to " . $args['server'] . " server", 601, $e);
        }
        $args['passive'] = isset($args['passive']) ? (bool)$args['passive'] : true;

        try {
            ftp_pasv($this->ftp, $args['passive']);
        } catch (Throwable $e) {
            throw new Exception("Can't " . ($args['passive'] ? "enable" : "disable") . " passive mode in server " . $args['server'], 602, $e);
        }
        if (isset($args['path'])) {
            try {
                $this->chdir($args['path']);
            } catch (Exception $e) {
                $this->mkdirRecursive($args['path']);
                $this->chdir($args['path']);
            }
        }
    }

    public function close(): bool
    {
        ftp_close($this->ftp);
        unset($this->ftp);

        return true;
    }

    public function chdir(string $path): void
    {
        try {
            ftp_chdir($this->ftp, $path);
        } catch (Throwable $e) {
            throw new Exception("Unable to change dir '$path'", 600, $e);
        }
    }

    public function put(array $args = []): void
    {
        foreach (['filename', 'source_file', 'path'] as $v) {
            if (!array_key_exists($v, $args)) {
                throw new Exception("Missing $v value", 600);
            }
        }
        if (!array_key_exists('method', $args) || !in_array($args['method'], [FTP_BINARY, FTP_ASCII])) {
            $args['method'] = FTP_BINARY;
        }
        if (isset($args['path'])) {
            $this->chdir($args['path']);
        }

        try {
            ftp_put($this->ftp, $args['filename'], $args['source_file'], $args['method']);
        } catch (Throwable $e) {
            throw new Exception("Error uploading {$args['filename']} >>> " . $e->getMessage(), 601, $e);
        }
    }

    public function delete(string $file): void
    {
        try {
            $binary = ftp_raw($this->ftp, 'TYPE I'); // SIZE command works only in Binary
            $raw = ftp_raw($this->ftp, "SIZE $file")[0];
        } catch (Throwable $e) {
            throw new Exception("Error deleting file $file >>> " . $e->getMessage(), 601, $e);
        }
        preg_match('/^(\d+)\s+(.*)$/', $raw, $matches);
        $code = $matches[1];
        $return = $matches[2];
        if ($code > 500) { // SIZE is supported and the file doesn't exits
            return;
        }

        try {
            ftp_delete($this->ftp, $file);
        } catch (Throwable $e) {
            throw new Exception("Can't delete file '$file'", 600, $e);
        }
    }

    public function mkdirRecursive(string $path): void
    {
        $path = trim($path, '/');
        $cwd = ftp_pwd($this->ftp);
        if (!$cwd) {
            throw new Exception("Can't get current working directory for " . $path, 600);
        }
        $cwd .= '/';
        foreach (explode('/', $path) as $part) {
            $cwd .= $part . '/';
            if (empty($part)) {
                continue;
            }

            try {
                ftp_chdir($this->ftp, $cwd);
            } catch (Throwable $e) {
                try {
                    ftp_mkdir($this->ftp, $part);
                } catch (Throwable $e) {
                    throw new Exception("Can't make recursive dir for " . $part, 600, $e);
                }

                try {
                    ftp_chdir($this->ftp, $part);
                } catch (Throwable $e) {
                    throw new Exception("Unable to change fir to " . $part, 600, $e);
                }
            }
        }
    }
}
