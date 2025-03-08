<?php

namespace Jorro\Injector;

interface InjectorContainerInterface
{
    /**
     *  get / create instance
     *
     * @param  string      $class
     * @param  array       $args
     * @param  mixed|null  $injectOptions
     *
     * @return mixed
     */
    public function getInstance(string $class, array $args = []): mixed;
}