<?php

namespace DragonCode\SimpleDataTransferObject;

use DragonCode\Contracts\DataTransferObject\DataTransferObject as Contract;
use DragonCode\SimpleDataTransferObject\Contracts\Reflection;
use DragonCode\Support\Concerns\Makeable;
use DragonCode\Support\Facades\Helpers\Arr;
use DragonCode\Support\Facades\Helpers\Str;
use ReflectionClass;
use ReflectionProperty;

/**
 * @method static static make(array $items = [])
 */
abstract class DataTransferObject implements Contract
{
    use Makeable;
    use Reflection;

    protected $map = [];

    protected $disallow = ['map', 'disallow'];

    /**
     * @param  array  $items
     *
     * @throws \ReflectionException
     */
    public function __construct(array $items = [])
    {
        $this->setMap($items);
        $this->setItems($items);
    }

    public function toArray(): array
    {
        $reflect = new ReflectionClass($this);

        $properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
        $result     = [];

        foreach ($properties as $property) {
            $name = $property->getName();

            $result[$name] = $this->{$name};
        }

        return $result;
    }

    /**
     * @param  array  $items
     *
     * @throws \ReflectionException
     */
    protected function setMap(array $items): void
    {
        foreach ($this->map as $from => $to) {
            $value = $this->getValueByKey($items, $from, $to);

            $this->setValue($to, $value);
        }
    }

    /**
     * @param  array  $items
     *
     * @throws \ReflectionException
     */
    protected function setItems(array $items): void
    {
        foreach ($items as $key => $value) {
            $this->setValue($key, $value);
        }
    }

    protected function getValueByKey(array $items, string $key, string $default)
    {
        return Arr::get($items, $key) ?: Arr::get($items, $default);
    }

    /**
     * @param  string  $key
     * @param  mixed  $value
     *
     * @throws \ReflectionException
     */
    protected function setValue(string $key, $value): void
    {
        if ($this->isAllow($key)) {
            $this->{$key} = $value;
        }
    }

    /**
     * @param  string  $key
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    protected function isAllow(string $key): bool
    {
        return $this->isAllowKey($key) && $this->isAllowProperty($key);
    }

    /**
     * @param  string  $key
     *
     * @throws \ReflectionException
     *
     * @return bool
     */
    protected function isAllowProperty(string $key): bool
    {
        if ($this->reflection()->hasProperty($key)) {
            $property = $this->reflection()->getProperty($key);

            return ! $property->isStatic() && ! $property->isPrivate();
        }

        return false;
    }

    protected function isAllowKey(string $key): bool
    {
        return ! in_array(Str::lower($key), $this->disallow, true);
    }
}
