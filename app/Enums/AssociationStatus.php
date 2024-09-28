<?php

namespace App\Enums;

use ArchTech\Enums\From;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

#[Meta(Label::class, Color::class)]
enum AssociationStatus: int
{
    use InvokableCases;
    use Names;
    use Values;
    use Options;
    use Metadata;
    use From;

    #[Label('kein Mitglied')] #[Color('cyan')]
    case DEFAULT = 1;
    #[Label('Passiv')] #[Color('orange')]
    case PASSIVE = 2;
    #[Label('Aktiv')] #[Color('purple')]
    case ACTIVE = 3;
    #[Label('Ehrenmitglied')] #[Color('negative')]
    case HONORARY = 4;

    public static function selectOptions()
    {
        return collect(self::options())
            ->map(
                fn(
                    $option,
                    $name
                ) => [
                    'value' => $option,
                    'label' => __(
                        self::fromName($name)
                            ->label()
                    ),
                ]
            )
            ->values()
            ->toArray();
    }
}
