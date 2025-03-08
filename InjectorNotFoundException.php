<?php

namespace Jorro\Injector;

use Psr\Container\NotFoundExceptionInterface;

class InjectorNotFoundException extends InjectorException implements NotFoundExceptionInterface
{
}
