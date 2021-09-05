<?php

namespace Tests\Dali\Assets;

use Minormous\Dali\Entity\Traits\EntityTrait;
use Minormous\Metabolize\Dali\Attributes\Table;
use Minormous\Metabolize\Dali\Attributes\Column;
use Minormous\Metabolize\Dali\Attributes\Source;
use Minormous\Dali\Entity\Interfaces\EntityInterface;

#[Table('test')]
#[Source('test', ArrayRepository::class)]
class Entity implements EntityInterface
{
    use EntityTrait;

    #[Column(isIdentifier: true)]
    private int $id;

    #[Column]
    private string $something;

    public function getId(): int
    {
        return $this->id;
    }

    public function getSomething(): string
    {
        return $this->something;
    }
}
