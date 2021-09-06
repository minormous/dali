<?php

namespace Minormous\Dali\Entity\Casts;

use DateTimeImmutable;
use DateTimeInterface;
use Webmozart\Assert\Assert;
use Minormous\Dali\Entity\Interfaces\CastInterface;

/**
 * @template-implements CastInterface<\DateTimeInterface, string>
 */
final class DateTimeCast implements CastInterface
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
     * @return string
     */
    public function toRaw($value): string
    {
        return $value->format('Y-m-d H:i:s');
    }

    public function toValue($raw)
    {
        $dateTimeClass = $this->dateTimeClass;
        /** @var \DateTimeInterface $dt */
        $dt = new $dateTimeClass($raw);

        return $dt;
    }
}
