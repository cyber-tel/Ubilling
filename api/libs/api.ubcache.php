<?php

class UbillingCache {

    /**
     * System alter.ini config content
     *
     * @var array
     */
    protected $altCfg = array();

    /**
     * Cache storage type: files, memcached, fake
     * via UBCACHE_STORAGE option
     *
     * @var string
     */
    protected $storage = '';

    /**
     * Memcached server IP/Hostname
     * via MEMCACHED_SERVER option
     * 
     * @var string
     */
    protected $memcachedServer = '';

    /**
     * Memcached server port
     * via MEMCACHED_PORT option
     *
     * @var int
     */
    protected $memcachedPort = '';

    /**
     * File storage path: "exports/" by default
     *
     * @var string
     */
    protected $storagePath = '';

    /**
     * Single instance of memcached object
     *
     * @var object
     */
    protected $memcached = ''; // single memcached object

    const CACHE_PREFIX = 'UBCACHE_';

    public function __construct() {
        $this->loadAlter();
        $this->setOptions();
        $this->initMemcached();
    }

    /**
     * Loads global alter config into protected property
     * 
     * @global object $ubillingConfig
     * 
     * @return void
     */
    protected function loadAlter() {
        global $ubillingConfig;
        $this->altCfg = $ubillingConfig->getAlter();
    }

    /**
     * Sets object storage mode
     * 
     * @return void
     */
    protected function setOptions() {
        if (isset($this->altCfg['UBCACHE_STORAGE'])) {
            $this->storage = $this->altCfg['UBCACHE_STORAGE'];
        } else {
            $this->storage = 'fake';
        }

        if ($this->storage == 'memcached') {
            if (isset($this->altCfg['MEMCACHED_SERVER'])) {
                $this->memcachedServer = $this->altCfg['MEMCACHED_SERVER'];
            } else {
                $this->memcachedServer = 'localhost';
            }
            if (isset($this->altCfg['MEMCACHED_PORT'])) {
                $this->memcachedPort = $this->altCfg['MEMCACHED_PORT'];
            } else {
                $this->memcachedPort = 11211;
            }
        }

        if ($this->storage == 'files') {
            $this->storagePath = 'exports/';
        }
    }

    /**
     * Inits memcached server if it needed
     * 
     * @return void
     */
    protected function initMemcached() {
        if ($this->storage == 'memcached') {
            $this->memcached = new Memcached();
            $this->memcached->addServer($this->memcachedServer, $this->memcachedPort);
        }
    }

    /**
     * Generates key storable internal name
     * 
     * @param string $key
     * 
     * @return string
     */
    protected function genKey($key) {
        $result = self::CACHE_PREFIX . md5($key);
        return ($result);
    }

    /* If you think it's too loud
      Bitch get the fuck out
      If you wanna slow down
      Bitch get the fuck out
      If your ass ain't with me
      Bitch get the fuck out
      What get the fuck out
      Bitch get the fuck out */

    /**
     * Puts data into cache storage
     * 
     * @param string $key
     * @param mixed $data
     * @param int $expiration
     * 
     * @return void
     */
    protected function set($key, $data, $expiration = 0) {
        $key = $this->genKey($key);
        if ($this->storage == 'files') {
            file_put_contents($this->storagePath . $key, $data);
        }

        if ($this->storage == 'memcached') {
            $this->memcached->set($key, $data, $expiration);
        }
    }

    /**
     * Returns data from cache by key or runs callback and fills new cache data
     * 
     * @param string $key  Storage key
     * @param Closure $callback Callback function 
     * @param int $expiration Expiration time in seconds
     * 
     * @return string
     */
    public function getCallback($key, Closure $callback, $expiration = 0) {
        $keyRaw = $key;
        $key = $this->genKey($key);

        //files storage
        if ($this->storage == 'files') {
            $cacheName = $this->storagePath . $key;
            if (!$expiration) {
                $expiration = 2629743; // month by default
            }
            $cacheTime = time() - $expiration;
            $updateCache = false;
            if (file_exists($cacheName)) {
                $updateCache = false;
                if ((filemtime($cacheName) > $cacheTime)) {
                    $updateCache = false;
                } else {
                    $updateCache = true;
                }
            } else {
                $updateCache = true;
            }

            if (!$updateCache) {
                //read data directly from cache
                $result = file_get_contents($cacheName);
            } else {
                //run callback function and store new data into cache
                $result = $callback();
                $this->set($keyRaw, $result, $expiration);
            }
            return ($result);
        }

        //memcached storage
        if ($this->storage == 'memcached') {
            $result = $this->memcached->get($key);
            if (!$result) {
                $result = $callback();
                $this->set($keyRaw, $result, $expiration);
            }
            return ($result);
        }
        //fake storage
        if ($this->storage == 'fake') {
            $result = $callback();
            return ($result);
        }
    }

    /**
     * Deletes data from cache by key name
     * 
     * @param string $key
     * 
     * @return void
     */
    public function delete($key) {
        $key = $this->genKey($key);
        if ($this->storage == 'files') {
            if (file_exists($this->storagePath . $key)) {
                unlink($this->storagePath . $key);
            }
        }

        if ($this->storage == 'memcached') {
            $this->memcached->delete($key);
        }
    }

}

?>