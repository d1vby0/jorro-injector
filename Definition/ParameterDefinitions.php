<?php

namespace Jorro\Injector\Definition;

use Jorro\Injector\Resolve\ResolveInterface;

class ParameterDefinitions
{
    protected static $selfReflection;

    protected(set) bool $hasPlugin;
    protected(set) ?array $definitions;

    public function __construct(\ReflectionFunctionAbstract $reflection)
    {
        $this->initialize($reflection->getParameters());
    }

    protected function initialize($parameters)
    {
        $this->hasPlugin = false;
        $this->definitions = [];
        foreach ($parameters as $index => $parameter) {
            $name = $parameter->name;
            if ($resolver = $parameter->getAttributes(ResolveInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                assert(!isset($resolver[1]), 'detected multiple parameter attributes');
                $resolver = $resolver[0]->newInstance();
            } else {
                $resolver = new ParameterDefinition();
            }
            $builded = $resolver->build($parameter);
            if ($builded->plugins) {
                $this->hasPlugin = true;
            }
            $this->definitions[$name] = $builded;
        }
        if (!$this->hasPlugin) {
            $this->definitions = array_filter($this->definitions, function ($p) {
                return ($p->id || $p->optional || $p->plugins || $p->lazy);
            });
        }
    }

    public static function lazyGhost($reflection): static
    {
        static::$selfReflection ??= new \ReflectionClass(static::class);
        return static::$selfReflection->newLazyGhost(function ($object) use ($reflection) {
            $object->__construct($reflection);
        });
    }
}