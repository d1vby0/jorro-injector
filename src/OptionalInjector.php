<?php

namespace Jorro\Injector;

use Jorro\Injector\Resolve\Optional;
use Psr\Container\NotFoundExceptionInterface;

class OptionalInjector extends Injector
{
    public function injectFunctionArgs($function, &$args)
    {
        foreach ($function->getParameters() as $index => $parameter) {
            if (key_exists($index, $args) || key_exists($parameter->getName(), $args)) {
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
                    throw new class('class not found : ' . (string)$types) extends \Exception implements NotFoundExceptionInterface {
                    };
                }
            } catch (NotFoundExceptionInterface $e) {
                if ($parameter->isDefaultValueAvailable()) {
                    return $parameter->getDefaultValue();
                }
                throw $e;
            }
        }
    }
}
