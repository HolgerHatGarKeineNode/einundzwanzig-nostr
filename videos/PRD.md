# PRD: Einundzwanzig Portal - Cinematic Presentation Video

## Executive Summary

**Produkt:** 90-Sekunden kinoreifes Präsentationsvideo des Einundzwanzig Portals
**Technologie:** Remotion 4.0.409 + React Three Fiber + Tailwind CSS v4
**Auflösung:** 1920x1080 (Desktop) + 1080x1920 (Mobile)
**Ziel:** Das Portal als lebendige, wachsende Bitcoin-Community-Infrastruktur präsentieren

---

## Vision

Das Video zeigt nicht einfach eine Website — es **enthüllt** eine Bewegung. Jede Animation verstärkt die Botschaft: Die deutschsprachige Bitcoin-Community ist organisiert, aktiv und wächst. Das Portal ist das Nervenzentrum.

---

## Verfügbare Assets

### Logos
- `public/einundzwanzig-square-inverted.svg` — Hauptlogo
- `public/einundzwanzig-horizontal-inverted.svg` — Horizontal Logo
- `public/bitcoin-logo.svg` — Bitcoin Symbol
- `public/einundzwanzig-wallpaper.png` — Background

### Meetup-Logos (100+)
- `public/logos/EinundzwanzigKempten.png`
- `public/logos/EinundzwanzigFrankfurtAmMain.png`
- `public/logos/EinundzwanzigSaarland.png`
- `public/logos/EinundzwanzigDarmstadt.png`
- `public/logos/EinundzwanzigTrier.png`
- ... (vollständige Liste in `public/logos/`)

### Audio
- `public/music/background-music.mp3`
- `public/sfx/` — 17 Sound Effects (logo-whoosh, typing, card-slide, etc.)

### Bestehende Komponenten (wiederverwendbar)
- `src/components/AnimatedLogo.tsx` — 3D Logo mit Glow
- `src/components/BitcoinEffect.tsx` — Fallende Bitcoin-Partikel
- `src/components/AudioManager.tsx` — Audio-Orchestrierung

---

## Szenen-Übersicht

| # | Szene | Dauer | Frames | Fokus |
|---|-------|-------|--------|-------|
| 1 | Logo Reveal | 6s | 0-180 | Brand Impact |
| 2 | Portal Title | 4s | 180-300 | Erwartung setzen |
| 3 | Dashboard Overview | 12s | 300-660 | Full Picture |
| 4 | Meine Meetups | 12s | 660-1020 | Persönliche Verbindung |
| 5 | Top Länder | 12s | 1020-1380 | Geografische Reichweite |
| 6 | Top Meetups | 10s | 1380-1680 | Community Chapters |
| 7 | Activity Feed | 10s | 1680-1980 | Live Pulse |
| 8 | Call to Action | 12s | 1980-2340 | Conversion |
| 9 | Outro | 12s | 2340-2700 | Brand Cement |

**Total: 90 Sekunden = 2700 Frames @ 30fps**

---

## Milestones

### Milestone 1: Foundation Setup
**Ziel:** Projektstruktur und Basis-Komponenten
**Deliverables:**
- [ ] Ordnerstruktur `src/scenes/portal/` erstellen
- [ ] Ordnerstruktur `src/components/portal/` erstellen
- [ ] `PortalPresentation.tsx` Hauptkomposition (Skeleton)
- [ ] Root.tsx mit neuen Compositions aktualisieren
- [ ] Basis-Test: Leere Composition rendert ohne Fehler

**Verifikation:** `npm run dev` → "PortalPresentation" auswählbar, schwarzer Bildschirm

---

### Milestone 2: Reusable UI Components
**Ziel:** Dashboard-Elemente als wiederverwendbare Komponenten
**Deliverables:**
- [ ] `StatsCounter.tsx` — Animierte Zahlen (0 → 204)
- [ ] `SparklineChart.tsx` — SVG Linien-Animation
- [ ] `MeetupCard.tsx` — Meetup-Karte mit Logo, Name, Location
- [ ] `CountryBar.tsx` — Land + Flag + User-Count + Bar
- [ ] `ActivityItem.tsx` — Aktivitäts-Feed Eintrag
- [ ] `DashboardSidebar.tsx` — Komplette Sidebar Recreation

**Verifikation:** Jede Komponente einzeln in einer Test-Composition sichtbar

---

### Milestone 3: Scene 1+2 — Intro & Title
**Ziel:** Kinoqualität Einstieg
**Deliverables:**
- [ ] `PortalIntroScene.tsx` — Logo Reveal (6s)
  - AnimatedLogo von 0 auf 100% mit Particles
  - Wallpaper Background mit Zoom
  - Audio: logo-whoosh → logo-reveal
