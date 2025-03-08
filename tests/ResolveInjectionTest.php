<?php

namespace ResolvableInjectorTest;

use Jorro\Injector\Injector;
use Jorro\Injector\Resolve\Resolve;
use Jorro\Injector\Resolve\Using;
use Jorro\Injector\Resolve\Values;
use PHPUnit\Framework\Attributes\DataProvider;
use Jorro\Injector\ResolvableInjector;
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
        #[Resolve(id: TheClassExtendsA::class)]
        public ?TheClassA $a,
        #[Resolve(optional: true)]
        public ?TheClassA $b,
        #[Resolve(optional: true, id: TheClassExtendsA::class)]
        public ?TheClassA $c,
        #[Resolve(id: TheClassExtendsA::class)]
        public ?TheClassA $na = null,
        #[Resolve(optional: true)]
        public ?TheClassA $nb = null,
        #[Resolve(optional: true, id: TheClassExtendsA::class)]
        public ?TheClassA $nc = null,
    ) {
    }
}

class TheClassId
{
    public function __construct(public $id = 'default')
    {
    }
}

class TheClassIdExtend
{
    public function __construct(public $id = 'default')
    {
    }
}

class TheClassH
{
    public function __construct(
        $data1,
        public TheClassId $a,
        #[Using('id')]
        public TheClassId $b,
        #[Using(id: 'data1')]
        public TheClassId $c,
        #[Using(id: 'data2')]
        public TheClassId $d,
        #[Resolve(TheClassIdExtend::class)] #[Using('id')]
        public $e,
        #[Resolve(TheClassIdExtend::class, plugins: new Using(id: 'data2'))]
        public $f,
        $data2 = 'the_data2',
        $id = 'the_id'
    ) {
    }
}

class TheClassI
{
    public function __construct(
        #[Values('direct')]
        public TheClassId $a,
        #[Values(id: 'named')]
        public TheClassId $b,
        #[Resolve(TheClassIdExtend::class)] #[Values('multiple')]
        public $c,
        #[Resolve(TheClassIdExtend::class, plugins: new Values(id: 'plugins'))]
        public $d,
    ) {
    }
}

class ResolveInjectionTest extends TestCase
{
    private Injector $injector;

    public static function injectorProvider()
    {
        return [
            [new ResolvableInjector()]
        ];
    }

    #[DataProvider('injectorProvider')]
    public function testResolveInjection($injector)
    {
        $g = $injector->get(TheClassG::class);
        $this->assertEquals($g::class, TheClassG::class);

        $this->assertEquals(TheClassExtendsA::class, $g->a::class);
        $this->assertEquals(TheClassA::class, $g->b::class);
        $this->assertEquals(TheClassExtendsA::class, $g->c::class);
        $this->isNull($g->na);
        $this->assertEquals(TheClassA::class, $g->nb::class);
        $this->assertEquals(TheClassExtendsA::class, $g->nc::class);
    }

    #[DataProvider('injectorProvider')]
    public function testUsingInjection($injector)
    {
        $h = $injector->get(TheClassH::class, data1: 'the_data1');
        $this->assertEquals(TheClassH::class, $h::class);
        $this->assertEquals('default', $h->a->id);
        $this->assertEquals('the_id', $h->b->id);
        $this->assertEquals('the_data1', $h->c->id);
        $this->assertEquals('the_data2', $h->d->id);
        $this->assertEquals(TheClassIdExtend::class, $h->e::class);
        $this->assertEquals('the_id', $h->e->id);
        $this->assertEquals(TheClassIdExtend::class, $h->f::class);
        $this->assertEquals('the_data2', $h->f->id);
    }

    #[DataProvider('injectorProvider')]
    public function testValuesInjection($injector)
    {
        $i = $injector->get(TheClassI::class);
        $this->assertEquals(TheClassI::class, $i::class);
        $this->assertEquals('direct', $i->a->id);
        $this->assertEquals('named', $i->b->id);
        $this->assertEquals('multiple', $i->c->id);
        $this->assertEquals('plugins', $i->d->id);
    }

}
