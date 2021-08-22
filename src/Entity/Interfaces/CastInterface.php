<?php

namespace Minormous\Dali\Entity\Interfaces;

/**
 * @template TType
 * @template TRaw
 */
interface CastInterface
{
    /**
     * @param TType $value
     * @return TRaw
     */
    public function toRaw($value);

    /**
     * @param TRaw $value
     * @return TType
     */
    public function toValue($raw);
}
