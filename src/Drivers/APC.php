<?php
/**
 * Copyright (c) 2017 Josh P (joshp.xyz).
 */

namespace J0sh0nat0r\SimpleCache\Drivers;

use J0sh0nat0r\SimpleCache\Exceptions\DriverOptionsInvalidException;
use J0sh0nat0r\SimpleCache\IDriver;

/**
 * APC Driver.
 *
 * Accepted options: None
 */
class APC implements IDriver
{
    /**
     * @var string APC prefix (apcu_ or apc_)
     */
    private $prefix;

    public function __construct()
    {
        if (extension_loaded('apcu')) {
            $this->prefix = 'apcu_';
        } elseif (extension_loaded('apc')) {
            $this->prefix = 'apc_';
        } else {
            throw new DriverOptionsInvalidException('This driver requires APC or APCu');
        }
    }

    public function set($key, $value, $time)
    {
        $function = $this->prefix.'store';

        return $function($key, $value, $time);
    }

    public function has($key)
    {
        $function = $this->prefix.'exists';

        return $function($key);
    }

    public function get($key)
    {
        $function = $this->prefix.'fetch';

        $success = false;
        $result = $function($key, $success);

        if (!$success) {
            return null;
        }

        return $result;
    }

    public function remove($key)
    {
        $function = $this->prefix.'delete';

        return $function($key);
    }

    public function clear()
    {
        if ($this->prefix === 'apc_') {
            return apc_clear_cache('user');
        }

        return apcu_clear_cache();
    }
}
