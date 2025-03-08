<?php

namespace Jorro\Injector\Resolve;

interface ResolvePluginInterface
{
    public function apply(\ReflectionFunctionAbstract $function, \ReflectionParameter $parameter, array &$injectArgs, array &$functionArgs): bool;

    public function needDefer(): bool;
}