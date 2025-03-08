<?php

namespace Jorro\Injector;

use Jorro\Injector\InjectionMethodInterface;
use PHPUnit\Util\InvalidJsonException;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Jorro\Injector\Resolve\ResolvePluginInterface;
use Jorro\Injector\Resolve\ResolveInterface;
use Jorro\Injector\Resolve\Resolve;

class ResolvableInjector extends Injector
{
    protected $attributesCache = [];
    protected $noAttributesCache = [];

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

    public function injectFunctionArgs($function, &$args)
    {
        if (
            (isset($this->noAttributesCache[$function->class ?? ''][$function->name])) ||
            !($attributes = $this->getResolveAttributes($function->class ?? '', $function->name, $function))
        ) {
            // method does not have attributes
            foreach ($function->getParameters() as $index => $parameter) {
                if (key_exists($index, $args) || key_exists($parameter->getName(), $args) || ($parameter->isDefaultValueAvailable())) {
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
                    $name = $parameter->getName();
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
                                throw new InvalidJsonException('class not found : ' . (string)$types);
                            }
                        }
                    }
                }
            }
            if (!$parameters) {
                break;
            }
        } while ((($first) && (!$first = false)) || ($resolved || throw new ResolvePluginException()));
    }
}
