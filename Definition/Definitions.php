<?php

namespace Jorro\Injector\Definition;

use Jorro\Injector\InjectorNotFoundException;
use Jorro\Injector\Definition\ClassDefinition;

class Definitions
{
    protected int $dataCount = 0;
    public array $data = [];

    public function __construct(
        protected int $dataLimit = 500,
    ) {
    }

    public function build(string $class): mixed
    {
        if (!class_exists($class)) {
            throw new InjectorNotFoundException($class);
        }
        if (($this->dataLimit) && (++$this->dataCount > $this->dataLimit)) {
            $this->dataCount = floor($this->dataLimit / 2);
            $this->data = array_slice($this->data, $this->dataCount * -1);
        }
        return $this->data[$class] = new ClassDefinition($class, $this);
    }
}