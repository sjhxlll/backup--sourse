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

use function Chevereto\Vars\session;
use function Chevereto\Vars\sessionVar;
use Hybridauth\Exception\RuntimeException;
use Hybridauth\Storage\Session;

/**
 * Hybridauth storage manager (Chevereto edition)
 */
class HybridauthSession extends Session
{
    /**
     * Namespace
     *
     * @var string
     */
    protected $storeNamespace = 'HYBRIDAUTH::STORAGE';

    /**
     * Key prefix
     *
     * @var string
     */
    protected $keyPrefix = '';

    /**
     * Initiate a new session
     *
     * @throws RuntimeException
     */
    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function get($key)
    {
        $key = $this->keyPrefix . strtolower($key);
        if (isset(session()[$this->storeNamespace], session()[$this->storeNamespace][$key])) {
            $value = session()[$this->storeNamespace][$key];

            if (is_array($value) && array_key_exists('lateObject', $value)) {
                $value = unserialize($value['lateObject']);
            }

            return $value;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @param string|object $value
     */
    public function set($key, $value)
    {
        $key = $this->keyPrefix . strtolower($key);
        if (is_object($value)) {
            $value = ['lateObject' => serialize($value)];
        }
        $session = session()[$this->storeNamespace] ?? [];
        $session[$key] = $value;
        sessionVar()->put($this->storeNamespace, $session);
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        sessionVar()->put($this->storeNamespace, []);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($key)
    {
        $key = $this->keyPrefix . strtolower($key);
        if (isset(session()[$this->storeNamespace], session()[$this->storeNamespace][$key])) {
            $tmp = session()[$this->storeNamespace];
            unset($tmp[$key]);
            sessionVar()->put($this->storeNamespace, $tmp);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMatch($key)
    {
        $key = $this->keyPrefix . strtolower($key);
        if (isset(session()[$this->storeNamespace]) && count(session()[$this->storeNamespace])) {
            $tmp = session()[$this->storeNamespace];
            foreach ($tmp as $k => $v) {
                if (strstr($k, $key)) {
                    unset($tmp[$k]);
                }
            }
            sessionVar()->put($this->storeNamespace, $tmp);
        }
    }
}
