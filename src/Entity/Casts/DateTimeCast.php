<?php

namespace Minormous\Dali\Entity\Casts;

use DateTimeImmutable;
use Minormous\Dali\Entity\Interfaces\CastInterface;

/**
 * @template-implements CastInterface<\DateTimeInterface, string>
 */
final class DateTimeCast implements CastInterface
{
    public function __construct(
        private string $dateTimeClass = DateTimeImmutable::class,
    ) {
    }

    /**
     * @param \DateTimeInterface $value
     * @return string
     */
    public function toRaw($value): string
    {
        return $value->format('Y-m-d H:i:s');
    }

    public function toValue($raw)
    {
        $dateTimeClass = $this->dateTimeClass;

        return new $dateTimeClass($raw);
    }
}
