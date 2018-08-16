# Benchmark скорости чтения\записи закрытых свойств различными методами


* [Исходники](#исходники)
  * [SourceClass.php](#sourceclassphp)
  * [TargetClass.php](#targetclassphp)
  * [Инициализация](#инициализация)
  * [Тесты чтения](#тесты-чтения)
  * [Тесты записи](#тесты-записи)
* [Запуск тестов](#запуск-тестов)
* [Результаты](#таблица-результатов)


### Исходники
#### SourceClass.php

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

#### TargetClass.php

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

#### Инициализация

```php
<?php

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
```

#### Тесты чтения
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

#### Тесты записи
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


### Запуск тестов
```
composer install
vendor/bin/phpbench run src/AccessorBenchmark.php --report='generator: "table", cols: ["subject", "best", "mode", "worst", "diff"], diff_col: "mode", sort: {mode: "desc"}'
```

### Таблица результатов

```
+---------------------------------------------------+---------+---------+---------+--------+
| subject                                           | best    | mode    | worst   | diff   |
+---------------------------------------------------+---------+---------+---------+--------+
| benchReflectionWrite                              | 6.464μs | 6.508μs | 6.839μs | 54.43x |
| benchReferenceClosureWithReflectedPropertiesWrite | 2.091μs | 2.131μs | 2.177μs | 17.82x |
| benchClosureWithReflectedPropertiesWrite          | 1.993μs | 2.023μs | 2.093μs | 16.92x |
| benchClosureWithReflectedPropertiesRead           | 1.426μs | 1.491μs | 2.431μs | 12.47x |
| benchReferenceClosureWithDefinedPropertiesWrite   | 1.291μs | 1.296μs | 1.324μs | 10.84x |
| benchReflectionWithDefinedPropertiesRead          | 1.247μs | 1.295μs | 1.321μs | 10.83x |
| benchClosureWithDefinedPropertiesWrite            | 1.228μs | 1.257μs | 1.271μs | 10.51x |
| benchReflectionRead                               | 1.123μs | 1.149μs | 1.183μs | 9.61x  |
| benchClosureWithDefinedPropertiesRead             | 0.722μs | 0.733μs | 0.757μs | 6.13x  |
| benchArrayRead                                    | 0.204μs | 0.211μs | 0.212μs | 1.76x  |
| benchGettersSettersWrite                          | 0.197μs | 0.207μs | 0.209μs | 1.74x  |
| benchGettersRead                                  | 0.110μs | 0.120μs | 0.124μs | 1.00x  |
+---------------------------------------------------+---------+---------+---------+--------+
```
