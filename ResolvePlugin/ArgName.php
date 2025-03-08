<?php

namespace Jorro\Injector\ResolvePlugin;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ArgName implements ResolvePluginInterface
{
    public function apply(?\ReflectionParameter $injectCallerPoint, \ReflectionParameter $injectPoint, array &$injectArgs, array &$functionArgs): bool
    {
        $functionArgs[$injectPoint->name] = ($injectCallerPoint) ? $injectCallerPoint->name : null;
        return true;
    }
}