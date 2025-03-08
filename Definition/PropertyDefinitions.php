<?php

namespace Jorro\Injector\Definition;

use Jorro\Injector\Resolve\ResolveInterface;

class PropertyDefinitions extends ParameterDefinitions
{
    protected static $selfReflection;

    public function __construct(\ReflectionClass $reflection)
    {
        $this->initialize($reflection);
    }
}