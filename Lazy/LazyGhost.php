<?php

namespace Jorro\Injector\Lazy;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class LazyGhost implements LazyInterface
{
    public function __construct(protected(set) ?array $skipLazys = [], protected(set) ?array $rawValues = null)
    {
    }
}
