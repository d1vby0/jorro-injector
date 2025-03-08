<?php

namespace Jorro\Injector\ResolvePlugin;

interface ResolvePluginInterface
{
    public function apply(\ReflectionFunctionAbstract $function, \ReflectionParameter $parameter, array &$injectArgs, array &$functionArgs): bool;
}