- [ ] `PortalTitleScene.tsx` — Title Card (4s)
  - "EINUNDZWANZIG PORTAL" Typing-Animation
  - Subtitle Fade-in
  - Audio: typing → ui-appear

**Verifikation:** Frame 0-300 zeigt Logo-Animation + Titel

---

### Milestone 4: Scene 3 — Dashboard Overview
**Ziel:** Das vollständige Dashboard erscheint
**Deliverables:**
- [ ] `DashboardOverviewScene.tsx` (12s)
  - 3D Perspective Entrance (von oben, kippt zu flat)
  - Sidebar slides in (spring, von links)
  - Header animiert ein
  - Content Cards staggered reveal (5 Frames Delay)
  - Sidebar Badges zählen hoch (Meetups: 204, etc.)
  - Audio: card-slide (mehrfach, gestaffelt)

**Verifikation:** Frame 300-660 zeigt komplettes Dashboard

---

### Milestone 5: Scene 4 — Meine Meetups
**Ziel:** Persönliche Verbindung zum Nutzer
**Deliverables:**
- [ ] `MeetupShowcaseScene.tsx` (12s)
  - Camera zoom in auf "Meine nächsten Meetup Termine"
  - "Einundzwanzig Kempten" Karte hebt sich (3D Shadow)
  - Echtes Logo: `EinundzwanzigKempten.png`
  - Location + Datum animieren ein
  - "Meine Meetups" Liste: Kempten, Memmingen, Friedrichshafen
  - Action Buttons erscheinen
  - Audio: slide-in, badge-appear

**Verifikation:** Frame 660-1020 zeigt Meetup-Details

---

### Milestone 6: Scene 5 — Top Länder
**Ziel:** Geografische Reichweite visualisieren
**Deliverables:**
- [ ] `CountryStatsScene.tsx` (12s)
  - Smooth transition vom Dashboard
  - Länder erscheinen sequentiell:
    1. 🇩🇪 Germany — 458 User
    2. 🇦🇹 Austria — 59 User
    3. 🇨🇭 Switzerland — 34 User
    4. 🇱🇺 Luxembourg — 8 User
    5. 🇧🇬 Bulgaria — 7 User
    6. 🇪🇸 Spain — 3 User
  - Zahlen zählen hoch (StatsCounter)
  - Progress Bars füllen sich (spring)
  - SparklineCharts animieren
  - Audio: success-chime pro Land

**Verifikation:** Frame 1020-1380 zeigt Länder-Statistiken

---

### Milestone 7: Scene 6 — Top Meetups
**Ziel:** Lokale Chapters sind aktiv
**Deliverables:**
- [ ] `TopMeetupsScene.tsx` (10s)
  - Top Meetups Liste animiert:
    1. Einundzwanzig Saarland — 26 User
    2. Einundzwanzig Frankfurt am Main — 26 User
    3. Einundzwanzig Kempten — 20 User
    4. Einundzwanzig Pfalz — 17 User
    5. Einundzwanzig Trier — 15 User
  - Echte Logos aus public/logos/
  - SparklineCharts zeigen Wachstum
  - Hervorhebung des führenden Meetups
  - Audio: checkmark-pop sequentiell

**Verifikation:** Frame 1380-1680 zeigt Top Meetups

---

### Milestone 8: Scene 7 — Activity Feed
**Ziel:** Echtzeit Community-Puls
**Deliverables:**
- [ ] `ActivityFeedScene.tsx` (10s)
  - "Aktivitäten" Spalte im Fokus
  - Items sliden von rechts ein:
    - "Neuer Termin" Badge bounces
    - Meetup Name types out
    - Timestamp fades in
  - Stack-Effekt: Neue Items pushen alte nach unten
  - Events aus Screenshot:
    - Einundzwanzig Kempten (vor 13 Stunden)
    - Einundzwanzig Darmstadt (vor 21 Stunden)
    - Einundzwanzig Vulkaneifel (vor 2 Tagen)
    - BitcoinWalk Würzburg (vor 2 Tagen)
  - Audio: button-click pro Item

**Verifikation:** Frame 1680-1980 zeigt Activity Feed

---

### Milestone 9: Scene 8 — Call to Action
**Ziel:** Conversion
**Deliverables:**
- [ ] `CallToActionScene.tsx` (12s)
  - Dashboard blurt + zoomt leicht raus
  - Overlay erscheint mit Glassmorphism
  - "Werde Teil der Community" — spring entrance
  - URL types: `portal.einundzwanzig.space`
  - URL pulst orange
  - Optional: QR Code materializes
  - EINUNDZWANZIG Logo center mit Glow
  - Audio: success-fanfare

**Verifikation:** Frame 1980-2340 zeigt CTA

---

