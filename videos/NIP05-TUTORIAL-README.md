# NIP-05 Tutorial Video - EINUNDZWANZIG

Eine 38-sekündige 3D-animierte Tutorial-Video, das zeigt, wie man ein Nostr NIP-05 Handle auf der EINUNDZWANZIG-Plattform erstellt.

## Video-Struktur

Das Video besteht aus 6 Szenen mit 3D-Elementen, Bitcoin-Effekten und animierten EINUNDZWANZIG Logos:

1. **IntroScene** (0-4s): Eröffnung mit animiertem EINUNDZWANZIG Square Logo und Titel
2. **UIShowcaseScene** (4-10s): Zeigt die NIP-05-Karten-Oberfläche mit animierten Logos in den Ecken
3. **InputDemoScene** (10-18s): Demonstriert die Handle-Eingabe mit animiertem Square Logo
4. **SaveButtonScene** (18-23s): Zeigt das Klicken des Speichern-Buttons mit Success-Logo
5. **VerificationScene** (23-28s): Zeigt erfolgreiche Verifizierung mit animiertem Logo und 3D-Schild
6. **OutroScene** (28-38s): **Verlängerte Szene (10 Sekunden)** mit großem Fokus auf `verein.einundzwanzig.space`

## Technische Details

- **Dauer**: 38 Sekunden (1140 Frames bei 30fps)
- **Auflösung**: 1920x1080 (Full HD)
- **Frame Rate**: 30fps
- **Verwendete Technologien**:
  - Remotion 4.0
  - React Three Fiber & Three.js für 3D-Elemente
  - Tailwind CSS 4.0 für Styling
  - TypeScript
  - Offizielles EINUNDZWANZIG Wallpaper und Logos

## Design-Highlights

### AnimatedLogo Komponente
Neue wiederverwendbare Komponente mit fancy Animationen:
- **EINUNDZWANZIG Square Logo** (einundzwanzig-square-inverted.svg)
- 3D rotierender Ring um das Logo
- Glüh-Effekt mit Pulsation
- Schwebende Animation (floating)
- 8 rotierende Partikel um das Logo
- Spring-basierte Skalierung und Rotation
- Vollständig anpassbare Größe und Verzögerung

### Wallpaper-Hintergrund
Jede Szene verwendet das offizielle EINUNDZWANZIG Wallpaper (`einundzwanzig-wallpaper.png`) als Hintergrund mit einem dunklen Overlay für bessere Lesbarkeit.

### Bitcoin-Effekt
Alle Szenen enthalten einen durchgängigen Bitcoin-Partikel-Effekt mit:
- 15 animierte Bitcoin-Symbole
- Fallende Animation mit Golden Ratio Verteilung
- Fade-in/Fade-out beim Ein-/Ausblenden
- Rotation während des Falls

### Farbschema
Verwendet ausschließlich die offiziellen Farben aus `app.css`:
- **Neutral/Zinc Töne**: neutral-50 bis neutral-950
- **Bitcoin Orange**: #f7931a (offizielle Bitcoin-Farbe)
- **Akzentfarben**: Entsprechend der EINUNDZWANZIG Brand Guidelines

### 3D-Elemente
- Animierte EINUNDZWANZIG Square Logos mit 3D-Ringen
- Geometrische Formen als Wireframes
- Metallische Bitcoin-farbene Materialien
- Animierte Schilde
- Glüh- und Partikel-Effekte

### EINUNDZWANZIG Branding
- **Square Logo** (einundzwanzig-square-inverted.svg) in allen Szenen
- **Horizontal Logo** (einundzwanzig-horizontal-inverted.svg) im Outro
- Offizielle Wallpaper durchgängig
- Kein ".e.V." oder "Deutschland" im Text - nur "EINUNDZWANZIG"

## Verwendung

### Preview im Remotion Studio
```bash
cd videos
npm run dev
```
Dann "Nip05Tutorial" aus der Compositions-Liste auswählen.

### Video rendern
```bash
npx remotion render Nip05Tutorial nip05-tutorial.mp4
```

### Mit benutzerdefinierten Einstellungen rendern
```bash
npx remotion render Nip05Tutorial nip05-tutorial.mp4 --codec=h264 --quality=90
```

## Anpassung

Das Video kann einfach angepasst werden durch Bearbeitung der Szenen-Dateien in `src/scenes/`:

- **Timing**: `durationInFrames` in `src/Nip05Tutorial.tsx` anpassen
- **Farben**: Neutral-Farben und #f7931a verwenden
- **Text**: Textinhalte in individuellen Szenen-Dateien ändern
- **Logo-Größe**: `size` prop in `<AnimatedLogo />` anpassen
- **Logo-Verzögerung**: `delay` prop in `<AnimatedLogo />` anpassen
- **Animations-Geschwindigkeit**: `spring()` Config-Werte und `interpolate()` Bereiche anpassen

## Szenen-Übersicht

### IntroScene
- Großes animiertes EINUNDZWANZIG Square Logo (350px)
- Spring-animierter Titel mit Skalierung und Opacity
- Verzögerte Untertitel-Animation
- Bitcoin-Partikel-Effekt
- Wallpaper-Hintergrund mit Overlay

