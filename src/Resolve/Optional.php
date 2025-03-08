<?php

namespace Jorro\Injector\Resolve;

/**
 * Alias of #[Resolve(optional: true)]
 */
#[\Attribute(\Attribute::TARGET_PARAMETER)]
final class Optional extends Resolve
{
    public function __construct()
    {
        parent::__construct(optional: true);
    }
}
