<?php

namespace Jorro\Injector;

use Jorro\Injector\Resolve\CacheResolve;
use Jorro\Injector\Resolve\Resolve;
use Jorro\Injector\Resolve\ResolveInterface;
use Jorro\Injector\Resolve\ResolvePluginInterface;
use Psr\Container\NotFoundExceptionInterface;

class ResolvableInjector extends Injector
{
    protected $attributesCache = [];
    protected $noAttributesCache = [];

    /**
     * resolve value by parameter type
     *
     * This method is untyped, are similar resolveValue... for performance reasons.
     *
     * @param $parameter \ReflectionParameter
     * @param $args      array
     *
     * @return void
     */
    protected function resolveValue($parameter, $args)
    {
        $types = $parameter->getType();
        if ($types instanceof \ReflectionNamedType) {
            if (!$types->isBuiltin()) {
                return $this->container->get($types->getName(), ...$args);
            }
        } else {
            if ($types) {
                foreach ($types->getTypes() as $type) {
                    if (!$type->isBuiltin()) {
                        try {
                            return $this->container->get($type->getName(), ...$args);
                        } catch (NotFoundExceptionInterface $e) {
                        }
                    }
                }
                throw new class('class not found : ' . (string)$types) extends \Exception implements NotFoundExceptionInterface {
                };
            }
        }
    }

    /**
     * Get and cache function parameter's attributes.
     *
     * Only attributes of constructor or #[CacheResolve] marked functions are cached
     * This method is untyped for performance reasons.
     *
     * @param $class    string class name
     * @param $name     string function name
     * @param $function \ReflectionFunctionAbstract
     *
     * @return array|false
     */
    protected function getResolveAttributes($class, $name, $function)
    {
        if (empty($this->attributesCache[$class][$name])) {
            $attributes = [];
            foreach ($parameters = $function->getParameters() as $parameter) {
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
                    $attributes[$parameter->getName()] = $resolve;
                }
            }
            if ($attributes) {
                if (!isset($this->attributesCache[$class][$name])) {
                    // Only attributes of constructor or #[CacheResolve] marked functions are cached
                    if ($name !== '__construct') {
                        if ((!($cache = $function->getAttributes(CacheResolve::class)) || (!$cache->newInstance()->cache))) {
                            $this->attributesCache[$class][$name] = false;
                            return $attributes;
                        }
                    }
                    $this->attributesCache[$class][$name] = $attributes;
                }
            } else {
                $this->noAttributesCache[$class][$name] = true;
                return false;
            }
        } else {
            $attributes = $this->attributesCache[$class][$name];
        }
        return $attributes;
    }

    /**
     * @inheritDoc
     */
    protected function injectFunctionArgs($function, &$args)
    {
        if (
            (isset($this->noAttributesCache[$function->class ?? ''][$function->name])) ||
            !($attributes = $this->getResolveAttributes($function->class ?? '', $function->name, $function))
        ) {
            parent::injectFunctionArgs($function, $args);
            return;
        }
        foreach ($function->getParameters() as $index => $parameter) {
            $name = $parameter->getName();
            if (key_exists($name, $args)) {
                continue;
            }
            if (key_exists($index, $args)) {
                $args[$name] = $args[$index];
                unset($args[$index]);
                continue;
            }
            if ($attribute = $attributes[$name] ?? null) {
                $injectArgs = [];
                if ((!$attribute->optional) && ($parameter->isDefaultValueAvailable())) {
                    $args[$name] = $parameter->getDefaultValue();
                    continue;
                }
                if ($attribute->hasPlugins()) {
                    if ($attribute->hasDeferPlugin()) {
                        $hasDeferPluginParameters[$name] = $parameter;
                        continue;
                    } else {
                        if (!$attribute->applyPlugins($function, $parameter, $injectArgs, $args)) {
                            throw new ResolvePluginException();
                        }
                    }
                }
                try {
                    if ($attribute->id) {
                        $args[$name] = $this->container->get($attribute->id, ... $injectArgs);
                        continue;
                    }
                    $args[$name] = $this->resolveValue($parameter, $injectArgs);
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
                    $args[$name] = $this->resolveValue($parameter, []);
                }
            }
        }

        if (isset($hasDeferPluginParameters)) {
            do {
                $resolved = false;
                foreach ($hasDeferPluginParameters as $name => $parameter) {
                    $attribute = $attributes[$name];
                    $injectArgs = [];
                    if (!$attribute->applyPlugins($function, $parameter, $injectArgs, $args)) {
                        continue;
                    }
                    $resolved = true;
                    unset($hasDeferPluginParameters[$name]);
                    try {
                        if ($attribute->id) {
                            $args[$name] = $this->container->get($attribute->id, ... $injectArgs);
                            continue;
                        }
                        $args[$name] = $this->resolveValue($parameter, $injectArgs);
                    } catch (NotFoundExceptionInterface $e) {
                        if ($parameter->isDefaultValueAvailable()) {
                            $args[$name] = $parameter->getDefaultValue();
                            continue;
                        }
                        throw $e;
                    }
                }
            } while (!$resolved || $hasDeferPluginParameters);
            if ($hasDeferPluginParameters) {
                throw new ResolvePluginException();
            }
        }
    }
}