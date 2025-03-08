<?php

namespace Jorro\Injector\Definition;

use Jorro\Injector\Lazy\LazyInterface;

class ClassDefinition
{
    public $constructor;
    public $properties;
    protected(set) ?LazyInterface $lazy = null;

    public function __construct(string $class, Definitions $definitions)
    {
        $reflectionClass = new \ReflectionClass($class);
        $this->properties = PropertyDefinitions::lazyGhost($reflectionClass);
        if ($reflectionMethod = $reflectionClass->getConstructor()) {
            $baseClass = $reflectionMethod->class;
            if ($baseClass != $class) {
                $definitions->data[$baseClass] ??= new static($baseClass, $definitions);
                $this->constructor = $definitions->data[$baseClass]->constructor;
            } else {
                $this->constructor = new ParameterDefinitions($reflectionMethod);
            }
        } else {
            $this->constructor = false;
        }
        return $this;
    }
}