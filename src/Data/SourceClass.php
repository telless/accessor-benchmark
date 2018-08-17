<?php

namespace Accessor\Data;

class SourceClass
{
    protected $foo;
    protected $bar;

    public function __construct()
    {
        $this->foo = 'foo';
        $this->bar = 'bar';
    }

    public function getFoo()
    {
        return $this->foo;
    }

    public function getBar()
    {
        return $this->bar;
    }
}
