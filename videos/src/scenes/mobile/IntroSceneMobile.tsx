import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, staticFile } from "remotion";
import { BitcoinEffect } from "../../components/BitcoinEffect";
import { AnimatedLogo } from "../../components/AnimatedLogo";

// Lightning Payment Animation Component
const LightningPaymentAnimation: React.FC<{ frame: number; fps: number; startFrame: number }> = ({
  frame,
  fps,
  startFrame,
}) => {
  const relativeFrame = frame - startFrame;

  if (relativeFrame < 0.5 * fps) return null;

  const boltProgress = interpolate(
    relativeFrame,
    [0.5 * fps, 1.5 * fps],
    [0, 1],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  const flashOpacity = interpolate(
    relativeFrame,
    [1.4 * fps, 1.5 * fps, 1.8 * fps],
    [0, 0.8, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  const checkSpring = spring({
    frame: relativeFrame - 1.6 * fps,
    fps,
    config: { damping: 12, stiffness: 150 },
  });
  const checkScale = interpolate(checkSpring, [0, 1], [0, 1]);

  const boltX = interpolate(boltProgress, [0, 1], [-80, 80]);
  const boltY = interpolate(boltProgress, [0, 0.5, 1], [0, -20, 0]);

  const boltRotation = interpolate(
    Math.sin(relativeFrame * 0.4),
    [-1, 1],
    [-15, 15]
  );

  const boltScale = interpolate(
    Math.sin(relativeFrame * 0.3),
    [-1, 1],
    [1, 1.3]
  );

  return (
    <div className="absolute inset-0 flex items-center justify-center pointer-events-none">
      {boltProgress < 1 && (
        <div
          className="absolute text-5xl"
          style={{
            transform: `translateX(${boltX}px) translateY(${boltY}px) rotate(${boltRotation}deg) scale(${boltScale})`,
            filter: "drop-shadow(0 0 12px #f7931a) drop-shadow(0 0 24px #ffcc00)",
          }}
        >
          ⚡
        </div>
      )}

      <div
        className="absolute inset-0 bg-yellow-400"
        style={{ opacity: flashOpacity }}
      />

      {checkScale > 0 && (
        <div
          className="absolute right-4 top-1/2 -translate-y-1/2 w-14 h-14 rounded-full bg-green-500 flex items-center justify-center"
          style={{
            transform: `translateY(-50%) scale(${checkScale})`,
            boxShadow: "0 0 20px rgba(34, 197, 94, 0.6)",
          }}
        >
          <span className="text-white text-3xl font-bold">✓</span>
        </div>
      )}
    </div>
  );
};

export const IntroSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width } = useVideoConfig();

  // Logo entrance
  const logoSpring = spring({
    frame,
    fps,
    config: { damping: 200 },
  });
  const logoOpacity = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoScale = interpolate(logoSpring, [0, 1], [0.5, 1]);

  // Main title
  const titleSpring = spring({
    frame: frame - 0.5 * fps,
    fps,
    config: { damping: 200 },
  });
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);
  const titleY = interpolate(titleSpring, [0, 1], [30, 0]);

  // Subtitle
  const subtitleSpring = spring({
    frame: frame - 1 * fps,
    fps,
    config: { damping: 200 },
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);

  // Step 1: Registration
  const step1Spring = spring({
    frame: frame - 2 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const step1Opacity = interpolate(step1Spring, [0, 1], [0, 1]);
  const step1Y = interpolate(step1Spring, [0, 1], [50, 0]);

  // Step 2: Lightning Payment
  const step2Spring = spring({
    frame: frame - 4.5 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const step2Opacity = interpolate(step2Spring, [0, 1], [0, 1]);
  const step2Y = interpolate(step2Spring, [0, 1], [50, 0]);

  // Step 3: NIP-05 Benefit
  const step3Spring = spring({
    frame: frame - 7.5 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const step3Opacity = interpolate(step3Spring, [0, 1], [0, 1]);
  const step3Y = interpolate(step3Spring, [0, 1], [50, 0]);

  // Arrow animations
  const arrow1Spring = spring({
    frame: frame - 3.5 * fps,
    fps,
    config: { damping: 200 },
  });
  const arrow1Opacity = interpolate(arrow1Spring, [0, 1], [0, 1]);

  const arrow2Spring = spring({
    frame: frame - 7 * fps,
    fps,
    config: { damping: 200 },
  });
  const arrow2Opacity = interpolate(arrow2Spring, [0, 1], [0, 1]);

  // Final call to action
  const ctaSpring = spring({
    frame: frame - 9.5 * fps,
    fps,
    config: { damping: 200 },
  });
  const ctaOpacity = interpolate(ctaSpring, [0, 1], [0, 1]);
  const ctaScale = interpolate(ctaSpring, [0, 1], [0.9, 1]);

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
      <div className="absolute inset-0 bg-neutral-900/80" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      {/* Header with Logo and Title */}
      <div className="absolute top-12 left-0 right-0 flex flex-col items-center gap-4 px-8">
        <div
          style={{
            opacity: logoOpacity,
            transform: `scale(${logoScale})`,
          }}
        >
          <AnimatedLogo size={100} delay={0} />
        </div>

        <div
          className="text-center"
          style={{
            opacity: titleOpacity,
            transform: `translateY(${titleY}px)`,
          }}
        >
          <h1 className="text-5xl font-bold text-white">
            NIP-05 Verifikation
          </h1>
          <p
            className="text-2xl text-neutral-300 mt-3"
            style={{ opacity: subtitleOpacity }}
          >
            So erhältst du deine verifizierte Nostr-Identität
          </p>
        </div>
      </div>

      {/* Steps Container */}
      <div className="absolute top-[22%] left-0 right-0 px-8">
        <div className="flex flex-col gap-4">
          {/* Step 1: Registration */}
          <div
            className="flex items-start gap-5"
            style={{
              opacity: step1Opacity,
              transform: `translateY(${step1Y}px)`,
            }}
          >
            <div className="flex-shrink-0 w-20 h-20 rounded-full bg-orange-500 flex items-center justify-center border-4 border-orange-400 shadow-xl">
              <span className="text-4xl font-bold text-white">1</span>
            </div>
            <div className="flex-1 bg-white/10 backdrop-blur-sm rounded-2xl p-6 border-2 border-white/20 shadow-lg">
              <h3 className="text-3xl font-bold text-white mb-2 flex items-center gap-3">
                <span className="text-4xl">📝</span>
                Registrierung im Verein
              </h3>
              <p className="text-xl text-neutral-200">
                Werde Mitglied bei EINUNDZWANZIG und erhalte Zugang zu allen Vorteilen
              </p>
            </div>
          </div>

          {/* Arrow 1 */}
          <div
            className="flex justify-center -my-1"
            style={{ opacity: arrow1Opacity }}
          >
            <div className="text-5xl text-orange-500">↓</div>
          </div>

          {/* Step 2: Lightning Payment */}
          <div
            className="flex items-start gap-5"
            style={{
              opacity: step2Opacity,
              transform: `translateY(${step2Y}px)`,
            }}
          >
            <div className="flex-shrink-0 w-20 h-20 rounded-full bg-orange-500 flex items-center justify-center border-4 border-orange-400 shadow-xl">
              <span className="text-4xl font-bold text-white">2</span>
            </div>
            <div className="flex-1 bg-white/10 backdrop-blur-sm rounded-2xl p-6 border-2 border-white/20 shadow-lg relative overflow-hidden">
              <div
                className="absolute top-0 right-0 w-40 h-40 blur-3xl opacity-50"
                style={{
                  background: "radial-gradient(circle, #f7931a 0%, transparent 70%)",
                }}
              />
              <h3 className="text-3xl font-bold text-white mb-2 flex items-center gap-3 relative z-10">
                <span className="text-4xl">⚡</span>
                Beitrag via Lightning
              </h3>
              <p className="text-xl text-neutral-200 relative z-10">
                Zahle deinen Mitgliedsbeitrag schnell und einfach mit Bitcoin Lightning
              </p>
              <LightningPaymentAnimation frame={frame} fps={fps} startFrame={4.5 * fps} />
            </div>
          </div>

          {/* Arrow 2 */}
          <div
            className="flex justify-center -my-1"
            style={{ opacity: arrow2Opacity }}
          >
            <div className="text-5xl text-orange-500">↓</div>
          </div>

          {/* Step 3: NIP-05 Benefit */}
          <div
            className="flex items-start gap-5"
            style={{
              opacity: step3Opacity,
              transform: `translateY(${step3Y}px)`,
            }}
          >
            <div className="flex-shrink-0 w-20 h-20 rounded-full bg-orange-500 flex items-center justify-center border-4 border-orange-400 shadow-xl">
              <span className="text-4xl font-bold text-white">3</span>
            </div>
            <div className="flex-1 bg-gradient-to-r from-orange-500/20 to-orange-600/20 backdrop-blur-sm rounded-2xl p-6 border-2 border-orange-400 shadow-lg">
              <h3 className="text-3xl font-bold text-white mb-2 flex items-center gap-3">
                <span className="text-4xl">✓</span>
                NIP-05 Handle erstellen
              </h3>
              <p className="text-xl text-neutral-200">
                Erstelle dein persönliches NIP-05 Handle und verifiziere deine Nostr-Identität
              </p>
            </div>
          </div>
        </div>
      </div>

      {/* Call to Action - Footer */}
      <div
        className="absolute bottom-12 left-0 right-0 text-center px-8"
        style={{
          opacity: ctaOpacity,
          transform: `scale(${ctaScale})`,
        }}
      >
        <p className="text-5xl text-white font-bold mb-4">
          Los geht's!
        </p>
        <p className="text-3xl text-neutral-200 font-medium">
          Im nächsten Video zeigen wir dir Schritt 3 im Detail
        </p>
      </div>
    </AbsoluteFill>
  );
};
