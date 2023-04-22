<?php

namespace App\Twig\Components;

use App\Kamisado\Kamisado;
use InvalidArgumentException;
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

    public const AVAILABLE_THEMES = [
        'original',
        'space',
    ];

    #[LiveProp(writable: true)]
    public string $theme = 'original';

    #[LiveProp(writable: true)]
    #[ExposeInTemplate]
    public ?string $error = null;

    #[LiveProp(writable: true)]
    public bool $isStarted = false;

    #[LiveProp(writable: true)]
    public bool $hideModel = false;

    #[LiveProp( writable: true, hydrateWith: 'hydrateGame', dehydrateWith: 'dehydrateGame')]
    #[ExposeInTemplate]
    public Kamisado $game;

    #[LiveProp(writable: true)]
    public int $selectedBotPlayer = Kamisado::DEFAULT_BOT_PLAYER;

    public function mount(): void
    {
        $this->resetGame();
        $this->isStarted = false;
    }

    public function getBoard(): array
    {
        return Kamisado::BOARD;
    }

    public function getAvailableThemes(): array
    {
        return self::AVAILABLE_THEMES;
    }

    #[LiveAction]
    public function selectTile(#[LiveArg] int $line, #[LiveArg] int $column): void
    {
        try {
            $this->game->selectTile($line, $column);
            $this->game->executeBotMove();
        } catch (InvalidArgumentException) {
            $this->error = 'Invalid move: game state is not consistent. Please reset the game.<br><code>' . json_encode($this->game->getState()) . '</code>';
        }
    }

    #[LiveAction]
    public function resetGame(): void
    {
        $this->game = Kamisado::newGame($this->selectedBotPlayer);
        $this->isStarted = true;
        $this->error = null;
        $this->hideModel = false;
    }

    public function dehydrateGame(Kamisado $kamisado): array
    {
        return $kamisado->getState();
    }

    public function hydrateGame(array $data): Kamisado
    {
        return Kamisado::newGame(
            $data['botPlayer'],
            $data['towers'],
            $data['activePlayer'],
            $data['selectedLine'],
            $data['selectedColumn'],
            $data['winner']
        );
    }
}
