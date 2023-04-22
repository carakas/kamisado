<?php

namespace App\Kamisado;

use InvalidArgumentException;

final class Kamisado
{
    public const DEFAULT_ACTIVE_PLAYER = 1;
    public const DEFAULT_SELECTED_LINE = null;
    public const DEFAULT_SELECTED_COLUMN = null;
    public const DEFAULT_TOWERS = [
        0 => [0 => 11, 1 => 12, 2 => 13, 3 => 14, 4 => 15, 5 => 16, 6 => 17, 7 => 18],
        7 => [0 => 28, 1 => 27, 2 => 26, 3 => 25, 4 => 24, 5 => 23, 6 => 22, 7 => 21],
    ];
    public const BOARD = [
        [1, 2, 3, 4, 5, 6, 7, 8],
        [6, 1, 4, 7, 2, 5, 8, 3],
        [7, 4, 1, 6, 3, 8, 5, 2],
        [4, 3, 2, 1, 8, 7, 6, 5],
        [5, 6, 7, 8, 1, 2, 3, 4],
        [2, 5, 8, 3, 6, 1, 4, 7],
        [3, 8, 5, 2, 7, 4, 1, 6],
        [8, 7, 6, 5, 4, 3, 2, 1],
    ];

    private function __construct (
        private array $towers,
        private int $activePlayer,
        private ?int $selectedLine,
        private ?int $selectedColumn,
        private ?int $winner,
    ) {
    }

    public static function newGame(
        array $towers = self::DEFAULT_TOWERS,
        int $activePlayer = self::DEFAULT_ACTIVE_PLAYER,
        ?int $selectedLine = self::DEFAULT_SELECTED_LINE,
        ?int $selectedColumn = self::DEFAULT_SELECTED_COLUMN,
        ?int $winner = null,
    ): self {
        return new self($towers, $activePlayer, $selectedLine, $selectedColumn, $winner);
    }

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

        if ($this->getTowerPlayer($line, $column) !== 0) {
            return false;
        }

        return $this->lineOfSightIsFree($line, $column);
    }

    public function getSelectableTiles(): array
    {
        $selectableTiles = [];
        foreach (self::BOARD as $line => $columns) {
            foreach ($columns as $column => $value) {
                if ($this->isSelectableTile($line, $column)) {
                    $selectableTiles[] = [$line, $column];
                }
            }
        }

        return $selectableTiles;
    }

    public function getTowerPlayer(int $line, int $column): int
    {
        if (!$this->hasTower($line, $column)) {
            return 0;
        }

        return (int) floor($this->towers[$line][$column] / 10);
    }

    public function selectTile(int $line, int $column): void
    {
        if (!$this->isSelectableTile($line, $column)) {
            throw new InvalidArgumentException('Cannot select this tile');
        }

        if ($this->selectedLine === null) {
            $this->selectedLine = $line;
            $this->selectedColumn = $column;

            return;
        }

        $this->moveTower($this->selectedLine, $this->selectedColumn, $line, $column);
    }

    public function getTileColour(int $line, int $column): int
    {
        return self::BOARD[$line][$column];
    }

    public function tileIsSelected(int $line, int $column): int
    {
        return $this->selectedLine === $line && $this->selectedColumn === $column;
    }

    public function getTowerColour(int $line, int $column): int
    {
        if (!$this->hasTower($line, $column)) {
            return 0;
        }

        return $this->towers[$line][$column] % 10;
    }

    public function getWinner(): ?int
    {
        return $this->winner;
    }

    public function getState(): array
    {
        return [
            'towers' => $this->towers,
            'activePlayer' => $this->activePlayer,
            'selectedLine' => $this->selectedLine,
            'selectedColumn' => $this->selectedColumn,
            'winner' => $this->winner,
        ];
    }

    private function lineOfSightIsFree(int $line, int $column): bool
    {
        $angle = abs(atan2($this->selectedLine - $line, $this->selectedColumn - $column) * 180 / M_PI);
        if ($angle === 90.0) {
            $step = $this->selectedLine > $line ? -1 : 1;
            for ($i = $this->selectedLine + $step; $i !== $line; $i += $step) {
                if ($this->hasTower($i, $column)) {
                    return false;
                }
            }

            return true;
        }

        if ($angle === 45.0) {
            $stepLine = $this->selectedLine > $line ? -1 : 1;
            $stepColumn = $this->selectedColumn > $column ? -1 : 1;
            for ($i = $this->selectedLine + $stepLine, $j = $this->selectedColumn + $stepColumn; $i !== $line; $i += $stepLine, $j += $stepColumn) {
                if ($this->hasTower($i, $j)) {
                    return false;
                }
            }

            return true;
        }
        if ($angle === 135.0) {
            $stepLine = $this->selectedLine > $line ? -1 : 1;
            $stepColumn = $this->selectedColumn > $column ? 1 : -1;
            for ($i = $this->selectedLine + $stepLine, $j = $this->selectedColumn - $stepColumn; $i !== $line; $i += $stepLine, $j -= $stepColumn) {
                if ($this->hasTower($i, $j)) {
                    return false;
                }
            }

            return true;
        }

        throw new InvalidArgumentException('Invalid angle');
    }

    private function moveTower(int $selectedLine, int $selectedColumn, int $line, int $column): void
    {
        if (!$this->isSelectableTile($line, $column)) {
            throw new InvalidArgumentException('Cannot select this tile');
        }

        $this->towers[$line][$column] = $this->towers[$selectedLine][$selectedColumn];
        $this->towers[$selectedLine][$selectedColumn] = null;

        if ($line === 0 || $line === 7) {
            $this->winner = $this->activePlayer;

            return;
        }

        $this->activePlayer = $this->activePlayer === 1 ? 2 : 1;
        $this->selectForPlayerAndColour($this->activePlayer, $this->getTileColour($line, $column));
    }

    private function selectForPlayerAndColour(int $activePlayer, int $selectedColour): void
    {
        $tower = $activePlayer * 10 + $selectedColour;
        foreach ($this->towers as $line => $columns) {
            foreach ($columns as $column => $value) {
                if ($value === $tower) {
                    $this->selectedLine = $line;
                    $this->selectedColumn = $column;
                    $this->verifySelectedTowerCanMove();

                    return;
                }
            }
        }

        throw new InvalidArgumentException('No tower found for player and colour');
    }

    private function verifySelectedTowerCanMove(): void
    {
        if ($this->selectedLine === null) {
            throw new InvalidArgumentException('No tower selected');
        }

        foreach (self::BOARD as $line => $columns) {
            foreach ($columns as $column => $value) {
                if ($this->isSelectableTile($line, $column)) {
                    return;
                }
            }
        }

        $this->activePlayer = $this->activePlayer === 1 ? 2 : 1;
        $this->selectForPlayerAndColour($this->activePlayer, $this->getTileColour($this->selectedLine, $this->selectedColumn));
    }
}
