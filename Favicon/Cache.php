<?php
/**
 * A simple cache class
 *
 * User: jerry
 * Date: 2015/8/15
 * Time: 23:34
 */

namespace WS;


class Cache {

    private $redis_conf = array(
        'host'      => '127.0.0.1',
        'port'      => 6379,
        'db'        => 7
    );

    private $_redis;

    private static $_instance = FALSE;

    private function __construct(){

        /**
         * connect to Redis
         */
        if ( ! extension_loaded('redis')){
            throw new WS_Exception('\WS\Cache: The redis Extension must be loaded to use Redis Cache.');
        }

        $this->_redis = new Redis();
        $this->_redis->connect($this->redis_conf['host'], $this->redis_conf['port']);

        $this->_redis->select($this->redis_conf['db']);
    }


    public static function &get_instance(){
        if(! self::$_instance){
            self::$_instance = new Cache();
        }

        return self::$_instance;
    }

    /**
     * get the value from redis
     * @param $key
     * @return mixed
     */
    public function get($key){
        return $this->_redis->get($key);
    }

    /**
     * save data into redis
     * @param string $key
     * @param string $value
     * @param int $ttl cache expire time, 0 means forever
     * @return mixed
     */
    public function save($key, $value, $ttl = 0){
        if($ttl != 0){
            return $this->_redis->setex($key, $ttl, $value);
        } else {
            return $this->_redis->set($key, $value);
        }
    }

    /**
     * delete a cache key
     * @param $key
     * @return mixed
     */
    public function delete($key){
        return $this->_redis->delete($key);
    }
}