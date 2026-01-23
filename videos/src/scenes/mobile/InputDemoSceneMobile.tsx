import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, staticFile } from "remotion";
import { BitcoinEffect } from "../../components/BitcoinEffect";
import { AnimatedLogo } from "../../components/AnimatedLogo";

export const InputDemoSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width } = useVideoConfig();

  // Typing animation
  const typingText = "satoshi21";
  const typingProgress = interpolate(frame, [0, 2 * fps], [0, typingText.length], {
    extrapolateRight: "clamp",
  });
  const currentText = typingText.slice(0, Math.floor(typingProgress));

  // Cursor blink
  const cursorBlink = Math.floor(frame / 15) % 2 === 0;

  // Card entrance
  const cardSpring = spring({
    frame,
    fps,
    config: { damping: 200 },
  });
  const cardY = interpolate(cardSpring, [0, 1], [100, 0]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);

  // Rules box appears after typing
  const rulesSpring = spring({
    frame: frame - 2.5 * fps,
    fps,
    config: { damping: 200 },
  });
  const rulesOpacity = interpolate(rulesSpring, [0, 1], [0, 1]);
  const rulesY = interpolate(rulesSpring, [0, 1], [30, 0]);

  // Pointer animation - from left
  const pointerSpring = spring({
    frame: frame - 0.5 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const pointerX = interpolate(pointerSpring, [0, 1], [-120, 0]);
  const pointerOpacity = interpolate(pointerSpring, [0, 1], [0, 1]);

  return (
    <AbsoluteFill>
      {/* Tiled Wallpaper Background */}
      <div
        className="absolute inset-0"
        style={{
          backgroundImage: `url(${staticFile("einundzwanzig-wallpaper.png")})`,
          backgroundSize: `${width}px auto`,
          backgroundRepeat: "repeat-y",
          backgroundPosition: "center top",
        }}
      />
      <div className="absolute inset-0 bg-neutral-950/80" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      {/* Animated EINUNDZWANZIG Logo */}
      <div className="absolute top-12 left-1/2 -translate-x-1/2">
        <AnimatedLogo size={120} delay={0.5 * fps} />
      </div>

      <div className="absolute inset-0 flex flex-col items-center justify-center px-8">
        <h2 className="text-5xl font-bold text-white mb-12 text-center">
          Schritt 1: Handle eingeben
        </h2>

        {/* Input Card - Full Width */}
        <div
          className="bg-white rounded-3xl p-10 shadow-2xl w-full relative"
          style={{
            opacity: cardOpacity,
            transform: `translateY(${cardY}px)`,
          }}
        >
          {/* Animated Pointer - from left */}
          <div
            className="absolute -left-24 top-1/2 -translate-y-1/2 text-8xl"
            style={{
              transform: `translateY(-50%) translateX(${pointerX}px)`,
              opacity: pointerOpacity,
            }}
          >
            👉
          </div>

          <label className="block text-3xl font-medium text-neutral-700 mb-5">
            Dein NIP-05 Handle
          </label>
          <div className="bg-white rounded-2xl border-4 border-neutral-800 px-6 py-5 mb-8">
            <div className="text-4xl text-neutral-800 mb-2">
              {currentText}
              {cursorBlink && frame < 2.5 * fps && <span className="text-neutral-800">|</span>}
            </div>
            <div className="text-2xl text-neutral-600 font-medium">@einundzwanzig.space</div>
          </div>

          {/* Rules Box */}
          <div
            className="bg-neutral-100 rounded-2xl border-2 border-neutral-300 p-6"
            style={{
              opacity: rulesOpacity,
              transform: `translateY(${rulesY}px)`,
            }}
          >
            <p className="text-2xl text-neutral-700 font-medium mb-4">
              ✅ Regeln für dein Handle:
            </p>
            <ul className="text-xl text-neutral-600 space-y-2">
              <li>• Nur Kleinbuchstaben (a-z)</li>
              <li>• Zahlen (0-9)</li>
              <li>• Zeichen: "-" und "_"</li>
            </ul>
          </div>
        </div>

        {/* Info text - Footer */}
        <p
          className="text-neutral-200 text-center mt-12 text-3xl px-4 font-medium"
          style={{
            opacity: rulesOpacity,
          }}
        >
          Dein Handle wird automatisch kleingeschrieben und muss einzigartig sein.
        </p>
      </div>
    </AbsoluteFill>
  );
};
