<?php

namespace Minormous\Dali\Entity\Casts;

use DateTimeImmutable;
use DateTimeInterface;
use Minormous\Dali\Entity\Interfaces\CastInterface;
use Webmozart\Assert\Assert;

/**
 * @template-implements CastInterface<\DateTimeInterface, int>
 */
final class TimestampCast implements CastInterface
{
    /**
     * @template TDateTime of DateTimeInterface
     * @psalm-param class-string<TDateTime> $dateTimeClass
     */
    public function __construct(
        private string $dateTimeClass = DateTimeImmutable::class,
    ) {
        Assert::isInstanceOf(
            $dateTimeClass,
            DateTimeInterface::class,
            'Declared DateTime class is not an instance of DateTimeInterface.'
        );
    }

    /**
     * @param \DateTimeInterface $value
     * @return int
     */
    public function toRaw($value): int
    {
        return $value->getTimestamp();
    }

    /**
     * @param int $raw
     * @return \DateTimeInterface
     */
    public function toValue($raw)
    {
        $dateTimeClass = $this->dateTimeClass;

        $dt = (new DateTimeImmutable())->setTimestamp($raw);

        /** @var \DateTimeInterface $dt */
        $dt = new $dateTimeClass($dt->format('Y-m-d H:i:s'));

        return $dt;
    }
}
