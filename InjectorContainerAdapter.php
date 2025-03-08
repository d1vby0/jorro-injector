<?php

namespace Jorro\Injector;

use Psr\Container\ContainerInterface;

class InjectorContainerAdapter implements InjectorContainerInterface
{
    public function __construct(protected ContainerInterface $container, protected InjectorContainerInterface $injector)
    {
    }

    /**
     * @inheritDoc
     */
    public function getInstance(string $class, array $args = [], mixed $injectOptions = null): mixed
    {
        if ($this->container->has($class)) {
            return $this->container->get($class);
        } else {
            return $this->injector->getInstance($class, $args, $injectOptions);
        }
    }
}