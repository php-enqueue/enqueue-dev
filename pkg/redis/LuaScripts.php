<?php

declare(strict_types=1);

namespace Enqueue\Redis;

class LuaScripts
{
    /**
     * Get the Lua script to migrate expired messages back onto the queue.
     *
     * KEYS[1] - The queue we are removing messages from, for example: queues:foo:reserved
     * KEYS[2] - The queue we are moving messages to, for example: queues:foo
     * ARGV[1] - The current UNIX timestamp
     *
     * @return string
     */
    public static function migrateExpired()
    {
        return <<<'LUA'
-- Get all of the messages with an expired "score"...
local val = redis.call('zrangebyscore', KEYS[1], '-inf', ARGV[1])

-- If we have values in the array, we will remove them from the first queue
-- and add them onto the destination queue in chunks of 100, which moves
-- all of the appropriate messages onto the destination queue very safely.
if(next(val) ~= nil) then
    redis.call('zremrangebyrank', KEYS[1], 0, #val - 1)

    for i = 1, #val, 100 do
        redis.call('lpush', KEYS[2], unpack(val, i, math.min(i+99, #val)))
    end
end

return val
LUA;
    }
}
