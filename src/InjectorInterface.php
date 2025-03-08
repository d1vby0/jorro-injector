<?php

namespace Jorro\Injector;

use Psr\Container\ContainerInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
interface InjectorInterface extends ContainerInterface
{
    public function setContainer(ContainerInterface $container): void;

    /**
     * Create instance
     *
     * @param  string  $class    Class name
     * @param  mixed   ...$args  Aguments
     */
    public function get(string $class, mixed ...$args): mixed;

    /**
     * @inheritDoc
     */
    public function has(string $id): bool;

    /**
     * @param  string|\Closure  $function  Function name
     * @param  mixed            ...$args   Aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure|\ReflectionFunction $function, mixed ...$args);

    /**
     * @param  object|string  $instance  Instance or class name
     * @param  string         $method    Method name
     * @param  mixed          ...$args   Aguments
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string|\ReflectionMethod $method, mixed ...$args): mixed;
}