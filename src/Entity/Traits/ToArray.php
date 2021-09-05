<?php

namespace Minormous\Dali\Entity\Traits;

trait ToArray
{
    public function toArray(): array
    {
        $data = [];
        foreach (\get_class_vars(\get_class($this)) as $propName => $x) {
            if (!isset($this->{$propName})) {
                continue;
            }
            $data[$propName] = $this->{$propName};
        }

        return $data;
    }
}
