<?php

namespace App\Enums;

use ArchTech\Enums\From;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

#[Meta(Label::class, Color::class, Icon::class)]
enum NewsCategory: int
{
    use From;
    use InvokableCases;
    use Metadata;
    use Names;
    use Options;
    use Values;

    #[Label('Einundzwanzig')] #[Color('amber')] #[Icon('bitcoin-sign')]
    case Einundzwanzig = 1;

    #[Label('Allgemeines')] #[Color('zinc')] #[Icon('newspaper')]
    case Allgemeines = 2;

    #[Label('Organisation')] #[Color('cyan')] #[Icon('file-lines')]
    case Organisation = 3;

    #[Label('Bitcoin')] #[Color('orange')] #[Icon('coins')]
    case Bitcoin = 4;

    #[Label('Meetups')] #[Color('green')] #[Icon('users')]
    case Meetups = 5;

    #[Label('Bildung')] #[Color('blue')] #[Icon('graduation-cap')]
    case Bildung = 6;

    #[Label('Protokolle')] #[Color('purple')] #[Icon('clipboard-list')]
    case Protokolle = 7;

    #[Label('Finanzen')] #[Color('emerald')] #[Icon('chart-pie')]
    case Finanzen = 8;

    #[Label('Veranstaltungen')] #[Color('rose')] #[Icon('calendar-star')]
    case Veranstaltungen = 9;

    public static function selectOptions()
    {
        return collect(self::options())
            ->map(
                fn (
                    $option,
                    $name
                ) => [
                    'value' => $option,
                    'label' => __(
                        self::fromName($name)
                            ->label()
                    ),
                    'icon' => self::fromName($name)
                        ->icon(),
                ]
            )
            ->values()
            ->toArray();
    }
}
