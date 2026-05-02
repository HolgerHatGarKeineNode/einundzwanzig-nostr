<?php

namespace Database\Seeders;

use App\Models\EinundzwanzigPleb;
use App\Models\ProjectProposal;
use App\Models\Vote;
use Illuminate\Database\Seeder;

class ProjectProposalSeeder extends Seeder
{
    /**
     * @var array<int, array{name:string, description:string, support_in_sats:int, website:string, accepted:bool}>
     */
    private array $proposals = [
        [
            'name' => 'Einundzwanzig Portal Refactoring',
            'description' => "Das Einundzwanzig Portal benötigt ein größeres Refactoring, um die Performance zu verbessern und neue Features wie Meetup-Anmeldung über Nostr DMs zu ermöglichen.\n\n**Geplante Änderungen:**\n- Migration auf Livewire 4\n- Nostr Login per NIP-46\n- Meetup Kalender als ICS-Feed\n- API für externe Tools",
            'support_in_sats' => 2_100_000,
            'website' => 'https://github.com/einundzwanzig-portal/einundzwanzig-portal',
            'accepted' => true,
        ],
        [
            'name' => 'Bitcoin Schule für Plebs',
            'description' => "Curriculum für eine deutschsprachige Bitcoin-Schule mit modular aufgebauten Kursen für Einsteiger und Fortgeschrittene.\n\n- Onboarding für totale Beginner\n- Self Custody Workshops\n- Lightning Network Hands-on\n- Privacy & Coinjoin Module",
            'support_in_sats' => 1_500_000,
            'website' => 'https://einundzwanzig.school',
            'accepted' => true,
        ],
        [
            'name' => 'Lightning Watchtower für Mitglieder',
            'description' => 'Hosting eines Lightning Watchtower Service exklusiv für Einundzwanzig Mitglieder, um Channel-Verlust durch böswillige Counterparties zu verhindern.',
            'support_in_sats' => 500_000,
            'website' => 'https://einundzwanzig.space/benefits',
            'accepted' => false,
        ],
        [
            'name' => 'Nostr Relay Hosting',
            'description' => 'Betrieb eines schnellen Nostr Relay (`wss://simple-relay.codingarena.top`) für die Einundzwanzig Community. Optimiert für deutschsprachige Inhalte und Meetup-Events.',
            'support_in_sats' => 800_000,
            'website' => 'https://simple-relay.codingarena.top',
            'accepted' => true,
        ],
        [
            'name' => 'Meetup Sticker Druck Q3 2026',
            'description' => 'Bestellung von 5000 Einundzwanzig Stickern für die Meetups im DACH-Raum. Verteilung über die lokalen Meetup Organisatoren.',
            'support_in_sats' => 210_000,
            'website' => 'https://einundzwanzig.space',
            'accepted' => false,
        ],
    ];

    public function run(): void
    {
        $plebs = EinundzwanzigPleb::query()->get();
        if ($plebs->isEmpty()) {
            return;
        }

        $markus = EinundzwanzigPleb::query()
            ->where('npub', 'npub17fqtu2mgf7zueq2kdusgzwr2lqwhgfl2scjsez77ddag2qx8vxaq3vnr8y')
            ->first();

        foreach ($this->proposals as $index => $data) {
            $pleb = $markus && $index < 2 ? $markus : $plebs->random();

            $proposal = ProjectProposal::query()->create([
                'einundzwanzig_pleb_id' => $pleb->id,
                'name' => $data['name'],
                'description' => $data['description'],
                'support_in_sats' => $data['support_in_sats'],
                'website' => $data['website'],
                'accepted' => $data['accepted'],
                'sats_paid' => $data['accepted'] ? $data['support_in_sats'] : null,
            ]);

            foreach ($plebs->random(min($plebs->count(), 6)) as $voter) {
                Vote::query()->updateOrCreate(
                    [
                        'einundzwanzig_pleb_id' => $voter->id,
                        'project_proposal_id' => $proposal->id,
                    ],
                    [
                        'value' => fake()->boolean(70),
                        'reason' => fake()->optional(0.4)->sentence(),
                    ]
                );
            }
        }
    }
}