### UIShowcaseScene
- Animierte Square Logos in beiden oberen Ecken (150px, halbtransparent)
- Wireframe 3D-Sphären und Oktaeder im Hintergrund
- Karten-Eingangs-Animation mit Bounce-Effekt
- Nachbau der tatsächlichen UI aus benefits.blade.php
- Neutral-Farbschema mit Bitcoin-Orange-Akzenten

### InputDemoScene
- Animiertes EINUNDZWANZIG Square Logo links oben (250px)
- Typewriter-Effekt für Handle-Eingabe
- Blinkender Cursor-Animation
- Animiertes Zeiger-Emoji
- Regel-Box gleitet nach Typing ein

### SaveButtonScene
- Animiertes EINUNDZWANZIG Logo erscheint bei Erfolg (280px)
- Animierte Cursor-Bewegung zum Button
- Button-Press-Effekt (Skalierung)
- Erfolgs-Nachricht mit Checkmark
- Neutral-800 Farbschema

### VerificationScene
- Großes animiertes EINUNDZWANZIG Logo oben (320px)
- 3D-Schild mit Rotation im Hintergrund
- Badge mit Eingangs-Animation
- Handle-Listen-Anzeige
- Schwebende Checkmark-Partikel

### OutroScene (10 Sekunden - mehr als doppelt so lang!)
- Animiertes EINUNDZWANZIG Square Logo oben (180px)
- EINUNDZWANZIG Horizontal SVG-Logo
- **HAUPTFOKUS: `verein.einundzwanzig.space`**
  - Riesige Schrift (text-7xl = 72px)
  - Orange Border (#f7931a)
  - Glüh-Effekt dahinter
  - Pulsations-Animation
  - 10 Sekunden sichtbar
  - Accent-Line darunter
- "Werde jetzt Mitglied!" Text
- Gestaffelte Animationen (Logo → SVG → CTA → URL → Footer)
- Vorteile-Footer (Nostr Relay, NIP-05, Lightning Watchtower)

## Assets

### Bilder
- `einundzwanzig-wallpaper.png` - Offizielles Hintergrund-Wallpaper

### SVG-Logos
- `einundzwanzig-square-inverted.svg` - Quadratisches Logo (NEU!)
- `einundzwanzig-horizontal-inverted.svg` - Horizontales Logo für Outro

### 3D-Elemente
Alle 3D-Elemente werden prozedural generiert mit Three.js:
- Torus Geometrie für Logo-Ringe
- Box, Sphere, Octahedron, Icosahedron für Hintergrund-Partikel
- Cylinder für Schild

## Komponenten

### BitcoinEffect
- 15 fallende Bitcoin-Symbole
- Golden Ratio Verteilung
- Fade-in/out Animationen
- Rotation

### AnimatedLogo (NEU!)
- EINUNDZWANZIG Square Logo Animation
- 3D rotierender Ring
- Glüh-Effekt mit Pulsation
- Floating Animation
- 8 rotierende Partikel
- Props: `size` (number), `delay` (frames)

## Farben-Referenz

Alle Farben entsprechen den EINUNDZWANZIG Brand Guidelines aus `app.css`:

```css
/* Hauptfarben */
--color-neutral-50 bis --color-neutral-950

/* Bitcoin Orange (offiziell) */
#f7931a

/* Akzent */
--color-accent: neutral-800 (light mode)
--color-accent: white (dark mode)
```

## Animation Best Practices

- Alle Animationen driven by `useCurrentFrame()` (keine CSS-Animationen)
- Spring-Physik für natürliche Bewegung
- Korrektes Sequencing mit `premountFor` für smoothes Laden
- Easing und Interpolation für glatte Übergänge
- 3D-Elemente synchronisiert mit Timeline via `useCurrentFrame()`

## Remotion Best Practices

Folgt allen Remotion Best Practices:
- ✅ Keine CSS-Animationen oder Tailwind-Animations-Klassen
- ✅ `ThreeCanvas` mit width und height props
- ✅ Keine `useFrame()` - nur `useCurrentFrame()`
- ✅ `layout="none"` für Sequences innerhalb ThreeCanvas
- ✅ Proper premounting für alle Sequences
- ✅ Spring-Konfigurationen für natürliche Bewegung
- ✅ Alle Animationen frame-basiert

## Performance

- Alle Assets optimiert
- 3D-Elemente verwenden niedrige Polygon-Counts wo möglich
- Bitcoin-Effekt mit moderater Partikel-Anzahl (15)
- Wiederverwendbare AnimatedLogo Komponente
- Effiziente Re-Renders durch proper React-Patterns

## Änderungen zur vorherigen Version

### ✨ Neu hinzugefügt:
- **AnimatedLogo Komponente** mit EINUNDZWANZIG Square Logo
- Fancy 3D-Ring-Animation um Logos
- Glüh-Effekte und Partikel
- Floating Animation für Logos

### 🔄 Ersetzt:
- ❌ 3D Keyboard Keys → ✅ AnimatedLogo
- ❌ 3D Checkmark Torus → ✅ AnimatedLogo
- ❌ Generic 3D shapes → ✅ Brand-specific Logo

### ⏱️ Verlängert:
- OutroScene: 4 Sekunden → **10 Sekunden** (2.5x länger!)
- Gesamtdauer: 32 Sekunden → **38 Sekunden**

### 📐 Vergrößert:
- URL in Outro: text-lg (18px) → **text-7xl (72px)** (4x größer!)
- URL Container mit Border und Glow-Effekt
- URL Pulsations-Animation für Aufmerksamkeit
