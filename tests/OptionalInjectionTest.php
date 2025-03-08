<?php

namespace OptionalInjectorTest;

use Jorro\Injector\Injector;
use Jorro\Injector\Resolve\Optional;
use PHPUnit\Framework\Attributes\DataProvider;
use Jorro\Injector\ResolvableInjector;
use Jorro\Injector\OptionalInjector;
use PHPUnit\Framework\TestCase;

class TheClassA
{
    public function __construct(public TheClassB $b, public TheClassC $c, public TheClassD $d)
    {
    }
}

class TheClassAFail
{
    public function __construct(public TheClassB $b, public TheClassC $c, public TheClassD $d, TheClassNone $n)
    {
    }
}

class TheClassExtendsA extends TheClassA
{
}

class TheClassB
{
    public function __construct(public TheClassC $c)
    {
    }
}

class TheClassC
{
    public function __construct(public TheClassD $d)
    {
    }
}

class TheClassD
{
}

class TheClassE
{
    public function __construct(public TheClassA $ea, public TheClassExtendsA $eb)
    {
    }
}

class TheClassF
{
    public function __construct(
        public TheClassNone1|TheClassNone2|TheClassA $a
    ) {
    }
}

class TheClassFFail
{
    public function __construct(
        public TheClassNone1|TheClassNone2 $a
    ) {
    }
}

class TheClassG
{
    public function __construct(
        public ?TheClassA $a = null,
        #[Optional]
        public ?TheClassA $b = null,
        #[Optional]
        public TheClassNone1|TheClassNone2|null $c = null,
    ) {
    }
}

class OptionalInjectionTest extends TestCase
{
    private Injector $injector;

    public static function injectorProvider()
    {
        return [
            [new OptionalInjector()],
            [new ResolvableInjector()],
        ];
    }

    #[DataProvider('injectorProvider')]
    public function testOptionalInjection($injector)
    {
        $g = $injector->get(TheClassG::class);
        $this->assertEquals($g::class, TheClassG::class);
        $this->isNull($g->a);
        $this->assertEquals(TheClassA::class, $g->b::class);
        $this->isNull($g->c);
    }

}
