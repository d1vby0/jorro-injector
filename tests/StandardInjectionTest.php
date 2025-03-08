<?php

namespace StandardInjectionTest;

use Jorro\Injector\InjectionMethod;
use Jorro\Injector\Injector;
use Jorro\Injector\OptionalInjector;
use Jorro\Injector\ResolvableInjector;
use Jorro\Injector\Resolve\Optional;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;

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

class StandardInjectionTest extends TestCase
{
    private Injector $injector;

    public static function injectorProvider()
    {
        return [
            [new Injector()],
            [new OptionalInjector()],
            [new ResolvableInjector()],
        ];
    }

    #[DataProvider('injectorProvider')]
    public function testInjection($injector)
    {
        $a = $injector->get(TheClassA::class);
        $this->assertEquals($a::class, TheClassA::class);
        $this->assertEquals($a->b::class, TheClassB::class);
        $this->assertEquals($a->c::class, TheClassC::class);
        $this->assertEquals($a->d::class, TheClassD::class);
        $this->assertEquals($a->b->c::class, TheClassC::class);
        $this->assertEquals($a->b->c->d::class, TheClassD::class);
    }

    #[DataProvider('injectorProvider')]
    public function testInjectionFailed($injector)
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $a = $injector->get(TheClassAFail::class);
    }

    #[DataProvider('injectorProvider')]
    public function testExtendClassInjection($injector)
    {
        $e = $injector->get(TheClassE::class);
        $this->assertEquals($e::class, TheClassE::class);
        $this->assertEquals($e->ea::class, TheClassA::class);
        $this->assertEquals($e->eb::class, TheClassExtendsA::class);
    }

    #[DataProvider('injectorProvider')]
    public function testUnionTypeInjection($injector)
    {
        $j = $injector->get(TheClassF::class);
        $this->assertEquals($j::class, TheClassF::class);
        $this->assertEquals($j->a::class, TheClassA::class);
    }

    #[DataProvider('injectorProvider')]
    public function testUnionTypeFailed($injector)
    {
        $this->expectException(NotFoundExceptionInterface::class);
        $j = $injector->get(TheClassFFail::class);
    }
}
