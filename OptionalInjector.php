<?php

namespace Jorro\Injector;

use Jorro\Injector\Resolve\Optional;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 * This injector support Optional attribute.
 */
class OptionalInjector extends Injector
{
    /**
     * @inheritDoc
     */
    protected function injectFunctionArgs(\ReflectionFunctionAbstract $function, array &$args, ?\ReflectionParameter $injectPoint = null): void
    {
        foreach ($function->getParameters() as $index => $parameter) {
            if (key_exists($index, $args) || key_exists($parameter->name, $args)) {
                continue;
            }
            if ($parameter->isDefaultValueAvailable()) {
                if (empty($parameter->getAttributes(Optional::class))) {
                    $args[$index] = $parameter->getDefaultValue();
                    continue;
                }
            }
            try {
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
            } catch (NotFoundExceptionInterface $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    $args[$index] = $parameter->getDefaultValue();
                    continue;
                }
                throw $e;
            }
        }
    }
}
