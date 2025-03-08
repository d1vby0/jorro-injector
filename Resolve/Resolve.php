<?php

namespace Jorro\Injector\Resolve;

use Jorro\Injector\Lazy\LazyParameterInterface;
use Jorro\Injector\ResolvePlugin\ResolvePluginInterface;

/**
 * #[Resolve('id')]
 * #[Resolve(id: 'id', optional: true)]
 * #[Resolve(plugins: new Using('id')]
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Resolve implements ResolveInterface
{
    /**
     * @param  string|null                        $id        Inject from container using the id
     * @param  bool                               $optional  Prefer injection over the default value.
     *
     *  function TheFunction (#[Resolve(optional:true)] A|B $param = null)
     * 1. Attempts to inject Class A instance into $param.
     * 2. (if Class A not exists) Attempts to inject Class B instance into $param.
     * 3. (if Class B not exists) Set null into $param.
     *
     * @param  array|ResolvePluginInterface|null  $plugins   plugins
     */
    public function __construct(
        protected(set) ?string $id = null,
        protected(set) bool $optional = false,
        protected(set) LazyParameterInterface|bool $lazy = false,
        protected array|ResolvePluginInterface|null $plugins = null,
    ) {
        if ($this->plugins) {
            if (!is_array($this->plugins)) {
                $this->plugins = [$this->plugins];
            }
        }
    }

    public function hasPlugins(): bool
    {
        return !empty($this->plugins);
    }

    /**
     * @inheritDoc
     */
    public function addPlugin(ResolvePluginInterface $plugin): void
    {
        $this->plugins[] = $plugin;
    }

    /**
     * @inheritDoc
     */
    public function setLazy(LazyParameterInterface $lazy): void
    {
        $this->lazy = $lazy;
    }

    /**
     * @inheritDoc
     */
    public function applyPlugins(?\ReflectionParameter $injectCallerPoint, \ReflectionParameter $injectPoint, array &$injectArgs, array &$functionArgs): bool
    {
        $inArgs = $injectArgs;
        $funcArgs = $functionArgs;
        foreach ($this->plugins as $plugin) {
            if (!$plugin->apply($injectCallerPoint, $injectPoint, $inArgs, $funcArgs)) {
                return false;
            }
        }
        $injectArgs = $inArgs;
        $functionArgs = $funcArgs;

        return true;
    }
}
