<?php

namespace Minormous\Dali\Entity\Interfaces;

/**
 * @psalm-immutable
 */
interface EntityInterface
{
    public function toArray(): array;
    public function with(mixed ...$values): static;
}
