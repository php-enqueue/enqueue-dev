<?php

declare(strict_types=1);

namespace Enqueue\Redis;

class LuaScripts
{
    /**
     * KEYS[1] - The queue we are reading message
     * KEYS[2] - The reserved queue we are moving message to
     * ARGV[1] - Now timestamp
     * ARGV[2] - Redelivery at timestamp
     *
     * @return string
     */
    public static function receiveMessage(): string
    {
        return <<<'LUA'
local message = redis.call('RPOP', KEYS[1])

if (not message) then
  return nil
end

local jsonSuccess, json = pcall(cjson.decode, message);

if (not jsonSuccess) then
  return nil
end

if (nil == json['headers']['attempts']) then
  json['headers']['attempts'] = 0
end

if (0 == json['headers']['attempts'] and nil ~= json['headers']['expires_at']) then
  if (tonumber(ARGV[1]) > json['headers']['expires_at']) then
    return nil
  end
end

json['headers']['attempts'] = json['headers']['attempts'] + 1

message = cjson.encode(json)

redis.call('ZADD', KEYS[2], tonumber(ARGV[2]), message)

return message
LUA;
    }

    /**
     * Get the Lua script to migrate expired messages back onto the queue.
     *
     * KEYS[1] - The queue we are removing messages from, for example: queues:foo:reserved
     * KEYS[2] - The queue we are moving messages to, for example: queues:foo
     * ARGV[1] - The current UNIX timestamp
     *
     * @return string
     */
    public static function migrateExpired(): string
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
