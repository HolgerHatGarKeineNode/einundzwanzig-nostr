import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, staticFile } from "remotion";
import { BitcoinEffect } from "../../components/BitcoinEffect";
import { AnimatedLogo } from "../../components/AnimatedLogo";

export const UIShowcaseSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width } = useVideoConfig();

  const cardSpring = spring({
    frame,
    fps,
    config: { damping: 20, stiffness: 200 },
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.8, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);

  const titleSpring = spring({
    frame: frame - 0.3 * fps,
    fps,
    config: { damping: 200 },
  });
  const titleY = interpolate(titleSpring, [0, 1], [50, 0]);
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);

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
      <div className="absolute inset-0 bg-neutral-950/85" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      {/* Animated Logos */}
      <div className="absolute top-16 left-8 opacity-60">
        <AnimatedLogo size={140} delay={0.2 * fps} />
      </div>
      <div className="absolute top-16 right-8 opacity-60">
        <AnimatedLogo size={140} delay={0.4 * fps} />
      </div>

      {/* Main Content */}
      <div className="absolute inset-0 flex flex-col items-center justify-center px-8">
        <h2
          className="text-5xl font-bold text-white mb-12 text-center"
          style={{
            opacity: titleOpacity,
            transform: `translateY(${titleY}px)`,
          }}
        >
          Die NIP-05 Oberfläche
        </h2>

        {/* UI Card Mockup - Full Width */}
        <div
          className="bg-neutral-50 rounded-3xl p-10 border-4 border-neutral-200 shadow-2xl w-full"
          style={{
            opacity: cardOpacity,
            transform: `scale(${cardScale})`,
          }}
        >
          <div className="flex items-start gap-6 mb-8">
            <div className="w-20 h-20 rounded-full bg-neutral-800 flex items-center justify-center flex-shrink-0">
              <div className="text-white text-5xl">✓</div>
            </div>
            <div className="flex-1">
              <h3 className="text-4xl font-semibold text-neutral-800 mb-3">
                Get NIP-05 verified
              </h3>
              <p className="text-2xl text-neutral-600 leading-relaxed">
                Verifiziere deine Identität mit einem menschenlesbaren Nostr-Namen.
              </p>
            </div>
          </div>

          {/* Input Preview */}
          <div className="space-y-4">
            <label className="block text-2xl font-medium text-neutral-700">
              Dein NIP-05 Handle
            </label>
            <div className="flex items-center bg-white rounded-2xl border-4 border-neutral-300 px-6 py-5">
              <div className="flex-1 text-3xl text-neutral-400">dein-name</div>
              <div className="text-2xl text-neutral-600 font-medium">@einundzwanzig.space</div>
            </div>
          </div>
        </div>
      </div>
    </AbsoluteFill>
  );
};
