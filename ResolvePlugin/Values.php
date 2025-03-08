<?php

namespace Jorro\Injector\ResolvePlugin;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Values implements ResolvePluginInterface
{
    protected array $args;

    public function __construct(...$args)
    {
        $this->args = $args;
    }

    /**
     * @inheritDoc
     */
    public function apply(?\ReflectionParameter $injectCallerPoint, \ReflectionParameter $injectPoint, array &$injectArgs, array &$functionArgs): bool
    {
        foreach ($this->args as $index => $value) {
            $injectArgs[$index] = $value;
        }
        return true;
    }
}