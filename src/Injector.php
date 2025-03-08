<?php

namespace Jorro\Injector;

use Jorro\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class Injector implements InjectorInterface
{
    /**
     * @param  ContainerInterface|null  $container  Container
     */
    public function __construct(protected ?ContainerInterface $container = null)
    {
        $this->container ??= $this;
    }

    /**
     * @param  ContainerInterface  $container
     *
     * @return void
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * Create instance
     *
     * @param  string  $class    Class name
     * @param  mixed   ...$args  Aguments
     */
    public function get(string $class, mixed ...$args): mixed
    {
        if (!class_exists($class)) {
            throw new InjectorNotFoundException("class not found : $class");
        }
        if (($args) || (method_exists($class, '__construct'))) {
            $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args);
            return new $class(... $args);
        } else {
            return new $class();
        }
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        return class_exists($id);
    }

    /**
     * @param  string|\Closure  $function  Function name
     * @param  mixed            ...$args   Aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure|\ReflectionFunction $function, mixed ...$args)
    {
        if (!is_object($function)) {
            $function = new \ReflectionFunction($function);
        }
        $this->injectFunctionArgs($function, $args);
        return $function->invokeArgs($args);
    }

    /**
     * @param  object|string  $instance  Instance or class name
     * @param  string         $method    Method name
     * @param  mixed          ...$args   Aguments
     *
     * @return mixed Return value of method
     */
    public function invokeMethod(object|string $instance, string|\ReflectionMethod $method, mixed ...$args): mixed
    {
        if (!is_object($method)) {
            $method = new \ReflectionMethod($instance, $method);
        }
        if ($method->isStatic()) {
            $this->injectFunctionArgs($method, $args);
            return $reflection->invokeArgs(null, $args);
        } else {
            if (!is_object($instance)) {
                $instance = $this->container->get($instance);
            }
            $this->injectFunctionArgs($method, $args);
            return $method->invokeArgs($instance, $this->injectFunctionArgs($instance::class, $method, $args));
        }
    }

    public function injectFunctionArgs($function, &$args)
    {
        foreach ($function->getParameters() as $index => $parameter) {
            if (key_exists($index, $args) || key_exists($parameter->getName(), $args)) {
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $args[$index] = $parameter->getDefaultValue();
                continue;
            }
            if (($types = $parameter->getType()) instanceof \ReflectionNamedType) {
                if (!$types->isBuiltin()) {
                    $args[$index] = $this->container->get($types->getName());
                    continue;
                }
            } elseif ($types) {
                foreach ($types->getTypes() as $type) {
                    if (!$type->isBuiltin()) {
                        try {
                            $args[$index] = $this->container->get($type->getName());
                            continue 2;
                        } catch (NotFoundExceptionInterface $e) {
                        }
                    }
                }
                throw new InjectorNotFoundException('class not found : ' . (string)$types);
            }
        }
    }

}
