<?php

namespace Jorro\Injector;

use Psr\Container\ContainerInterface;

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
    public function setContainer(ContainerInterface|InjectorInterface $container): void;

    /**
     * create LazyGhost instance
     *
     * @param  string      $class
     * @param  array|null  $skipLazys
     * @param  array|null  $rawValues
     * @param  mixed       ...$args
     *
     * @return mixed
     * @throws InjectorNotFoundException
     */
    public function getLazyGhost(string $class, ?\ReflectionParameter $__injectPoint__ = null, ?array $skipLazys = null, ?array $rawValues = null, mixed ...$args): mixed;

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
    public function getLazyProxy(string $class, ?\ReflectionParameter $__injectPoint__ = null, ?array $skipLazys = null, ?array $rawValues = null, mixed ...$args): mixed;

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