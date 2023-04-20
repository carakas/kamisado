<?php

namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('kamisado')]
final class KamisadoComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public array $towers = self::DEFAULT_TOWERS;

    #[LiveProp(writable: true)]
    public int $activePlayer = self::DEFAULT_ACTIVE_PLAYER;

    #[LiveProp(writable: true)]
    public ?int $selectedLine = self::DEFAULT_SELECTED_LINE;

    #[LiveProp(writable: true)]
    public ?int $selectedColumn = self::DEFAULT_SELECTED_COLUMN;

    #[LiveProp(writable: true)]
    public ?int $winner = null;

    private const DEFAULT_ACTIVE_PLAYER = 1;
    private const DEFAULT_SELECTED_LINE = null;
    private const DEFAULT_SELECTED_COLUMN = null;
    private const DEFAULT_TOWERS = [
        0 => [0 => 11, 1 => 12, 2 => 13, 3 => 14, 4 => 15, 5 => 16, 6 => 17, 7 => 18],
        7 => [0 => 28, 1 => 27, 2 => 26, 3 => 25, 4 => 24, 5 => 23, 6 => 22, 7 => 21],
    ];
    private const BOARD = [
        [1, 2, 3, 4, 5, 6, 7, 8],
        [6, 1, 4, 7, 2, 5, 8, 3],
        [7, 4, 1, 6, 3, 8, 5, 2],
        [4, 3, 2, 1, 8, 7, 6, 5],
        [5, 6, 7, 8, 1, 2, 3, 4],
        [2, 5, 8, 3, 6, 1, 4, 7],
        [3, 8, 5, 2, 7, 4, 1, 6],
        [8, 7, 6, 5, 4, 3, 2, 1],
    ];

    public function hasTower(int $line, int $column): bool
    {
        return (bool) ($this->towers[$line][$column] ?? false);
    }

    public function isSelectableTile(int $line, int $column): bool
    {
        if ($this->winner) {
            return false;
        }

        if ($this->selectedLine === null) {
            return $this->hasTower($line, $column) && $this->getTowerPlayer($line, $column) === $this->activePlayer;
        }
        if ($this->selectedLine === $line && $this->selectedColumn === $column) {
            return false; // already selected
        }

        $angle = atan2($this->selectedLine - $line, $this->selectedColumn - $column) * 180 / M_PI;
        if ($this->activePlayer === 1 && $angle !== -135.0 && $angle !== -45.0 && $angle !== -90.0) {
            return false;
        }
        if ($this->activePlayer === 2 && $angle !== 135.0 && $angle !== 45.0 & $angle !== 90.0) {
            return false;
        }

        return $this->getTowerPlayer($line, $column) === 0;
    }

    public function getTowerPlayer(int $line, int $column): int
    {
        if (!$this->hasTower($line, $column)) {
            return 0;
        }

        return (int) floor($this->towers[$line][$column] / 10);
    }

    public function getTowerColour(int $line, int $column): int
    {
        if (!$this->hasTower($line, $column)) {
            return 0;
        }

        return $this->towers[$line][$column] % 10;
    }

    public function tileIsSelected(int $line, int $column): int
    {
        return $this->selectedLine === $line && $this->selectedColumn === $column;
    }

    public function getBoard(): array
    {
        return self::BOARD;
    }

    #[LiveAction]
    public function selectTile(#[LiveArg] int $line, #[LiveArg] int $column): void
    {
        if (!$this->isSelectableTile($line, $column)) {
            throw new \InvalidArgumentException('Cannot select this tile');
        }

        if ($this->selectedLine === null) {
            $this->selectedLine = $line;
            $this->selectedColumn = $column;

            return;
        }

        $this->moveTower($this->selectedLine, $this->selectedColumn, $line, $column);
    }

    private function getTileColour(int $line, int $column): int
    {
        return self::BOARD[$line][$column];
    }

    private function moveTower(?int $selectedLine, ?int $selectedColumn, int $line, int $column): void
    {
        $this->towers[$line][$column] = $this->towers[$selectedLine][$selectedColumn];
        $this->towers[$selectedLine][$selectedColumn] = null;

        if ($line === 0 || $line === 7) {
            $this->winner = $this->activePlayer;

            return;
        }

        $this->activePlayer = $this->activePlayer === 1 ? 2 : 1;
        $selectedColour = $this->getTileColour($line, $column);
        $this->selectForPlayerAndColour($this->activePlayer, $selectedColour);
    }

    private function selectForPlayerAndColour(int $activePlayer, int $selectedColour): void
    {
        $tower = $activePlayer * 10 + $selectedColour;
        foreach ($this->towers as $line => $columns) {
            foreach ($columns as $column => $value) {
                if ($value === $tower) {
                    $this->selectedLine = $line;
                    $this->selectedColumn = $column;

                    return;
                }
            }
        }
    }

    #[LiveAction]
    public function resetGame(): void
    {
        $this->towers = self::DEFAULT_TOWERS;
        $this->activePlayer = self::DEFAULT_ACTIVE_PLAYER;
        $this->selectedLine = self::DEFAULT_SELECTED_LINE;
        $this->selectedColumn = self::DEFAULT_SELECTED_COLUMN;
        $this->winner = null;
    }
}
