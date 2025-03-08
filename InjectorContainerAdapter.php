<?php

namespace Jorro\Injector;

use Psr\Container\ContainerInterface;
use Jorro\Injector\Lazy\LazyParameterInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class InjectorContainerAdapter implements InjectorContainerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    public function getInstance(string $class, LazyParameterInterface|bool $__lazy__ = false, ?\ReflectionParameter $__injectPoint__ = null, mixed ...$args): mixed
    {
        return $this->container->get($class);
    }
}