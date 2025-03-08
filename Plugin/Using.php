<?php

namespace Jorro\Injector\Plugin;

use Jorro\Injector\Definition\ParameterDefinition;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Using implements PluginInterface
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
            if (!key_exists($value, $functionArgs)) {
                return false;
            }
            $injectArgs[$index] = $functionArgs[$value];
        }
        return true;
    }
}