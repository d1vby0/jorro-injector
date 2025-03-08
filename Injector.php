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
    protected InjectorContainerInterface $container;

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
        if (!class_exists($id)) {
            throw new InjectorNotFoundException("class not found : $id");
        }
        if (($args) || (method_exists($id, '__construct'))) {
            $this->injectFunctionArgs(new \ReflectionMethod($id, '__construct'), $args, null);
            return new $id(... $args);
        } else {
            return new $id();
        }
    }

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
    public function getInstance(string $class, array $args = [], LazyParameterInterface|bool $lazy = false, ?\ReflectionParameter $injectPoint = null): mixed
    {
        if (!class_exists($class)) {
            throw new InjectorNotFoundException("class not found : $class");
        }
        if ($lazy) {
            return $this->getLazyGhost($class, $injectPoint);
            if ($lazy === true) {
            } elseif ($lazy instanceof LazyGhost) {
                return $this->getLazyGhost($class, $args, $lazy->skipLazys, $lazy->rawValues, $injectPoint);
            } elseif ($lazy instanceof LazyProxy) {
                return $this->getLazyProxy($class, $args, $lazy->skipLazys, $lazy->rawValues, $injectPoint);
            }
        }
        if (($args) || (method_exists($class, '__construct'))) {
            $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args, $injectPoint);
            return new $class(... $args);
        } else {
            return new $class();
        }
    }

    /**
     * @param  \ReflectionClass  $reflection
     * @param  object            $instance
     * @param  array|null        $skipLazys
     * @param  array|null        $rawValues
     *
     * @return mixed
     * @throws \ReflectionException
     */
    protected function initializeLazyParameters(\ReflectionClass $reflection, object $instance, ?array $skipLazys = null, ?array $rawValues = null): mixed
    {
        if ($skipLazys) {
            foreach ($skipLazys as $name => $skipLazy) {
                $reflection->getProperty($name)->skipLazyInitialization($instance);
            }
        }
        if ($rawValues) {
            foreach ($rawValues as $name => $rawValue) {
                $reflection->getProperty($name)->setRawValueWithoutLazyInitialization($instance, $rawValue);
            }
        }
    }

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
    public function getLazyGhost(string $class, array $args = [], ?array $skipLazys = null, ?array $rawValues = null, ?\ReflectionParameter $injectPoint = null): mixed
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newLazyGhost(function ($object) use ($class, $args, $injectPoint) {
            if (($args) || (method_exists($class, '__construct'))) {
                $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args, $injectPoint);
                $object->__construct(... $args);
            }
        });
        if ($skipLazys || $rawValues) {
            $this->initializeLazyParameters($reflection, $instance, $skipLazys, $rawValues);
        }
        return $instance;
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
    public function getLazyProxy(string $class, array $args = [], ?array $skipLazys = null, ?array $rawValues = null, ?\ReflectionParameter $injectPoint = null): mixed
    {
        $reflection = new \ReflectionClass($class);
        $instance = $reflection->newLazyProxy(function ($object) use ($class, $args, $injectPoint) {
            if (($args) || (method_exists($class, '__construct'))) {
                $this->injectFunctionArgs(new \ReflectionMethod($class, '__construct'), $args, $injectPoint);
                return new $class(... $args);
            } else {
                return new $class();
            }
        });
        if ($skipLazys || $rawValues) {
            $this->initializeLazyParameters($reflection, $instance, $skipLazys, $rawValues);
        }
        return $instance;
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
     * @param  mixed|null                   $injectPoint
     *
     * @return void
     * @throws InjectorNotFoundException
     */
    protected function injectFunctionArgs(\ReflectionFunctionAbstract $function, array &$args, ?\ReflectionParameter $injectCallerP = null): void
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
                    $args[$index] = $this->container->getInstance($types->getName(), [], false, $parameter);
                    continue;
                }
            } elseif ($types) {
                foreach ($types->getTypes() as $type) {
                    if (!$type->isBuiltin()) {
                        try {
                            $args[$index] = $this->container->getInstance($type->getName(), [], false, $parameter);
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
