<?php

namespace Jorro\Injector\ResolvePlugin;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class PassName implements ResolvePluginInterface
{
    public function __construct(protected ?string $name = null)
    {
    }

    /**
     * @inheritDoc
     */
    public function apply(\ReflectionFunctionAbstract $function, \ReflectionParameter $parameter, array &$injectArgs, array &$functionArgs): bool
    {
        $injectArgs[$this->name ?? 0] = $parameter->name;

        return true;
    }
}