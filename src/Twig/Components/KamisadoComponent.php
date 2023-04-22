<?php

namespace App\Twig\Components;

use App\Kamisado\Kamisado;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

#[AsLiveComponent('kamisado')]
final class KamisadoComponent
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $theme = 'original';

    #[LiveProp(writable: true)]
    public bool $isStarted = false;

    #[LiveProp( writable: true, hydrateWith: 'hydrateGame', dehydrateWith: 'dehydrateGame')]
    #[ExposeInTemplate('game')]
    public Kamisado $game;

    public function mount(): void
    {
        $this->resetGame();
        $this->isStarted = false;
    }

    public function getBoard(): array
    {
        return Kamisado::BOARD;
    }

    #[LiveAction]
    public function selectTile(#[LiveArg] int $line, #[LiveArg] int $column): void
    {
        $this->game->selectTile($line, $column);
    }

    #[LiveAction]
    public function resetGame(): void
    {
        $this->game = Kamisado::newGame();
        $this->isStarted = true;
    }

    public function dehydrateGame(Kamisado $kamisado): array
    {
        return $kamisado->getState();
    }

    public function hydrateGame(array $data): Kamisado
    {
        return Kamisado::newGame(
            $data['towers'],
            $data['activePlayer'],
            $data['selectedLine'],
            $data['selectedColumn'],
            $data['winner']
        );
    }
}
