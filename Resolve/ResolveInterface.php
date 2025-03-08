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

    /**
     * @return bool
     */
    public function hasPlugins(): bool;

    /**
     * @param  ResolvePluginInterface  $plugins
     *
     * @return void
     */
    public function addPlugin(ResolvePluginInterface $plugins): void;

    /**
     * @param  \ReflectionParameter|null  $injectCallerPoint
     * @param  \ReflectionParameter       $injectPoint
     * @param  array                      $injectArgs
     * @param  array                      $functionArgs
     *
     * @return bool
     */
    public function applyPlugins(?\ReflectionParameter $injectCallerPoint, \ReflectionParameter $injectPoint, array &$injectArgs, array &$functionArgs): bool;
}
