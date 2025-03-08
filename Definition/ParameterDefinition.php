<?php

namespace Jorro\Injector\Definition;

use Jorro\Injector\Lazy\LazyInterface;
use Jorro\Injector\Plugin\PluginInterface;
use Jorro\Injector\Resolve\ResolveInterface;

class ParameterDefinition implements ResolveInterface
{
    protected(set) \ReflectionParameter|\ReflectionProperty|array $reflection;

    public function __construct(
        protected(set) string|array|null $id = null,
        protected(set) bool $optional = false,
        protected(set) ?LazyInterface $lazy = null,
        protected(set) array|PluginInterface|null $plugins = null,
    ) {
        if (is_string($this->id)) {
            $this->id = [$this->id];
        }
        if ($this->plugins instanceof PluginInterface) {
            $this->plugins = [$this->plugins];
        }
    }

    public function build(\ReflectionParameter|\ReflectionProperty $reflection): static
    {
        $this->reflection = $reflection;
        $this->getAttributes();
        if ($reflection->isDefaultValueAvailable()) {
            if (!$this->optional) {
                $this->id = null;
            }
        }
        if (!$this->id) {
            $this->getTypes();
        }
        return $this;
    }

    protected function getTypes(): void
    {
        if ($this->id) {
            return;
        }
        $this->id = null;
        $types = $this->reflection->getType();
        $typeNames = [];
        if ($types instanceof \ReflectionNamedType) {
            if (!$types->isBuiltin()) {
                $typeNames[] = $types->getName();
            }
        } elseif ($types) {
            foreach ($types->getTypes() as $type) {
                if (!$type->isBuiltin()) {
                    $typeNames[] = $type->getName();
                }
            }
        }
        if ($typeNames) {
            $this->id = $typeNames;
        }
    }

    protected function getAttributes(): void
    {
        if (!$this->lazy) {
            if ($lazy = $this->reflection->getAttributes(LazyInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
                $this->lazy = $lazy[0]->newInstance();
            }
        }
        if ($plugins = $this->reflection->getAttributes(PluginInterface::class, \ReflectionAttribute::IS_INSTANCEOF)) {
            foreach ($plugins as $plugin) {
                $this->plugins[] = $plugin->newInstance();
            }
        }
    }

    public function getReflection(): \ReflectionParameter|\ReflectionProperty
    {
        return $this->reflection;
    }

    public function applyPlugins(array &$injectArgs, array &$functionArgs, mixed $injectOptions): bool
    {
        $appliedInjectArgs = $injectArgs;
        $appliedFunctionArgs = $functionArgs;
        foreach ($this->plugins as $plugin) {
            if (!$plugin->apply($this, $appliedInjectArgs, $appliedFunctionArgs, $injectOptions)) {
                return false;
            }
        }
        $injectArgs = $appliedInjectArgs;
        $functionArgs = $appliedFunctionArgs;

        return true;
    }
}