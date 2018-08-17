<?php

namespace Accessor\Data;

class TargetClass
{
    protected $foo;
    protected $bar;

    public function setFoo($foo)
    {
        $this->foo = $foo;
    }

    public function setBar($bar)
    {
        $this->bar = $bar;
    }
}
