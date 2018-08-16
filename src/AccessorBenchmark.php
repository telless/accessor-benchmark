<?php

namespace Accessor;

use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

/**
 * @BeforeMethods({"init"})
 */
class AccessorBenchmark
{
    /** @var SourceClass */
    private $sourceObject;
    /** @var TargetClass */
    private $targetObject;

    /** @var \ReflectionClass */
    private $targetReflection;
    /** @var \ReflectionClass */
    private $sourceReflection;

    /** @var \Closure */
    private $writeClosure;
    /** @var \Closure */
    private $readClosure;
    /** @var \Closure */
    private $readReferenceClosure;

    public function init()
    {
        $this->targetObject = new TargetClass();
        $this->sourceObject = new SourceClass();

        $this->sourceReflection = new \ReflectionClass(SourceClass::class);
        $this->targetReflection = new \ReflectionClass(TargetClass::class);

        $this->readClosure = function ($field) {
            return $this->$field;
        };
        $this->writeClosure = function ($field, $value) {
            $this->$field = $value;
        };

        $this->readReferenceClosure = function&($field) {
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

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchGettersSettersWrite()
    {
        $this->targetObject->setFoo($this->sourceObject->getFoo());
        $this->targetObject->setBar($this->sourceObject->getBar());
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchReflectionWrite()
    {
        foreach ($this->targetReflection->getProperties() as $property) {
            $source = new \ReflectionProperty(SourceClass::class, $property->getName());
            $property->setAccessible(true);
            $source->setAccessible(true);

            $property->setValue($this->targetObject, $source->getValue());
        }
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchReferenceClosureWithDefinedPropertiesWrite()
    {
        foreach (['foo', 'bar'] as $field) {
            $ref = &$this->readReferenceClosure->bindTo($this->targetObject, TargetClass::class)($field);
            $ref = $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field);
        }
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchClosureWithDefinedPropertiesWrite()
    {
        foreach (['foo', 'bar'] as $field) {
            $this->writeClosure->bindTo($this->targetObject, TargetClass::class)(
                $field,
                $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field)
            );
        }
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchReferenceClosureWithReflectedPropertiesWrite()
    {
        foreach ($this->targetReflection->getProperties() as $field) {
            $ref = &$this->readReferenceClosure->bindTo($this->targetObject, TargetClass::class)($field->getName());
            $ref = $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field->getName());
        }
    }

    /**
     * @Revs(1000000)
     * @Iterations(5)
     */
    public function benchClosureWithReflectedPropertiesWrite()
    {
        foreach ($this->sourceReflection->getProperties() as $field) {
            $this->writeClosure->bindTo($this->targetObject, TargetClass::class)(
                $field->getName(),
                $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field->getName())
            );
        }
    }
}
