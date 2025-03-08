<?php

namespace Jorro\Injector\ResolvePlugin;

interface ResolvePluginInterface
{
    public function apply(mixed $injectFrom, \ReflectionParameter $injectPoint, array &$injectArgs, array &$functionArgs): bool;
}