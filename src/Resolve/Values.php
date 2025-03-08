<?php

namespace Jorro\Injector\Resolve;

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
    public function apply(\ReflectionFunctionAbstract $function, \ReflectionParameter $parameter, array &$injectArgs, array &$functionArgs): bool
    {
        foreach ($this->args as $index => $value) {
            $injectArgs[$index] = $value;
        }
        return true;
    }
}