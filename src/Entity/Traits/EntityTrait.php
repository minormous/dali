<?php

namespace Minormous\Dali\Entity\Traits;

use Minormous\Dali\Exceptions\InvalidEntityException;
use Webmozart\Assert\Assert;

trait EntityTrait
{
    use ToArray;

    public function __construct(array $values)
    {
        foreach ($values as $key => $value) {
            if (!\property_exists($this, $key)) {
                continue;
            }

            $this->set($key, $value);
        }
    }

    /**
     * @param array<mixed> $values Using named arguments, set values.
     * @return static A copy with the new values set of the current object.
     */
    public function with(mixed ...$values): static
    {
        Assert::allStringNotEmpty(\array_keys($values));
        $new = clone $this;
        foreach ($values as $key => $value) {
            $new->set($key, $value);
        }

        return $new;
    }

    protected function set(string $key, mixed $value): void
    {
        if (!\property_exists($this, $key)) {
            throw InvalidEntityException::withProperty(\get_class($this), $key);
        }

        $this->{$key} = $value;
    }
}
