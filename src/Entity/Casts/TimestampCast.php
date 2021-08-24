<?php

namespace Minormous\Dali\Entity\Casts;

use DateTimeImmutable;
use Minormous\Dali\Entity\Interfaces\CastInterface;

/**
 * @template-implements CastInterface<\DateTimeInterface, int>
 */
final class TimestampCast implements CastInterface
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
        return $value->getTimestamp();
    }

    public function toValue($raw)
    {
        $dateTimeClass = $this->dateTimeClass;

        $dt = new DateTimeImmutable();
        $dt->setTimestamp($raw);

        return new $dateTimeClass($dt->format('Y-m-d H:i:s'));
    }
}
