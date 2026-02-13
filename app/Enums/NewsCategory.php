<?php

namespace App\Enums;

use ArchTech\Enums\From;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

#[Meta(Label::class, Color::class, Icon::class, Emoji::class)]
enum NewsCategory: int
{
    use From;
    use InvokableCases;
    use Metadata;
    use Names;
    use Options;
    use Values;

    #[Label('Einundzwanzig')] #[Color('amber')] #[Icon('bitcoin-sign')] #[Emoji('₿')]
    case Einundzwanzig = 1;

    #[Label('Allgemeines')] #[Color('zinc')] #[Icon('newspaper')] #[Emoji('📋')]
    case Allgemeines = 2;

    #[Label('Organisation')] #[Color('cyan')] #[Icon('file-lines')] #[Emoji('📁')]
    case Organisation = 3;

    #[Label('Bitcoin')] #[Color('orange')] #[Icon('coins')] #[Emoji('🏠')]
    case Bitcoin = 4;

    #[Label('Meetups')] #[Color('green')] #[Icon('users')] #[Emoji('🎉')]
    case Meetups = 5;

    #[Label('Bildung')] #[Color('blue')] #[Icon('graduation-cap')] #[Emoji('📚')]
    case Bildung = 6;

    #[Label('Protokolle')] #[Color('purple')] #[Icon('clipboard-list')] #[Emoji('📝')]
    case Protokolle = 7;

    #[Label('Finanzen')] #[Color('emerald')] #[Icon('chart-pie')] #[Emoji('💰')]
    case Finanzen = 8;

    #[Label('Veranstaltungen')] #[Color('rose')] #[Icon('calendar-star')] #[Emoji('📅')]
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
                    'emoji' => self::fromName($name)
                        ->emoji(),
                ]
            )
            ->values()
            ->toArray();
    }
}
