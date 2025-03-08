<?php

namespace Jorro\Injector\Resolve;

use Jorro\Injector\Definition\ParameterDefinition;

/**
 * Alias of #[Resolve(optional: true)]
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Optional extends ParameterDefinition
{
    public function __construct()
    {
        parent::__construct(optional: true);
    }
}
