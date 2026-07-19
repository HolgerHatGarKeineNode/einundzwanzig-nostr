import { execFileSync } from 'node:child_process'

/**
 * Setzt den Chatraum-Vermerk eines Antrags in der Vorschau-Datenbank zurueck,
 * damit der Lauf wiederholbar ist.
 *
 * Der Raum auf dem Relay bleibt bestehen und wird bewusst NICHT geloescht: Die
 * Anlage ist idempotent ("already exists" gilt auf Create und Join als Erfolg),
 * und so prueft jeder weitere Lauf zusaetzlich genau diese Eigenschaft — ein
 * Wiederholungsversuch darf keinen zweiten Waisenraum erzeugen.
 */
export function resetRoom(slug, database) {
    execFileSync('sqlite3', [
        database,
        `UPDATE project_proposals SET nostr_group_h = NULL, nostr_group_created_at = NULL WHERE slug = '${slug}';`,
    ])
}
