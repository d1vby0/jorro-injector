<?php

namespace Jorro\Injector\Resolve;

use Jorro\Injector\Definition\ParameterDefinition;

/**
 * Alias of #[Resolve(id: $id)]
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Types extends ParameterDefinition
{
    public function __construct(string|array $id)
    {
        parent::__construct(id: $id);
    }
}
