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
use phpseclib3\Net\SFTP as NetSFTP;

class Sftp
{
    private $sftp;

    public function __construct(array $args = [])
    {
        foreach (['server', 'user', 'password'] as $v) {
            if (!array_key_exists($v, $args)) {
                throw new Exception("Missing $v value", 601);
            }
        }
        $parsed_server = parse_url($args['server']);
        $host = $parsed_server['host'] ?? $args['server'];
        $port = $parsed_server['port'] ?? 22;
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) === false && filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            $hbn = gethostbyname($host);
            if ($hbn == $host) {
                throw new Exception("Can't resolve host for $host", 602);
            } else {
                $host = $hbn;
            }
        }
        $this->sftp = new NetSFTP($host, $port);
        if (!$this->sftp->login($args['user'], $args['password'])) {
            $errors = implode('; ', $this->sftp->getSFTPErrors());

            throw new Exception("Can't SFTP login to " . $args['server'] . " server " . $errors, 603);
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
        $this->sftp->exec('exit');
        unset($this->sftp);

        return true;
    }

    public function chdir(string $path): void
    {
        if (!$this->sftp->chdir($path)) {
            throw new Exception("Can't change dir '$path'", 300);
        }
    }

    public function put(array $args = []): void
    {
        foreach (['filename', 'source_file', 'path'] as $v) {
            if (!array_key_exists($v, $args)) {
                throw new Exception("Missing $v value", 600);
            }
        }
        if (array_key_exists('path', $args) && !$this->sftp->chdir($args['path'])) {
            throw new Exception("Can't change dir '" . $args['path'] . "'", 610);
        }
        if (!$this->sftp->put($args['filename'], $args['source_file'], 1)) { // 1 for local file, 2 for string
            throw new Exception("Can't upload '" . $args['filename'] . "' to '" . $args['path'] . "'", 620);
        }
    }

    public function delete(string $file): bool
    {
        if (!$this->sftp->stat($file[0])) {
            return true;
        }
        if (!$this->sftp->delete($file)) {
            throw new Exception("Can't delete file '$file'");
        }

        return true;
    }

    public function deleteMultiple(array $files = [])
    {
        if (count($files) == 0) {
            throw new Exception("Missing or invalid array argument");
        }
        foreach ($files as $file) {
            $this->sftp->delete($file, false);
        }

        return $files;
    }

    public function mkdirRecursive(string $path): bool
    {
        return $this->sftp->mkdir($path, -1, true);
    }
}
