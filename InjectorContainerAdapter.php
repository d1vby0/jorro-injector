<?php

namespace Jorro\Injector;

use Jorro\Injector\Lazy\LazyParameterInterface;
use Psr\Container\ContainerInterface;

class InjectorContainerAdapter implements InjectorContainerInterface
{
    public function __construct(protected ContainerInterface $container)
    {
    }

    /**
     * @inheritDoc
     */
    public function getInstance(string $class, array $args = [], LazyParameterInterface|bool $lazy = false, ?\ReflectionParameter $injectPoint = null): mixed
    {
        return $this->container->get($class);
    }
}