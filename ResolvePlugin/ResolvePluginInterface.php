<?php

namespace Jorro\Injector\ResolvePlugin;

interface ResolvePluginInterface
{
    public function apply(?\ReflectionParameter $injectCallerPoint, \ReflectionParameter $injectPoint, array &$injectArgs, array &$functionArgs): bool;
}