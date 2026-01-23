import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, Img, staticFile } from "remotion";
import { BitcoinEffect } from "../components/BitcoinEffect";
import { AnimatedLogo } from "../components/AnimatedLogo";

export const VerificationScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({
    frame,
    fps,
    config: { damping: 200 },
  });
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);

  // Badge entrance
  const badgeSpring = spring({
    frame: frame - 0.5 * fps,
    fps,
    config: { damping: 8 },
  });
  const badgeScale = interpolate(badgeSpring, [0, 1], [0, 1]);
  const badgeRotation = interpolate(badgeSpring, [0, 1], [-180, 0]);

  // Handle list
  const listSpring = spring({
    frame: frame - 1.5 * fps,
    fps,
    config: { damping: 200 },
  });
  const listOpacity = interpolate(listSpring, [0, 1], [0, 1]);
  const listY = interpolate(listSpring, [0, 1], [50, 0]);

  // Checkmark particles
  const particle1Spring = spring({
    frame: frame - 2 * fps,
    fps,
    config: { damping: 15 },
  });
  const particle1Y = interpolate(particle1Spring, [0, 1], [0, -100]);
  const particle1Opacity = interpolate(particle1Spring, [0, 0.7, 1], [0, 1, 0]);

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
      <div className="absolute top-16 left-3/4 -translate-x-1/2">
        <AnimatedLogo size={320} delay={fps} />
      </div>

      {/* Floating Checkmark Particles */}
      <div
        className="absolute top-1/3 left-1/4 text-6xl"
        style={{
          opacity: particle1Opacity,
          transform: `translateY(${particle1Y}px)`,
        }}
      >
        ✓
      </div>

      <div className="absolute inset-0 flex flex-col items-center justify-center px-12">
        <h2
          className="text-5xl font-bold text-white mb-16 text-center"
          style={{ opacity: titleOpacity }}
        >
          Verifizierung erfolgreich!
        </h2>

        {/* Success Badge */}
        <div
          className="bg-white rounded-2xl p-8 shadow-2xl mb-12"
          style={{
            opacity: badgeScale,
            transform: `scale(${badgeScale}) rotate(${badgeRotation}deg)`,
          }}
        >
          <div className="flex items-center gap-6">
            <div className="w-20 h-20 rounded-full bg-neutral-800 flex items-center justify-center">
              <span className="text-5xl text-white">✓</span>
            </div>
            <div>
              <p className="text-sm text-neutral-600 font-medium mb-1">Dein Handle ist aktiv:</p>
              <p className="text-3xl font-bold text-neutral-800">
                satoshi21@einundzwanzig.space
              </p>
            </div>
          </div>
        </div>

        {/* Handle List */}
        <div
          className="bg-white/10 backdrop-blur-sm rounded-xl p-8 border-2 border-white/20 max-w-2xl w-full"
          style={{
            opacity: listOpacity,
            transform: `translateY(${listY}px)`,
          }}
        >
          <p className="text-xl font-semibold text-white mb-4">Deine aktivierten Handles:</p>
          <div className="space-y-3">
            <div className="flex items-center gap-4 bg-white/10 rounded-lg px-5 py-3">
              <span className="text-lg text-white">satoshi21@einundzwanzig.space</span>
              <span className="bg-neutral-800 text-white text-xs font-bold px-3 py-1 rounded-full border-2 border-neutral-600">
                OK
              </span>
            </div>
          </div>
        </div>

        <p className="text-neutral-300 text-center mt-12 text-xl max-w-3xl" style={{ opacity: listOpacity }}>
          Dein NIP-05 Handle ist jetzt aktiv! Nostr-Clients zeigen ein Verifizierungs-Häkchen für dich an.
        </p>
      </div>
    </AbsoluteFill>
  );
};
