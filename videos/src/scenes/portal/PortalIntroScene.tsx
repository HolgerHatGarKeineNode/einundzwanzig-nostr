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
import {
  SPRING_CONFIGS,
  TIMING,
  GLOW_CONFIG,
  secondsToFrames,
} from "../../config/timing";

/**
 * PortalIntroScene - Scene 1: Logo Reveal (6 seconds / 180 frames @ 30fps)
 *
 * Animation sequence:
 * 1. Wallpaper background zooms in from 1.2 to 1.0 scale
 * 2. AnimatedLogo scales from 0 to 100% with spring animation
 * 3. Bitcoin particles fall in the background
 * 4. Glow effect pulses around the logo
 */
export const PortalIntroScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Calculate delays using centralized timing
  const logoEntranceDelay = secondsToFrames(TIMING.LOGO_ENTRANCE_DELAY, fps);
  const titleDelay = secondsToFrames(TIMING.TITLE_DELAY, fps);
  const subtitleDelay = secondsToFrames(TIMING.SUBTITLE_DELAY, fps);

  // Background zoom animation - starts zoomed in, zooms out over 3 seconds
  // Fine-tuned: Extended zoom duration for more cinematic feel
  const backgroundZoomDuration = secondsToFrames(3.5, fps);
  const backgroundZoom = interpolate(frame, [0, backgroundZoomDuration], [1.15, 1.0], {
    extrapolateRight: "clamp",
  });

  // Logo entrance spring animation - delayed slightly for dramatic effect
  const logoSpring = spring({
    frame: frame - logoEntranceDelay,
    fps,
    config: SPRING_CONFIGS.LOGO,
  });
  const logoScale = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoOpacity = interpolate(logoSpring, [0, 0.5], [0, 1], {
    extrapolateRight: "clamp",
  });

  // Outer glow pulse effect using centralized config
  const glowIntensity = interpolate(
    Math.sin((frame - logoEntranceDelay) * GLOW_CONFIG.FREQUENCY.FAST),
    [-1, 1],
    GLOW_CONFIG.INTENSITY.NORMAL
  );
  const glowScale = interpolate(
    Math.sin((frame - logoEntranceDelay) * GLOW_CONFIG.FREQUENCY.NORMAL),
    [-1, 1],
    GLOW_CONFIG.SCALE.NORMAL
  );

  // Title text entrance - appears after logo with smooth spring
  const titleSpring = spring({
    frame: frame - titleDelay,
    fps,
    config: SPRING_CONFIGS.SMOOTH,
  });
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);
  const titleY = interpolate(titleSpring, [0, 1], [30, 0]);

  // Subtitle entrance - follows title with smooth spring
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SPRING_CONFIGS.SMOOTH,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);

  // Center position for logo (adjusts as text appears)
  // Fine-tuned: smoother transition over longer duration
  const contentTransitionDuration = secondsToFrames(1.2, fps);
  const contentY = interpolate(
    frame,
    [titleDelay, titleDelay + contentTransitionDuration],
    [0, -60],
    {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    }
  );

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">

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
