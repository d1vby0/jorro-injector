<?php

namespace Jorro\Injector;


use Psr\Container\NotFoundExceptionInterface;

class InjectorNotFoundException extends \Exception implements  NotFoundExceptionInterface
{
}