### Milestone 10: Scene 9 — Outro
**Ziel:** Brand Cement
**Deliverables:**
- [ ] `PortalOutroScene.tsx` (12s)
  - Fade to wallpaper background
  - Horizontal Logo fade in (center)
  - BitcoinEffect particles
  - "Einundzwanzig e.V." text
  - Background music fade out (3s)
  - Audio: final-chime

**Verifikation:** Frame 2340-2700 zeigt Outro

---

### Milestone 11: Audio Integration
**Ziel:** Vollständige Audio-Synchronisation
**Deliverables:**
- [ ] `PortalAudioManager.tsx` — Audio für alle Szenen
  - Background Music: Frame 0-2700, fade in/out
  - SFX Timeline synchronisiert mit Szenen
- [ ] Audio-Test: Volle Composition mit Sound

**Verifikation:** Audio spielt korrekt zu allen Animationen

---

### Milestone 12: Mobile Version
**Ziel:** 1080x1920 Portrait-Variante
**Deliverables:**
- [ ] `PortalPresentationMobile.tsx`
- [ ] Mobile Szenen-Adaptionen (Layout-Anpassungen)
- [ ] Mobile in Root.tsx registriert

**Verifikation:** Mobile Composition rendert korrekt

---

### Milestone 13: Polish & Final Render
**Ziel:** Produktionsreif
**Deliverables:**
- [ ] Timing Fine-Tuning aller Übergänge
- [ ] Frame-genaue Audio-Sync Überprüfung
- [ ] Test-Render: `npx remotion render PortalPresentation --frames=0-300`
- [ ] Full Render: `npx remotion render PortalPresentation`
- [ ] Mobile Render: `npx remotion render PortalPresentationMobile`

**Verifikation:** MP4 Dateien in `/out` ohne Fehler

---

## Technische Architektur

### Dateistruktur (Final)
```
src/
├── PortalPresentation.tsx              # Desktop Composition
├── PortalPresentationMobile.tsx        # Mobile Composition
├── components/
│   ├── AnimatedLogo.tsx                # (existing)
│   ├── BitcoinEffect.tsx               # (existing)
│   ├── AudioManager.tsx                # (existing)
│   └── portal/
│       ├── StatsCounter.tsx
│       ├── SparklineChart.tsx
│       ├── MeetupCard.tsx
│       ├── CountryBar.tsx
│       ├── ActivityItem.tsx
│       └── DashboardSidebar.tsx
├── scenes/
│   ├── IntroScene.tsx                  # (existing)
│   └── portal/
│       ├── PortalIntroScene.tsx
│       ├── PortalTitleScene.tsx
│       ├── DashboardOverviewScene.tsx
│       ├── MeetupShowcaseScene.tsx
│       ├── CountryStatsScene.tsx
│       ├── TopMeetupsScene.tsx
│       ├── ActivityFeedScene.tsx
│       ├── CallToActionScene.tsx
│       └── PortalOutroScene.tsx
└── Root.tsx                            # +2 Compositions
```

### Animation Patterns

**Spring Configs (wiederverwendbar):**
```tsx
const SMOOTH = { damping: 200 };           // Sanft, kein Bounce
const SNAPPY = { damping: 20, stiffness: 200 }; // Schnell, minimal Bounce
const BOUNCY = { damping: 12 };            // Verspielt, Bounce
```

**Staggered Reveal:**
```tsx
const STAGGER_DELAY = 5; // Frames zwischen Items
items.map((item, i) => {
  const itemSpring = spring({
    frame: frame - (i * STAGGER_DELAY),
    fps,
    config: SNAPPY,
  });
  // ...
});
```

**3D Perspective Entrance:**
```tsx
const perspectiveX = interpolate(frame, [0, 60], [30, 0], { extrapolateRight: 'clamp' });
const zoom = interpolate(frame, [0, 60], [0.85, 1], { extrapolateRight: 'clamp' });
style={{
  transform: `perspective(1000px) rotateX(${perspectiveX}deg) scale(${zoom})`,
}}
```

---

## Erfolgskriterien

1. **Technisch:** Alle Milestones abgeschlossen, keine Render-Fehler
2. **Visuell:** Jede Szene hat mindestens 2 aktive Animationen
3. **Audio:** Jeder Szenenübergang hat korrespondierenden Sound
4. **Performance:** Render-Zeit < 5 Minuten für Full HD
5. **Qualität:** Video wirkt professionell, nicht "zusammengestückelt"

---

## Risiken & Mitigations

| Risiko | Mitigation |
|--------|------------|
| Logo-Formate inkonsistent (jpg/png/svg) | Img-Komponente mit fallback handling |
| Performance bei vielen Animationen | Premounting für smooth transitions |
| Audio Sync drift | Frame-genaue Sequence-Platzierung |
| Mobile Layout bricht | Separate Scene-Varianten pro Format |

---

## Nächster Schritt

Nach Genehmigung dieser PRD:
1. Milestone 1 beginnen: Foundation Setup
