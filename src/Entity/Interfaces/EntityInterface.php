<?php

namespace Minormous\Dali\Entity\Interfaces;

/**
 * @psalm-immutable
 */
interface EntityInterface
{
    /**
     * @return array<string,mixed>
     */
    public function toArray(): array;
    public function with(mixed ...$values): static;
}
