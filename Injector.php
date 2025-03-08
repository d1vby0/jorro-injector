<?php

namespace Jorro\Injector;

use Jorro\Injector\Lazy\LazyGhost;
use Jorro\Injector\Lazy\LazyParameterInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class Injector implements InjectorInterface
{
    protected InjectorInterface $container;

    /**
     * @param  ContainerInterface|null  $container  Container
     */
    public function __construct(ContainerInterface|InjectorContainerInterface|null $container = null)
    {
        $this->setContainer($container ?? $this);
    }

    /**
     * @param  ContainerInterface  $container
     *
     * @return void
     */
    public function setContainer(ContainerInterface|InjectorContainerInterface $container): void
    {
        if ($container instanceof InjectorContainerInterface) {
            $this->container = $container;
        } else {
            $this->container = new InjectorContainerAdapter($container);
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
     * @inheritDoc
     */
    public function get(string $id, mixed ...$args): mixed
    {
        return $this->getInstance($id, false, null, ...$args);
    }

    /**
     * @param  string                       $class
     * @param  LazyParameterInterface|bool  $__lazy__
     * @param  \ReflectionParameter|null    $__injectPoint__
     * @param  mixed                        ...$args
     *
     * @return mixed
     * @throws InjectorNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \ReflectionException
     */
    public function getInstance(string $class, LazyParameterInterface|bool $__lazy__ = false, ?\ReflectionParameter $__injectPoint__ = null, mixed ...$args): mixed
    {
        if (!class_exists($class)) {
            throw new InjectorNotFoundException("class not found : $class");
        }
        if ($__lazy__) {
            return $this->getLazyGhost($class, $__injectPoint__);
            if ($__lazy__ === true) {
            } elseif ($__lazy__ instanceof LazyGhost) {
                return $this->getLazyGhost($class, $__injectPoint__);
            } elseif ($__lazy__ instanceof LazyProxy) {
                return $this->getLazyProxy($class, $__injectPoint__);
            }
        }
        if (($args) || (method_exists($class, '__construct'))) {
            $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args, $__injectPoint__);
            return new $class(... $args);
        } else {
            return new $class();
        }
    }

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
    public function getLazyGhost(string $class, ?\ReflectionParameter $__injectPoint__ = null, ?array $skipLazys = null, ?array $rawValues = null, mixed ...$args): mixed
    {
        return new \ReflectionClass($class)->newLazyGhost(function ($object) use ($class, $args, $__injectPoint__) {
            if (($args) || (method_exists($class, '__construct'))) {
                $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args, $__injectPoint__);
                $object->__construct(... $args);
            }
        });
    }

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
    public function getLazyProxy(string $class, ?\ReflectionParameter $__injectPoint__ = null, ?array $skipLazys = null, ?array $rawValues = null, mixed ...$args): mixed
    {
        return new \ReflectionClass($class)->newLazyProxy(function ($object) use ($class, $args, $__injectPoint__) {
            if (($args) || (method_exists($class, '__construct'))) {
                $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args, $__injectPoint__);
                return new $class(... $args);
            } else {
                return new $class();
            }
        });
    }

    /**
     * @param  string|\Closure  $function  Function name
     * @param  mixed            ...$args   Aguments
     *
     * @return mixed Return value of function
     */
    public function invokeFunction(string|\Closure|\ReflectionFunction $function, mixed ...$args): mixed
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
                $instance = $this->container->getInstance($instance);
            }
            $this->injectFunctionArgs($method, $args);
            return $method->invokeArgs($instance, $this->injectFunctionArgs($instance::class, $method, $args));
        }
    }

    /**
     * Inject to function argmunets
     *
     * @param  \ReflectionFunctionAbstract  $function
     * @param  array                        $args
     *
     * @return void
     * @throws InjectorNotFoundException
     * @throws NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    protected function injectFunctionArgs(\ReflectionFunctionAbstract $function, array &$args, mixed $injectFrom = null): void
    {
        foreach ($function->getParameters() as $index => $parameter) {
            if (key_exists($index, $args) || key_exists($parameter->name, $args)) {
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                $args[$index] = $parameter->getDefaultValue();
                continue;
            }
            if (($types = $parameter->getType()) instanceof \ReflectionNamedType) {
                if (!$types->isBuiltin()) {
                    $args[$index] = $this->container->getInstance($types->getName(), false, $parameter);
                    continue;
                }
            } elseif ($types) {
                foreach ($types->getTypes() as $type) {
                    if (!$type->isBuiltin()) {
                        try {
                            $args[$index] = $this->container->getInstance($type->getName(), false, $parameter);
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
