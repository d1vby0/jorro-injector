<?php

namespace Jorro\Injector\Resolve;

use Jorro\Injector\ResolvePlugin\ResolvePluginInterface;

interface ResolveInterface
{
    public ?string $id {
        get;
    }

    public bool $optional {
        get;
    }

    public function hasPlugins(): bool;

    /**
     * @return bool
     */

    public function addPlugin(ResolvePluginInterface $plugins): void;

    /**
     * @param  \ReflectionParameter         $parameter
     * @param  array                        $injectArgs    A reference to the arguments used to generate this parameters value.
     * @param  array                        $functionArgs  A reference to the argument of the function that is injecting this parameter.
     *
     * @return bool  is success
     */
    public function applyPlugins(mixed $injectFrom, \ReflectionParameter $parameter, array &$injectArgs, array &$functionArgs): bool;
}
