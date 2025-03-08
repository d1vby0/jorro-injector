<?php

namespace Jorro\Injector\Lazy;

use Jorro\Injector\ResolvePlugin\ResolvePluginInterface;

interface LazyParameterInterface
{
    public ?array $skipLazys {
        get;
    }
    public ?array $rawValues {
        get;
    }
}
