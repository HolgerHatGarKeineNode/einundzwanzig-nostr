import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
  staticFile,
  Sequence,
} from "remotion";
import { Audio } from "@remotion/media";
import { AnimatedLogo } from "../../components/AnimatedLogo";
import { BitcoinEffect } from "../../components/BitcoinEffect";

// Spring configurations from PRD
const SMOOTH = { damping: 200 };

/**
 * PortalIntroScene - Scene 1: Logo Reveal (6 seconds / 180 frames @ 30fps)
 *
 * Animation sequence:
 * 1. Wallpaper background zooms in from 1.2 to 1.0 scale
 * 2. AnimatedLogo scales from 0 to 100% with spring animation
 * 3. Bitcoin particles fall in the background
 * 4. Glow effect pulses around the logo
 * 5. Audio: logo-whoosh at start, logo-reveal when logo appears
 */
export const PortalIntroScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Background zoom animation - starts zoomed in, zooms out
  const backgroundZoom = interpolate(frame, [0, 3 * fps], [1.2, 1.0], {
    extrapolateRight: "clamp",
  });

  // Logo entrance spring animation - delayed slightly for dramatic effect
  const logoEntranceDelay = Math.floor(0.5 * fps);
  const logoSpring = spring({
    frame: frame - logoEntranceDelay,
    fps,
    config: { damping: 15, stiffness: 80 },
  });
  const logoScale = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoOpacity = interpolate(logoSpring, [0, 0.5], [0, 1], {
    extrapolateRight: "clamp",
  });

  // Outer glow pulse effect
  const glowIntensity = interpolate(
    Math.sin((frame - logoEntranceDelay) * 0.08),
    [-1, 1],
    [0.4, 0.8]
  );
  const glowScale = interpolate(
    Math.sin((frame - logoEntranceDelay) * 0.06),
    [-1, 1],
    [1.0, 1.15]
  );

  // Title text entrance - appears after logo
  const titleDelay = Math.floor(2 * fps);
  const titleSpring = spring({
    frame: frame - titleDelay,
    fps,
    config: SMOOTH,
  });
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);
  const titleY = interpolate(titleSpring, [0, 1], [30, 0]);

  // Subtitle entrance
  const subtitleDelay = Math.floor(2.8 * fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SMOOTH,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);

  // Center position for logo (adjusts as text appears)
  const contentY = interpolate(frame, [titleDelay, titleDelay + fps], [0, -60], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: logo-whoosh at start */}
      <Sequence durationInFrames={Math.floor(2 * fps)}>
        <Audio src={staticFile("sfx/logo-whoosh.mp3")} volume={0.7} />
      </Sequence>

      {/* Audio: logo-reveal when logo appears */}
      <Sequence from={logoEntranceDelay} durationInFrames={Math.floor(2 * fps)}>
        <Audio src={staticFile("sfx/logo-reveal.mp3")} volume={0.6} />
      </Sequence>

      {/* Wallpaper Background with zoom effect */}
      <div
        className="absolute inset-0"
        style={{
          transform: `scale(${backgroundZoom})`,
          transformOrigin: "center center",
        }}
      >
        <Img
          src={staticFile("einundzwanzig-wallpaper.png")}
          className="absolute inset-0 w-full h-full object-cover"
          style={{ opacity: 0.25 }}
        />
      </div>

      {/* Dark gradient overlay for depth */}
      <div
        className="absolute inset-0"
        style={{
          background:
            "radial-gradient(circle at center, transparent 0%, rgba(24, 24, 27, 0.7) 70%, rgba(24, 24, 27, 0.95) 100%)",
        }}
      />

      {/* Bitcoin particle effect in background */}
      <BitcoinEffect />

      {/* Content container with vertical centering */}
      <div
        className="absolute inset-0 flex flex-col items-center justify-center"
        style={{
          transform: `translateY(${contentY}px)`,
        }}
      >
        {/* Outer glow effect behind logo */}
        <div
          className="absolute"
          style={{
            width: 500,
            height: 500,
            background:
              "radial-gradient(circle, rgba(247, 147, 26, 0.4) 0%, transparent 60%)",
            opacity: glowIntensity * logoSpring,
            transform: `scale(${glowScale * logoScale})`,
            filter: "blur(40px)",
          }}
        />

        {/* AnimatedLogo with entrance animation */}
        <div
          style={{
            opacity: logoOpacity,
            transform: `scale(${logoScale})`,
          }}
        >
          <AnimatedLogo size={350} delay={logoEntranceDelay} />
        </div>

        {/* Title text */}
        <div
          className="mt-8 text-center"
          style={{
            opacity: titleOpacity,
            transform: `translateY(${titleY}px)`,
          }}
        >
          <h1 className="text-6xl font-bold text-white tracking-wider">
            EINUNDZWANZIG
          </h1>
        </div>

        {/* Subtitle */}
        <div
          className="mt-4 text-center"
          style={{
            opacity: subtitleOpacity,
          }}
        >
          <p className="text-2xl text-orange-500 font-medium tracking-wide">
            Das Portal
          </p>
        </div>
      </div>

      {/* Vignette overlay */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          boxShadow: "inset 0 0 200px 50px rgba(0, 0, 0, 0.7)",
        }}
      />
    </AbsoluteFill>
  );
};
