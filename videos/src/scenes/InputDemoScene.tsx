import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, Img, staticFile } from "remotion";
import { BitcoinEffect } from "../components/BitcoinEffect";
import { AnimatedLogo } from "../components/AnimatedLogo";

export const InputDemoScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

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

  // Pointer animation
  const pointerSpring = spring({
    frame: frame - 0.5 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const pointerX = interpolate(pointerSpring, [0, 1], [-100, 0]);

  return (
    <AbsoluteFill>
      {/* Wallpaper Background */}
      <Img
        src={staticFile("einundzwanzig-wallpaper.png")}
        className="absolute inset-0 w-full h-full object-cover"
      />
      <div className="absolute inset-0 bg-neutral-950/75" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      {/* Animated EINUNDZWANZIG Logo */}
      <div className="absolute top-20 left-20">
        <AnimatedLogo size={250} delay={0.5 * fps} />
      </div>

      <div className="absolute inset-0 flex flex-col items-center justify-center px-12">
        <h2 className="text-5xl font-bold text-white mb-16 text-center">
          Schritt 1: Handle eingeben
        </h2>

        {/* Input Card */}
        <div
          className="bg-white rounded-2xl p-10 shadow-2xl max-w-3xl w-full relative"
          style={{
            opacity: cardOpacity,
            transform: `translateY(${cardY}px)`,
          }}
        >
          {/* Animated Pointer */}
          <div
            className="absolute -left-20 top-1/2 -translate-y-1/2 text-6xl"
            style={{
              transform: `translateX(${pointerX}px) translateY(-50%)`,
            }}
          >
            👉
          </div>

          <label className="block text-xl font-medium text-neutral-700 mb-4">
            Dein NIP-05 Handle
          </label>
          <div className="flex items-center bg-white rounded-lg border-4 border-neutral-800 px-6 py-4 mb-6">
            <div className="flex-1 text-2xl text-neutral-800">
              {currentText}
              {cursorBlink && frame < 2.5 * fps && <span className="text-neutral-800">|</span>}
            </div>
            <div className="text-xl text-neutral-600 font-medium">@einundzwanzig.space</div>
          </div>

          {/* Rules Box */}
          <div
            className="bg-neutral-100 rounded-lg border-2 border-neutral-300 p-5"
            style={{
              opacity: rulesOpacity,
              transform: `translateY(${rulesY}px)`,
            }}
          >
            <p className="text-sm text-neutral-700 font-medium mb-2">
              ✅ Regeln für dein Handle:
            </p>
            <ul className="text-sm text-neutral-600 space-y-1">
              <li>• Nur Kleinbuchstaben (a-z)</li>
              <li>• Zahlen (0-9)</li>
              <li>• Zeichen: "-" und "_"</li>
            </ul>
          </div>
        </div>

        {/* Info text */}
        <p
          className="text-neutral-300 text-center mt-8 text-lg max-w-2xl"
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
