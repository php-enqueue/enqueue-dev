<?php

namespace Enqueue\Redis;

interface Redis
{
    /**
     * @param string $key
     * @param string $value
     *
     * @return int length of the list
     */
    public function lpush($key, $value);

    /**
     * @param string $key
     * @param int    $timeout in seconds
     *
     * @return string|null
     */
    public function brpop($key, $timeout);

    /**
     * @param string $key
     *
     * @return string|null
     */
    public function rpop($key);

    public function connect();

    public function disconnect();

    /**
     * @param string $key
     */
    public function del($key);

    public function zrangebyscore($key, $min, $max, $offset, $limit, $options = []);

    public function zadd($key, $score, $member);

    public function rpush($key, $value);

    public function lpop($key);

    public function llen($key);

    public function zrem($key, $member);
}
