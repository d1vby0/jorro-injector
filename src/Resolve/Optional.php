<?php

namespace Jorro\Injector\Resolve;

/**
 * Alias of #[Resolve(optional: true)]
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
class Optional extends Resolve
{
    public function __construct()
    {
        $this->optional = true;
    }
}
