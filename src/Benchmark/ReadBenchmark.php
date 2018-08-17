<?php

namespace Accessor\Benchmark;

use Accessor\Data\SourceClass;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

/**
 * @BeforeMethods({"init"})
 */
class ReadBenchmark
{
    /** @var SourceClass */
    private $sourceObject;
    /** @var \ReflectionClass */
    private $sourceReflection;
    /** @var \Closure */
    private $readClosure;

    public function init()
    {
        $this->sourceObject = new SourceClass();
        $this->sourceReflection = new \ReflectionClass(SourceClass::class);
        $this->readClosure = function ($field) {
            return $this->$field;
        };
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchArrayRead()
    {
        $data = (array)$this->sourceObject;
        foreach ($data as $key => $value) {
            $res = [$key, $value];
        }

        return $res;
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchGettersRead()
    {
        $res = $this->sourceObject->getBar();
        $res = $this->sourceObject->getFoo();

        return $res;
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchReflectionWithDefinedPropertiesRead()
    {
        foreach (['foo', 'bar'] as $field) {
            $prop = new \ReflectionProperty(SourceClass::class, $field);
            $prop->setAccessible(true);
            $res = [$field, $prop->getValue($this->sourceObject)];
        }

        return $res;
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchReflectionRead()
    {
        foreach ($this->sourceReflection->getProperties() as $prop) {
            $prop->setAccessible(true);
            $res = [$prop->getName(), $prop->getValue($this->sourceObject)];
        }

        return $res;
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchClosureWithDefinedPropertiesRead()
    {
        foreach (['foo', 'bar'] as $field) {
            $res = [$field, $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field)];
        }

        return $res;
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchClosureWithReflectedPropertiesRead()
    {
        foreach ($this->sourceReflection->getProperties() as $prop) {
            $res = [
                $prop->getName(),
                $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($prop->getName()),
            ];
        }

        return $res;
    }
}
