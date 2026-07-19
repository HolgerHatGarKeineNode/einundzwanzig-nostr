<?php

namespace App\Enums;

use ArchTech\Enums\From;
use ArchTech\Enums\InvokableCases;
use ArchTech\Enums\Meta\Meta;
use ArchTech\Enums\Metadata;
use ArchTech\Enums\Names;
use ArchTech\Enums\Options;
use ArchTech\Enums\Values;

/**
 * Abgeleiteter Status einer Projektunterstützung.
 *
 * Der Status wird nicht gespeichert, sondern aus den Vorstands-Stimmen und
 * dem ausgezahlten Betrag berechnet. Siehe ProjectProposal::status().
 */
#[Meta(Label::class, Color::class, Icon::class)]
enum ProjectProposalStatus: string
{
    use From;
    use InvokableCases;
    use Metadata;
    use Names;
    use Options;
    use Values;

    #[Label('In Abstimmung')] #[Color('zinc')] #[Icon('clock')]
    case InVoting = 'new';

    #[Label('Angenommen')] #[Color('blue')] #[Icon('check-circle')]
    case Accepted = 'accepted';

    #[Label('Abgelehnt')] #[Color('red')] #[Icon('x-circle')]
    case Rejected = 'rejected';

    #[Label('Unterstützt')] #[Color('green')] #[Icon('banknotes')]
    case Supported = 'supported';
}
