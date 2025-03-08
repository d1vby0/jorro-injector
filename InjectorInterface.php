<?php

namespace Jorro\Injector;

use Psr\Container\ContainerInterface;

/**
 * Provides dependency injection for class methods, functions, and create instance.
 */
interface InjectorInterface extends ContainerInterface, InjectorContainerInterface
{

}