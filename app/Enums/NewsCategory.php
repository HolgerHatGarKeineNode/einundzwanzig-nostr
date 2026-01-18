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

    #[Label('Organisation')] #[Color('cyan')] #[Icon('file-lines')]
    case ORGANISATION = 1;

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
