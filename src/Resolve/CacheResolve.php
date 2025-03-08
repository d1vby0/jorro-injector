<?php

namespace Jorro\Injector\Resolve;

#[\Attribute(\Attribute::TARGET_FUNCTION|\Attribute::TARGET_METHOD)]
class CacheResolve
{
    public function __construct(protected(set) bool $cache = true)
    {
    }
}
