<?php

namespace Jorro\Injector;

use Jorro\Injector\Lazy\LazyParameterInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
interface InjectorContainerInterface
{
    /**
     * @param  string                       $class
     * @param  LazyParameterInterface|bool  $__lazy__
     * @param  \ReflectionParameter|null    $__injectPoint__
     * @param  mixed                        ...$args
     *
     * @return mixed
     */
    public function getInstance(string $class, LazyParameterInterface|bool $__lazy__ = false, ?\ReflectionParameter $__injectPoint__ = null, mixed ...$args): mixed;

}