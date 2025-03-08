<?php

namespace Jorro\Injector\ResolvePlugin;

#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Using implements ResolvePluginInterface
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
            if (!key_exists($value, $functionArgs)) {
                return false;
            }
            $injectArgs[$index] = $functionArgs[$value];
        }
        return true;
    }
}