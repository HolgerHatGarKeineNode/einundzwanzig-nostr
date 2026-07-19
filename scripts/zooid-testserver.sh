#!/usr/bin/env bash
#
# Lokaler zooid-Relay fuer die Chatraum-Tests der Projektunterstuetzungen.
#
# Bildet den Prod-Relay group.einundzwanzig.space nach, damit die NIP-29-Anlage
# einmal vollstaendig gegen ein echtes Gate gelaufen ist, bevor sie Prod
# beruehrt — dort abgesetzte Gruppen-Events sind nicht zurueckholbar.
#
# Unterschied zur Vorlage in einundzwanzig-group (tests/e2e/support/
# zooid-testserver.sh): dort steht public_read = true, obwohl der Kommentar
# daneben "nichts oeffentlich, so testen wir mit echtem Gate" behauptet. Wer
# Vertraulichkeit dagegen prueft, prueft die falsche Welt. Hier ist es false.
#
# Ports: 3334 ist der Mitschau-zooid und bleibt UNBERUEHRT, 3335-3340 gehoeren
# den Playwright-Workern von einundzwanzig-group. Wir nehmen 3341.
#
# Nutzung:  ./scripts/zooid-testserver.sh [start|stop|status|seed]
set -euo pipefail

ZOOID_DIR=/home/user/Code/zooid
PORT="${ZOOID_PORT:-3341}"
WORK="${ZOOID_WORK:-/tmp/zooid-verein-$PORT}"
DATA="$WORK/data"
CONFIG="$WORK/config"
LOG="$WORK/relay.log"
PIDFILE="$WORK/relay.pid"
R="ws://localhost:$PORT"
HTTP="http://localhost:$PORT"

# Wegwerf-Schluessel, ausschliesslich lokal. NIEMALS gegen Prod verwenden.
OWNER_SEC=6595bf88719dd46d6a06d3220c1b492398351906850dd024d2f54ac65a3592c5
OWNER_PUB=c43fd9f4e31a3969b7b2d5dc72a4360a4a8a3ed140a84acb1046210a1954b260
# "Vorstandsmitglied": hat can_manage und legt damit Raeume an (kind 9007/9000).
# Der ECHTE Vorstands-Pubkey; signiert wird per NIP-46 (Amber) aus der .env,
# ein Secret liegt hier bewusst nicht.
BOARD_PUB=0adf67475ccc5ca456fd3022e46f5d526eb0af6284bf85494c0dd7847f3e5033
# "Antragsteller": normales Mitglied, per allowpubkey zugelassen.
SUBMITTER_SEC=b7550c0b4c20e479e317ce4fb9bb5c144577f772fce12e48815fb5d71c637781
SUBMITTER_PUB=424f10e956aee6f5c3a92206f59077d576e7e5f628c1b06e8da1b229018c47f6

nak_bin() { command -v nak || echo "$HOME/go/bin/nak"; }
NAK=$(nak_bin)

write_config() {
    mkdir -p "$CONFIG" "$DATA"
    cat > "$CONFIG/test.toml" <<TOML
host = "localhost:$PORT"
schema = "verein_test"
secret = "$OWNER_SEC"
inactive = false

[info]
pubkey = "$OWNER_PUB"
name = "Verein Test Space"
description = "lokaler Relay fuer die Chatraum-Tests"

# Wie der Prod-Relay: nichts oeffentlich lesbar, kein Self-Join. Zugang nur
# ueber allowpubkey (NIP-86) durch einen Admin.
[policy]
public_read = false
public_write = false
public_join = false
strip_signatures = false

[groups]
enabled = true

[management]
enabled = true

# Der Vorstand ist relay-weit Admin — genau wie in Prod. Nur damit werden
# kind 9007 (Raum anlegen) und kind 9000 (Mitglied aufnehmen) akzeptiert,
# siehe zooid/groups.go:357-359 in Verbindung mit config.go:260-272.
[roles.vorstand]
pubkeys = ["$BOARD_PUB"]
can_invite = true
can_manage = true
TOML
}

is_up() { timeout 5 curl -sf -H 'Accept: application/nostr+json' "$HTTP" >/dev/null 2>&1; }

start() {
    if is_up; then
        echo "→ zooid laeuft bereits auf $PORT"
        return 0
    fi
    write_config
    # Alle drei Standard-Deskriptoren umlenken, nicht nur stdout/stderr des
    # Relays: Erbt der Hintergrundprozess sie, wartet ein aufrufendes Werkzeug
    # (CI, Agent-Shell) auf deren EOF und der Start "haengt", obwohl der Relay laeuft.
    (
        cd "$ZOOID_DIR" || exit 1
        setsid env PORT="$PORT" DATA="$DATA" CONFIG="$CONFIG" \
            ./bin/zooid </dev/null >"$LOG" 2>&1 &
        echo $! > "$PIDFILE"
    ) </dev/null >/dev/null 2>&1
    for _ in $(seq 1 40); do
        is_up && break
        sleep 0.25
    done
    if is_up; then
        echo "→ zooid gestartet auf $PORT (PID $(cat "$PIDFILE"), Log $LOG)"
    else
        echo "✗ zooid kam nicht hoch — Log:" >&2
        tail -20 "$LOG" >&2
        return 1
    fi
}

# NIP-86 allowpubkey: HTTP-Auth per kind-27235-Event, base64 im Authorization-Header.
allow_pubkey() {
    local pk="$1" body evt auth
    body="{\"method\":\"allowpubkey\",\"params\":[\"$pk\"]}"
    evt=$("$NAK" event -k 27235 --sec "$OWNER_SEC" \
        -t u="$HTTP" -t method=POST \
        -t payload="$(printf '%s' "$body" | sha256sum | cut -d' ' -f1)" 2>/dev/null)
    auth=$(printf '%s' "$evt" | base64 -w0)
    curl -s -X POST "$HTTP" \
        -H 'Content-Type: application/nostr+json+rpc' \
        -H "Authorization: Nostr $auth" -d "$body" >/dev/null
}

seed() {
    echo "→ Mitglieder zulassen (allowpubkey)"
    allow_pubkey "$BOARD_PUB"
    allow_pubkey "$SUBMITTER_PUB"
    echo "  Vorstand:      $BOARD_PUB"
    echo "  Antragsteller: $SUBMITTER_PUB"
}

status() {
    if is_up; then
        echo "→ laeuft auf $PORT"
        timeout 5 curl -sf -H 'Accept: application/nostr+json' "$HTTP" | head -c 200
        echo
    else
        echo "→ laeuft nicht"
    fi
}

stop() {
    if [ -f "$PIDFILE" ]; then
        local pid
        pid=$(cat "$PIDFILE")
        # Nur beenden, was wir selbst gestartet haben.
        if [ -d "/proc/$pid" ] && tr '\0' ' ' < "/proc/$pid/cmdline" | grep -q zooid; then
            kill "$pid" 2>/dev/null && echo "→ zooid ($pid) beendet"
        fi
        rm -f "$PIDFILE"
    else
        echo "→ keine PID-Datei, nichts zu beenden"
    fi
}

case "${1:-start}" in
    start) start && seed ;;
    seed) seed ;;
    status) status ;;
    stop) stop ;;
    *) echo "Nutzung: $0 [start|stop|status|seed]" >&2; exit 1 ;;
esac
