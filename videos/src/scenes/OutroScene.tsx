import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, Img, staticFile } from "remotion";
import { BitcoinEffect } from "../components/BitcoinEffect";
import { AnimatedLogo } from "../components/AnimatedLogo";

export const OutroScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Logo animation at top
  const logoSpring = spring({
    frame,
    fps,
    config: { damping: 200 },
  });
  const logoOpacity = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoY = interpolate(logoSpring, [0, 1], [-50, 0]);

  // Main SVG Logo
  const svgSpring = spring({
    frame: frame - 0.5 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const svgScale = interpolate(svgSpring, [0, 1], [0.8, 1]);
  const svgOpacity = interpolate(svgSpring, [0, 1], [0, 1]);

  // URL Animation - delayed and prominent
  const urlSpring = spring({
    frame: frame - 2 * fps,
    fps,
    config: { damping: 20, stiffness: 100 },
  });
  const urlScale = interpolate(urlSpring, [0, 1], [0.5, 1]);
  const urlOpacity = interpolate(urlSpring, [0, 1], [0, 1]);

  // URL pulsing effect
  const urlPulse = interpolate(
    Math.sin((frame - 2 * fps) * 0.05),
    [-1, 1],
    [1, 1.05]
  );

  // Call to action
  const ctaSpring = spring({
    frame: frame - 1 * fps,
    fps,
    config: { damping: 200 },
  });
  const ctaOpacity = interpolate(ctaSpring, [0, 1], [0, 1]);
  const ctaY = interpolate(ctaSpring, [0, 1], [30, 0]);

  // Footer appears last
  const footerSpring = spring({
    frame: frame - 4 * fps,
    fps,
    config: { damping: 200 },
  });
  const footerOpacity = interpolate(footerSpring, [0, 1], [0, 1]);

  return (
    <AbsoluteFill>
      {/* Wallpaper Background */}
      <Img
        src={staticFile("einundzwanzig-wallpaper.png")}
        className="absolute inset-0 w-full h-full object-cover"
      />
      <div className="absolute inset-0 bg-neutral-950/85" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      {/* Animated Logo Top */}
      <div
        className="absolute top-16 left-1/2 -translate-x-1/2"
        style={{
          opacity: logoOpacity,
          transform: `translateX(-50%) translateY(${logoY}px)`,
        }}
      >
        <AnimatedLogo size={180} delay={0} />
      </div>

      {/* Content */}
      <div className="absolute inset-0 flex flex-col items-center justify-center px-12">
        {/* EINUNDZWANZIG Logo SVG */}
        <div
          className="mb-16"
          style={{
            opacity: svgOpacity,
            transform: `scale(${svgScale})`,
          }}
        >
          <Img
            src={staticFile("einundzwanzig-horizontal-inverted.svg")}
            style={{ width: 700, height: "auto" }}
          />
        </div>

        {/* Call to Action Text */}
        <div
          style={{
            opacity: ctaOpacity,
            transform: `translateY(${ctaY}px)`,
          }}
        >
          <p className="text-4xl text-white text-center font-semibold mb-12">
            Werde jetzt Mitglied!
          </p>
        </div>

        {/* URL - MAIN FOCUS - Very Large and Prominent */}
        <div
          className="relative"
          style={{
            opacity: urlOpacity,
            transform: `scale(${urlScale * urlPulse})`,
          }}
        >
          {/* Glow effect behind URL */}
          <div
            className="absolute inset-0 blur-3xl"
            style={{
              background: "radial-gradient(ellipse, #f7931a 0%, transparent 70%)",
              opacity: 0.4,
            }}
          />

          {/* URL Container with border */}
          <div className="relative bg-neutral-800/80 backdrop-blur-sm rounded-3xl px-20 py-12 border-4 border-orange-500 shadow-2xl">
            <p className="text-7xl text-white text-center font-bold tracking-wide">
              verein.einundzwanzig.space
            </p>

            {/* Accent line */}
            <div className="h-2 bg-gradient-to-r from-transparent via-orange-500 to-transparent mt-6 rounded-full" />
          </div>
        </div>

        {/* Footer Info */}
        <div
          className="absolute bottom-20 left-0 right-0 text-center"
          style={{ opacity: footerOpacity }}
        >
          <p className="text-neutral-300 text-2xl mb-3 font-semibold">
            Mitglieder-Vorteile:
          </p>
          <p className="text-neutral-400 text-xl">
            🔗 Nostr Relay • ✓ NIP-05 • ⚡ Lightning Watchtower
          </p>
        </div>
      </div>
    </AbsoluteFill>
  );
};
