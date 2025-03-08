<?php

namespace Jorro\Injector;

use Jorro\Injector\Lazy\LazyParameterInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
interface InjectorInterface extends ContainerInterface, InjectorContainerInterface
{
    /**
     * @param  ContainerInterface  $container
     *
     * @return void
     */
    public function setContainer(ContainerInterface|InjectorContainerInterface $container): void;

    /**
     * create LazyGhost instance
     *
     * @param  string                     $class
     * @param  array                      $args
     * @param  array|null                 $skipLazys
     * @param  array|null                 $rawValues
     * @param  \ReflectionParameter|null  $injectPoint
     *
     * @return mixed
     * @throws \ReflectionException
     */
    public function getLazyGhost(string $class, array $args = [], ?array $skipLazys = null, ?array $rawValues = null, ?\ReflectionParameter $injectPoint = null): mixed;

    /**
     * create LazyProxy instance
     *
     * @param  string      $class
     * @param  array|null  $skipLazys
     * @param  array|null  $rawValues
     * @param  mixed       ...$args
     *
     * @return mixed
     * @throws InjectorNotFoundException
     */
    public function getLazyProxy(string $class, array $args = [], ?array $skipLazys = null, ?array $rawValues = null, ?\ReflectionParameter $injectPoint = null): mixed;

    /**
     * @param  string|\Closure  $function  Function name
     * @param  mixed            ...$args   Aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure|\ReflectionFunction $function, mixed ...$args): mixed;

    /**
     * @param  object|string  $instance  Instance or class name
     * @param  string         $method    Method name
     * @param  mixed          ...$args   Aguments
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string|\ReflectionMethod $method, mixed ...$args): mixed;
}