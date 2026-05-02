<?php

namespace Database\Seeders;

use App\Enums\NewsCategory;
use App\Models\EinundzwanzigPleb;
use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $markus = EinundzwanzigPleb::query()
            ->where('npub', 'npub17fqtu2mgf7zueq2kdusgzwr2lqwhgfl2scjsez77ddag2qx8vxaq3vnr8y')
            ->first() ?? EinundzwanzigPleb::query()->first();

        if (! $markus) {
            return;
        }

        $news = [
            [
                'name' => 'Generalversammlung 2026 - Save the Date',
                'description' => "Die nächste Generalversammlung findet am 21. Juni 2026 in Wien statt. Alle Mitglieder sind herzlich eingeladen.\n\nAgenda:\n- Bericht des Vorstands\n- Wahl des neuen Vorstands\n- Project Support Abstimmungen",
                'category' => NewsCategory::Veranstaltungen,
            ],
            [
                'name' => 'Neuer Lightning Watchtower verfügbar',
                'description' => 'Mitglieder können ab sofort unseren Watchtower nutzen. Details zur Konfiguration im Mitgliederbereich.',
                'category' => NewsCategory::Bitcoin,
            ],
            [
                'name' => 'Meetup-Welle im Sommer 2026',
                'description' => 'Über 30 Einundzwanzig Meetups im DACH-Raum geplant. Termine im Portal.',
                'category' => NewsCategory::Meetups,
            ],
            [
                'name' => 'Q1 2026 Finanzbericht',
                'description' => 'Der Finanzbericht für das erste Quartal 2026 ist im Mitgliederbereich abrufbar.',
                'category' => NewsCategory::Finanzen,
            ],
            [
                'name' => 'Neue Bildungsinitiative gestartet',
                'description' => 'Die Einundzwanzig Bitcoin Schule startet im September. Anmeldung ab sofort möglich.',
                'category' => NewsCategory::Bildung,
            ],
        ];

        foreach ($news as $item) {
            Notification::query()->create([
                ...$item,
                'einundzwanzig_pleb_id' => $markus->id,
            ]);
        }
    }
}
