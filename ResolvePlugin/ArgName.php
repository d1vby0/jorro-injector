<?php

namespace Jorro\Injector\ResolvePlugin;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class ArgName implements ResolvePluginInterface
{
    public function apply(mixed $injectFrom, \ReflectionParameter $parameter, array &$injectArgs, array &$functionArgs): bool
    {
        if ($injectFrom instanceof \ReflectionParameter) {
            $functionArgs[$parameter->name] = $injectFrom->name;
        }
        return true;
    }
}