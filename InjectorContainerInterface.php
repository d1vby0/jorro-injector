<?php

namespace Jorro\Injector;

use Jorro\Injector\Lazy\LazyParameterInterface;
use Psr\Container\NotFoundExceptionInterface;

interface InjectorContainerInterface
{
    /**
     * @param  string                       $class
     * @param  LazyParameterInterface|bool  $lazy
     * @param  \ReflectionParameter|null    $injectPoint
     * @param  mixed                        ...$args
     *
     * @return mixed
     * @throws InjectorNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    public function getInstance(string $class, array $args = [], LazyParameterInterface|bool $lazy = false, ?\ReflectionParameter $injectPoint = null): mixed;
}