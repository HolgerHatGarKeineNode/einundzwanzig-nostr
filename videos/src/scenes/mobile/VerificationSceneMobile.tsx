import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, staticFile } from "remotion";
import { BitcoinEffect } from "../../components/BitcoinEffect";
import { AnimatedLogo } from "../../components/AnimatedLogo";

export const VerificationSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width } = useVideoConfig();

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
  const particle1Y = interpolate(particle1Spring, [0, 1], [0, -120]);
  const particle1Opacity = interpolate(particle1Spring, [0, 0.7, 1], [0, 1, 0]);

  const particle2Spring = spring({
    frame: frame - 2.2 * fps,
    fps,
    config: { damping: 15 },
  });
  const particle2Y = interpolate(particle2Spring, [0, 1], [0, -100]);
  const particle2Opacity = interpolate(particle2Spring, [0, 0.7, 1], [0, 1, 0]);

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
      <div className="absolute top-12 right-8">
        <AnimatedLogo size={140} delay={fps} />
      </div>

      {/* Floating Checkmark Particles */}
      <div
        className="absolute top-1/3 left-16 text-7xl text-green-400"
        style={{
          opacity: particle1Opacity,
          transform: `translateY(${particle1Y}px)`,
        }}
      >
        ✓
      </div>
      <div
        className="absolute top-1/3 right-20 text-6xl text-green-400"
        style={{
          opacity: particle2Opacity,
          transform: `translateY(${particle2Y}px)`,
        }}
      >
        ✓
      </div>

      <div className="absolute inset-0 flex flex-col items-center justify-center px-8">
        <h2
          className="text-5xl font-bold text-white mb-12 text-center"
          style={{ opacity: titleOpacity }}
        >
          Verifizierung erfolgreich!
        </h2>

        {/* Success Badge - Full Width */}
        <div
          className="bg-white rounded-3xl p-8 shadow-2xl mb-8 w-full"
          style={{
            opacity: badgeScale,
            transform: `scale(${badgeScale}) rotate(${badgeRotation}deg)`,
          }}
        >
          <div className="flex items-center gap-6">
            <div className="w-24 h-24 rounded-full bg-neutral-800 flex items-center justify-center flex-shrink-0">
              <span className="text-6xl text-white">✓</span>
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-xl text-neutral-600 font-medium mb-2">Dein Handle ist aktiv:</p>
              <p className="text-3xl font-bold text-neutral-800 break-all">
                satoshi21@einundzwanzig.space
              </p>
            </div>
          </div>
        </div>

        {/* Handle List - Full Width */}
        <div
          className="bg-white/10 backdrop-blur-sm rounded-3xl p-8 border-2 border-white/20 w-full"
          style={{
            opacity: listOpacity,
            transform: `translateY(${listY}px)`,
          }}
        >
          <p className="text-2xl font-semibold text-white mb-5">Deine aktivierten Handles:</p>
          <div className="space-y-4">
            <div className="flex items-center gap-4 bg-white/10 rounded-2xl px-6 py-5">
              <span className="text-2xl text-white flex-1 break-all">satoshi21@einundzwanzig.space</span>
              <span className="bg-neutral-800 text-white text-xl font-bold px-5 py-2 rounded-full border-2 border-neutral-600 flex-shrink-0">
                OK
              </span>
            </div>
          </div>
        </div>

        <p className="text-neutral-200 text-center mt-12 text-3xl px-4 font-medium leading-relaxed" style={{ opacity: listOpacity }}>
          Dein NIP-05 Handle ist jetzt aktiv! Nostr-Clients zeigen ein Verifizierungs-Häkchen für dich an.
        </p>
      </div>
    </AbsoluteFill>
  );
};
