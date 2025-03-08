<?php

namespace Jorro\Injector;

use Jorro\Injector\Definition\Definitions;
use Jorro\Injector\Definition\ParameterDefinitions;
use Jorro\Injector\Resolver\ParameterResolverInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
class Injector implements InjectorInterface
{
    protected ?InjectorContainerInterface $container;

    /**
     * @param  ContainerInterface|InjectorContainerInterface|null  $container     Container
     * @param  array|\ArrayAccess                                  $classMapping  Class Mapping
     *                                                                            This mapping only used if class does not exist.
     *                                                                            if specified `$this`, return injector instance.
     */
    public function __construct(
        ContainerInterface|InjectorContainerInterface|null $container = null,
        protected Definitions $definitions = new Definitions(),
    ) {
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
            $this->container = new InjectorContainerAdapter($container, $this);
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
        return $this->getInstance($id, $args);
    }

    /**
     *  get or create instance
     *
     * @param  string      $class
     * @param  array       $args
     * @param  mixed|null  $injectOptions
     *
     * @return mixed
     * @throws InjectorNotFoundException
     * @throws \ReflectionException
     */
    public function getInstance(string $class, array $args = [], mixed $injectOptions = null): mixed
    {
        $classDefinitlion = $this->definitions->data[$class] ??= $this->definitions->build($class);
        if ($lazy = $classDefinitlion->lazy ?? $injectOptions?->lazy) {
            return $this->getLazyGhost($class, $args, null, null, $injectOptions);
            if ($lazy === true) {
            } elseif ($lazy instanceof LazyGhost) {
                return $this->getLazyGhost($class, $args, $lazy->skipLazys, $lazy->rawValues, $injectOptions);
            } elseif ($lazy instanceof LazyProxy) {
                return $this->getLazyProxy($class, $args, $lazy->skipLazys, $lazy->rawValues, $injectOptions);
            }
        }
        if (empty($classDefinitlion->constructor)) {
            $instance = new $class();
        } else {
            $this->injectFunctionArgs($classDefinitlion->constructor, $args, $injectOptions);
            $instance = new $class(...$args);
        }
        if ($classDefinitlion->properties) {

        }
        return $instance;
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
    public function getLazyGhost(string $class, array $args = [], ?array $skipLazys = null, ?array $rawValues = null, mixed $injectOptions = null): mixed
    {
        $reflection = new \ReflectionClass($class);
        $classDefinitlion = $this->definitions->data[$class] ??= $this->definitions->build($class);
        $instance = $reflection->newLazyGhost(function ($object) use ($class, $args, $classDefinitlion, $injectOptions) {
            if (!empty($classDefinitlion->constructor)) {
                $this->injectFunctionArgs($classDefinitlion->constructor, $args, $injectOptions);
                $object->__construct(...$args);
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
    public function getLazyProxy(string $class, array $args = [], ?array $skipLazys = null, ?array $rawValues = null, mixed $injectOptions = null): mixed
    {
        $reflection = new \ReflectionClass($class);
        $classDefinitlion = $this->definitions->data[$class] ??= $this->definitions->build($class);
        $instance = $reflection->newLazyGhost(function ($object) use ($class, $args, $classDefinitlion, $injectOptions) {
            if (empty($classDefinitlion->constructor)) {
                $instance = new $class();
            } else {
                $this->injectFunctionArgs($classDefinitlion->constructor, $args, $injectOptions);
                $instance = new $class(...$args);
            }
            return $instance;
        });
        if ($skipLazys || $rawValues) {
            $this->initializeLazyParameters($reflection, $instance, $skipLazys, $rawValues);
        }
        return $instance;
    }

    public function injectFunctionArgs(ParameterDefinitions $parameters, array &$args, mixed $injectOptions = null)
    {
        $hasArgs = !empty($args);
        $index = -1;
        if (!$parameters->hasPlugin) {
            foreach ($parameters->definitions as $name => $definition) {
                if ($hasArgs) {
                    $index++;
                    if (array_key_exists($index, $args) || array_key_exists($name, $args)) {
                        continue;
                    }
                }
                foreach ($definition->id as $id) {
                    try {
                        $args[$name] = $this->container->getInstance($id, [], $definition);
                        continue 2;
                    } catch (NotFoundExceptionInterface $e) {
                    }
                }
                if (!$definition->optional) {
                    throw new InjectorNotFoundException(implode('|', $definition->id));
                }
            }
        } else {
            $plugins = null;
            do {
                $resolved = is_null($plugins);
                foreach ($plugins ?? $parameters->definitions as $name => $definition) {
                    if ($hasArgs) {
                        $index++;
                        if (array_key_exists($name, $args)) {
                            continue;
                        }
                        if (array_key_exists($index, $args)) {
                            $args[$name] = $args[$index];
                            unset($args[$index]);
                            continue;
                        }
                    }
                    $injectArgs = [];
                    if ($definition->plugins) {
                        if (!$definition->applyPlugins($injectArgs, $args, $injectOptions)) {
                            $plugins[$name] = $definition;
                            continue;
                        } else {
                            $resolved = true;
                            unset($plugins[$name]);
                        }
                    }
                    if ($definition->id) {
                        foreach ($definition->id as $id) {
                            try {
                                $args[$name] = $this->container->getInstance($id, $injectArgs, $definition);
                                continue 2;
                            } catch (NotFoundExceptionInterface $e) {
                            }
                        }
                        if (!$definition->optional) {
                            throw new InjectorNotFoundException(implode('|', $definition->id));
                        }
                    }
                    $parameter = $definition->getReflection();
                    if ($parameter->isDefaultValueAvailable()) {
                        $args[$name] = $parameter->getDefaultValue();
                    }
                }
            } while ($resolved && $plugins);
            if ($plugins) {
                var_dump($plugins);
                throw new InjectorException();
            }
        }
    }

}
