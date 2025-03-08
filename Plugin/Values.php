<?php

namespace Jorro\Injector\Plugin;

use Jorro\Injector\Definition\ParameterDefinition;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Values implements PluginInterface
{
    protected array $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }

    /**
     * @inheritDoc
     */
    public function apply(ParameterDefinition $injectPoint, array &$injectArgs, array &$functionArgs, mixed $injectOptions): bool
    {
        foreach ($this->args as $index => $value) {
            $injectArgs[$index] = $value;
        }
        return true;
    }
}