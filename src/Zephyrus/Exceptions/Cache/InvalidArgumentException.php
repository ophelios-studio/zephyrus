<?php namespace Zephyrus\Exceptions\Cache;

class InvalidArgumentException
    extends CacheException
    implements \Psr\SimpleCache\InvalidArgumentException
{

}
