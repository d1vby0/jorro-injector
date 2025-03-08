<?php

namespace Jorro\Injector\Resolve;

use Jorro\Injector\Definition\ParameterDefinition;
use Jorro\Injector\Lazy\LazyInterface;
use Jorro\Injector\Plugin\PluginInterface;
use Jorro\Injector\Resolve\ResolveInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY|\Attribute::TARGET_PARAMETER)]
class Resolve extends ParameterDefinition
{
}