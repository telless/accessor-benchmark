<?php

namespace Accessor\Benchmark;

use Accessor\Data\SourceClass;
use Accessor\Data\TargetClass;
use PhpBench\Benchmark\Metadata\Annotations\BeforeMethods;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\Revs;

/**
 * @BeforeMethods({"init"})
 */
class WriteBenchmark
{
    /** @var SourceClass */
    private $sourceObject;
    /** @var \ReflectionClass */
    private $sourceReflection;
    /** @var \Closure */
    private $readClosure;

    /** @var TargetClass */
    private $targetObject;
    /** @var \ReflectionClass */
    private $targetReflection;
    /** @var \Closure */
    private $readReferenceClosure;
    /** @var \Closure */
    private $writeClosure;

    public function init()
    {
        $this->sourceObject = new SourceClass();
        $this->sourceReflection = new \ReflectionClass(SourceClass::class);
        $this->readClosure = function ($field) {
            return $this->$field;
        };

        $this->targetObject = new TargetClass();
        $this->targetReflection = new \ReflectionClass(TargetClass::class);
        $this->readReferenceClosure = function&($field) {
            return $this->$field;
        };
        $this->writeClosure = function ($field, $value) {
            $this->$field = $value;
        };
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
