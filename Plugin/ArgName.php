<?php

namespace Jorro\Injector\Plugin;

use Jorro\Injector\Definition\ParameterDefinition;

#[\Attribute(\Attribute::TARGET_PARAMETER|\Attribute::TARGET_PROPERTY)]
class ArgName implements PluginInterface
{
    public function apply(ParameterDefinition $injectPoint, array &$injectArgs, array &$functionArgs, mixed $injectOptions): bool
    {
        $reflection = $injectPoint->getReflection();
        $argName = ($injectOptions instanceof ParameterDefinition) ? $injectOptions->getReflection()->name : null;
        $functionArgs[$reflection->name] = $argName;

        return true;
    }
}