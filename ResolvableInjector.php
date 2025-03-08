<?php

namespace Jorro\Injector;

use Jorro\Injector\Lazy\LazyParameterInterface;
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
    protected array $resolveAttributesCache = [];
    protected int $resolveAttributesCacheCount = 0;

    /**
     * @param  ContainerInterface|null  $container                   DI Container
     * @param  int                      $resolveAttributesCacheSize  Cache buffer size of resolve attributes
     */
    public function __construct(
        ?ContainerInterface $container = null,
        protected int $resolveAttributesCacheSize = 200
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
        if (++$this->resolveAttributesCacheCount > $this->resolveAttributesCacheSize) {
            // Truncate cache buffer
            $this->resolveAttributesCacheCount = floor($this->resolveAttributesCacheSize / 2);
            $this->resolveAttributesCache = array_slice($this->resolveAttributesCache, $this->resolveAttributesCacheCount * -1);
        }
        $attributes = [];
        foreach ($function->getParameters() as $parameter) {
            if ($resolve = $parameter->getAttributes(ResolveInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                // Only one resolve attribute can be specified.
                assert(!isset($resolve[1]), 'detected multiple resolve attributes');
                $resolve = $resolve[0]->newInstance();
            }
            if ($lazy = $parameter->getAttributes(LazyParameterInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                assert(!isset($lazy[1]), 'detected multiple lazy attributes');
                if (!$resolve) {
                    $resolve = new Resolve();
                }
                assert(!$resolve->lazy, 'detected multiple lazy attributes');
                $resolve->setLazy($lazy[0]->newInstance());
            }
            if ($plugins = $parameter->getAttributes(ResolvePluginInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                if (!$resolve) {
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
    protected function injectFunctionArgs(\ReflectionFunctionAbstract $function, array &$args, ?\ReflectionParameter $injectPoint = null): void
    {
        if (!$attributes = $this->resolveAttributesCache[$function->class ?? ''][$function->name] ??= $this->getAttributes($function)) {
            // method does not have attributes
            foreach ($function->getParameters() as $index => $parameter) {
                if (key_exists($index, $args) || key_exists($parameter->name, $args) || ($parameter->isDefaultValueAvailable())) {
                    continue;
                }
                $types = $parameter->getType();
                if ($types instanceof \ReflectionNamedType) {
                    if (!$types->isBuiltin()) {
                        $args[$index] = $this->container->getInstance($types->getName(), [], false, $parameter);
                        continue;
                    }
                } else {
                    if ($types) {
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
                        if (!$attribute->applyPlugins($injectPoint, $parameter, $injectArgs, $args)) {
                            $parameters[$name] = $parameter;
                            continue;
                        } else {
                            $resolved = true;
                            unset($parameters[$name]);
                        }
                    }
                    try {
                        if ($attribute->id) {
                            $args[$name] = $this->container->getInstance($attribute->id, $injectArgs, $attribute->lazy, $parameter);
                            continue;
                        }
                        $types = $parameter->getType();
                        if ($types instanceof \ReflectionNamedType) {
                            if (!$types->isBuiltin()) {
                                $args[$name] = $this->container->getInstance($types->getName(), $injectArgs, $attribute->lazy, $parameter);
                                continue;
                            }
                        } else {
                            if ($types) {
                                foreach ($types->getTypes() as $type) {
                                    if (!$type->isBuiltin()) {
                                        try {
                                            $args[$name] = $this->container->getInstance($type->getName(), $injectArgs, $attribute->lazy, $parameter);
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
                                $args[$name] = $this->container->getInstance($types->getName(), [], false, $parameter);
                                continue;
                            }
                        } else {
                            if ($types) {
                                foreach ($types->getTypes() as $type) {
                                    if (!$type->isBuiltin()) {
                                        try {
                                            $args[$name] = $this->container->getInstance($type->getName(), [], false, $parameter);
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
