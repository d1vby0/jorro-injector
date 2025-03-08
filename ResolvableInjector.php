<?php

namespace Jorro\Injector;

use Jorro\Buffer\LimitedArray;
use Jorro\Buffer\LimitedArrayInterface;
use Jorro\Injector\Resolve\Resolve;
use Jorro\Injector\Resolve\ResolveInterface;
use Jorro\Injector\ResolvePlugin\ResolvePluginInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 * This injector support Optional, Resovle, ResolvePlugin attributes.
 */
class ResolvableInjector extends Injector
{
    /**
     * @param  ContainerInterface|null  $container   DI Container
     * @param  LimitedArrayInterface    $attributes  Cache buffer of resolve attributes to performance
     */
    public function __construct(
        ?ContainerInterface $container = null,
        protected LimitedArrayInterface $attributes = new LimitedArray(200, 100)
    ) {
        parent::__construct($container);
    }

    /**
     * Get resolve attributes of function parameters
     *
     * @param  \ReflectionFunctionAbstract  $function
     *
     * @return mixed
     */
    protected function getAttributes(\ReflectionFunctionAbstract $function): mixed
    {
        $this->attributes->incliment();
        $attributes = [];
        foreach ($function->getParameters() as $parameter) {
            if ($resolve = $parameter->getAttributes(ResolveInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                // Only one resolve attribute can be specified.
                assert(!isset($resolve[1]), 'detected multiple resolve attributes');
                $resolve = $resolve[0]->newInstance();
            }
            if ($plugins = $parameter->getAttributes(ResolvePluginInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                if (!$resolve) {
                    // If no resolve attribute specified
                    $resolve = new Resolve();
                }
                foreach ($plugins as $plugin) {
                    $resolve->addPlugin($plugin->newInstance());
                }
            }
            if ($resolve) {
                $attributes[$parameter->name] = $resolve;
            }
        }
        return $attributes ?: false;
    }

    /**
     * @inheritDoc
     */
    protected function injectFunctionArgs(\ReflectionFunctionAbstract $function, array &$args): void
    {
        if (!$attributes = $this->attributes->array[$function->class ?? ''][$function->name] ??= $this->getAttributes($function)) {
            // method does not have attributes
            foreach ($function->getParameters() as $index => $parameter) {
                if (key_exists($index, $args) || key_exists($parameter->name, $args) || ($parameter->isDefaultValueAvailable())) {
                    continue;
                }
                $types = $parameter->getType();
                if ($types instanceof \ReflectionNamedType) {
                    if (!$types->isBuiltin()) {
                        $args[$index] = $this->container->get($types->getName());
                        continue;
                    }
                } else {
                    if ($types) {
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
            return;
        }
        $first = true;
        $parameters = null;
        do {
            $resolved = false;
            foreach ($parameters ?? $function->getParameters() as $index => $parameter) {
                if ($first) {
                    $name = $parameter->name;
                    if (key_exists($name, $args)) {
                        continue;
                    }
                    if (key_exists($index, $args)) {
                        $args[$name] = $args[$index];
                        unset($args[$index]);
                        continue;
                    }
                } else {
                    $name = $index;
                }
                if ($attribute = $attributes[$name] ?? null) {
                    if ((!$attribute->optional) && ($parameter->isDefaultValueAvailable())) {
                        $args[$name] = $parameter->getDefaultValue();
                        continue;
                    }
                    $injectArgs = [];
                    if ($attribute->hasPlugins()) {
                        if (!$attribute->applyPlugins($function, $parameter, $injectArgs, $args)) {
                            $parameters[$name] = $parameter;
                            continue;
                        } else {
                            $resolved = true;
                            unset($parameters[$name]);
                        }
                    }
                    try {
                        if ($attribute->id) {
                            $args[$name] = $this->container->get($attribute->id, ... $injectArgs);
                            continue;
                        }
                        $types = $parameter->getType();
                        if ($types instanceof \ReflectionNamedType) {
                            if (!$types->isBuiltin()) {
                                $args[$name] = $this->container->get($types->getName(), ... $injectArgs);
                                continue;
                            }
                        } else {
                            if ($types) {
                                foreach ($types->getTypes() as $type) {
                                    if (!$type->isBuiltin()) {
                                        try {
                                            $args[$name] = $this->container->get($type->getName(), ... $injectArgs);
                                            continue 2;
                                        } catch (NotFoundExceptionInterface $e) {
                                        }
                                    }
                                }
                                throw new InjectorNotFoundException('class not found : ' . (string)$types);
                            }
                        }
                    } catch (NotFoundExceptionInterface $e) {
                        if ($parameter->isDefaultValueAvailable()) {
                            $args[$name] = $parameter->getDefaultValue();
                            continue;
                        }
                        throw $e;
                    }
                } else {
                    if ($parameter->isDefaultValueAvailable()) {
                        $args[$name] = $parameter->getDefaultValue();
                    } else {
                        $types = $parameter->getType();
                        if ($types instanceof \ReflectionNamedType) {
                            if (!$types->isBuiltin()) {
                                $args[$name] = $this->container->get($types->getName());
                                continue;
                            }
                        } else {
                            if ($types) {
                                foreach ($types->getTypes() as $type) {
                                    if (!$type->isBuiltin()) {
                                        try {
                                            $args[$name] = $this->container->get($type->getName());
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
            }
            if (!$parameters) {
                break;
            }
        } while ((($first) && (!$first = false)) || ($resolved || throw new InjectorException()));
    }
}
