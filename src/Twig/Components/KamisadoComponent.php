<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('kamisado')]
final class KamisadoComponent
{
    use DefaultActionTrait;

    private array $towers = [
        0 => [ 0 => 11, 1 => 12, 2 => 13, 3 => 14, 4 => 15, 5 => 16, 6 => 17, 7 => 18 ],
        7 => [ 0 => 28, 1 => 27, 2 => 26, 3 => 25, 4 => 24, 5 => 23, 6 => 22, 7 => 21 ],
    ];

    const BOARD = [
        [1,2,3,4,5,6,7,8],
        [6,1,4,7,2,5,8,3],
        [7,4,1,6,3,8,5,2],
        [4,3,2,1,8,7,6,5],
        [5,6,7,8,1,2,3,4],
        [2,5,8,3,6,1,4,7],
        [3,8,5,2,7,4,1,6],
        [8,7,6,5,4,3,2,1],
    ];

    public function hasTower(int $line, int $column): bool
    {
        return (bool) ($this->towers[$line][$column] ?? false);
    }

    public function isSelectableTile(int $line, int $column): bool
    {
        return true;
        return ! $this->hasTower($line, $column);
    }

    public function getTowerPlayer(int $line, int $column): int
    {
        if (! $this->hasTower($line, $column)) {
            throw new \InvalidArgumentException('No tower at this position');
        }

        return (int) floor($this->towers[$line][$column] / 10);
    }

    public function getTowerColour(int $line, int $column): int
    {
        if (! $this->hasTower($line, $column)) {
            throw new \InvalidArgumentException('No tower at this position');
        }

        return $this->towers[$line][$column] % 10;
    }

    public function isInitiated(): bool
    {
        return count($this->towers) === 16;
    }

    public function getBoard(): array
    {
        return self::BOARD;
    }
}
