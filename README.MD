# Benchmark read\write protected properties


* [Sources](#sources)
  * [SourceClass.php](#sourceclass)
  * [TargetClass.php](#targetclass)
  * [Init read tests](#init-read-tests)
  * [Read tests](#read-tests)
  * [Init](#init-write)
  * [Write](#write-test)
* [Run](#run)
* [Results](#results)
  * [Read results](#read-results)
  * [Write results](#write-results)


### Sources
#### SourceClass
```php
<?php

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
```

#### TargetClass
```php
<?php

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

```

#### Init read tests

```php
<?php

public function init()
{
    $this->sourceObject = new SourceClass();
    $this->sourceReflection = new \ReflectionClass(SourceClass::class);
    $this->readClosure = function ($field) {
        return $this->$field;
    };
}
```

#### Read tests
```php
<?php

public function benchArrayRead()
{
    $data = (array)$this->sourceObject;
    foreach ($data as $key => $value) {
        $res = [$key, $value];
    }

    return $res;
}

public function benchGettersRead()
{
    $res = $this->sourceObject->getBar();
    $res = $this->sourceObject->getFoo();

    return $res;
}

public function benchReflectionWithDefinedPropertiesRead()
{
    foreach (['foo', 'bar'] as $field) {
        $prop = new \ReflectionProperty(SourceClass::class, $field);
        $prop->setAccessible(true);
        $res = [$field, $prop->getValue($this->sourceObject)];
    }

    return $res;
}

public function benchReflectionRead()
{
    foreach ($this->sourceReflection->getProperties() as $prop) {
        $prop->setAccessible(true);
        $res = [$prop->getName(), $prop->getValue($this->sourceObject)];
    }

    return $res;
}

public function benchClosureWithDefinedPropertiesRead()
{
    foreach (['foo', 'bar'] as $field) {
        $res = [$field, $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field)];
    }

    return $res;
}

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
```
#### Init write tests
```php
<?php

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
```

#### Write tests
```php
<?php

public function benchGettersSettersWrite()
{
    $this->targetObject->setFoo($this->sourceObject->getFoo());
    $this->targetObject->setBar($this->sourceObject->getBar());
}

public function benchReflectionWrite()
{
    foreach ($this->targetReflection->getProperties() as $property) {
        $source = new \ReflectionProperty(SourceClass::class, $property->getName());
        $property->setAccessible(true);
        $source->setAccessible(true);

        $property->setValue($this->targetObject, $source->getValue());
    }
}

public function benchReferenceClosureWithDefinedPropertiesWrite()
{
    foreach (['foo', 'bar'] as $field) {
        $ref = &$this->readReferenceClosure->bindTo($this->targetObject, TargetClass::class)($field);
        $ref = $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field);
    }
}

public function benchClosureWithDefinedPropertiesWrite()
{
    foreach (['foo', 'bar'] as $field) {
        $this->writeClosure->bindTo($this->targetObject, TargetClass::class)(
            $field,
            $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field)
        );
    }
}

public function benchReferenceClosureWithReflectedPropertiesWrite()
{
    foreach ($this->targetReflection->getProperties() as $field) {
        $ref = &$this->readReferenceClosure->bindTo($this->targetObject, TargetClass::class)($field->getName());
        $ref = $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field->getName());
    }
}

public function benchClosureWithReflectedPropertiesWrite()
{
    foreach ($this->sourceReflection->getProperties() as $field) {
        $this->writeClosure->bindTo($this->targetObject, TargetClass::class)(
            $field->getName(),
            $this->readClosure->bindTo($this->sourceObject, SourceClass::class)($field->getName())
        );
    }
}
```


### Run
```
composer install
vendor/bin/phpbench run src/Benchmark/ReadBenchmark.php --report='generator: "table", cols: ["subject", "best", "mode", "worst", "diff"], diff_col: "mode", sort: {mode: "asc"}'
vendor/bin/phpbench run src/Benchmark/WriteBenchmark.php --report='generator: "table", cols: ["subject", "best", "mode", "worst", "diff"], diff_col: "mode", sort: {mode: "asc"}'
```

### Results

Benchmarks ran with 5 iterations of 1,000,000 revs

#### Read results
```
+------------------------------------------+---------+---------+---------+--------+
| subject                                  | best    | mode    | worst   | diff   |
+------------------------------------------+---------+---------+---------+--------+
| benchGettersRead                         | 0.115μs | 0.116μs | 0.116μs | 1.00x  |
| benchArrayRead                           | 0.216μs | 0.219μs | 0.222μs | 1.90x  |
| benchClosureWithDefinedPropertiesRead    | 0.745μs | 0.782μs | 0.790μs | 6.77x  |
| benchReflectionRead                      | 1.168μs | 1.170μs | 1.180μs | 10.12x |
| benchReflectionWithDefinedPropertiesRead | 1.260μs | 1.268μs | 1.310μs | 10.97x |
| benchClosureWithReflectedPropertiesRead  | 1.512μs | 1.544μs | 1.553μs | 13.36x |
+------------------------------------------+---------+---------+---------+--------+
```

#### Write results
```
+---------------------------------------------------+---------+---------+---------+--------+
| subject                                           | best    | mode    | worst   | diff   |
+---------------------------------------------------+---------+---------+---------+--------+
| benchGettersSettersWrite                          | 0.196μs | 0.203μs | 0.212μs | 1.00x  |
| benchReferenceClosureWithDefinedPropertiesWrite   | 1.250μs | 1.263μs | 1.339μs | 6.23x  |
| benchClosureWithDefinedPropertiesWrite            | 1.263μs | 1.302μs | 1.328μs | 6.43x  |
| benchClosureWithReflectedPropertiesWrite          | 2.035μs | 2.102μs | 2.122μs | 10.38x |
| benchReferenceClosureWithReflectedPropertiesWrite | 2.158μs | 2.179μs | 2.279μs | 10.76x |
| benchReflectionWrite                              | 6.423μs | 6.644μs | 9.178μs | 32.80x |
+---------------------------------------------------+---------+---------+---------+--------+
```
