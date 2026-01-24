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
import { BitcoinEffect } from "../../components/BitcoinEffect";

// Spring configurations
const SMOOTH = { damping: 200 };
const SNAPPY = { damping: 15, stiffness: 80 };

/**
 * PortalOutroScene - Scene 9: Outro (12 seconds / 360 frames @ 30fps)
 *
 * Animation sequence:
 * 1. Fade from previous scene to wallpaper background
 * 2. BitcoinEffect particles throughout
 * 3. Horizontal Logo fades in at center
 * 4. "EINUNDZWANZIG" text appears below logo
 * 5. Glow effect pulses around the logo
 * 6. Background music fades out in last 3 seconds
 * 7. Audio: final-chime at logo appearance
 */
export const PortalOutroScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const durationInFrames = 12 * fps; // 360 frames

  // Background fade-in from black (0-30 frames)
  const backgroundSpring = spring({
    frame,
    fps,
    config: SMOOTH,
  });
  const backgroundOpacity = interpolate(backgroundSpring, [0, 1], [0, 0.3]);

  // Logo entrance animation (delayed 1 second)
  const logoDelay = Math.floor(1 * fps);
  const logoSpring = spring({
    frame: frame - logoDelay,
    fps,
    config: SNAPPY,
  });
  const logoOpacity = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoScale = interpolate(logoSpring, [0, 1], [0.8, 1]);
  const logoY = interpolate(logoSpring, [0, 1], [30, 0]);

  // Logo glow pulse effect
  const glowIntensity = interpolate(
    Math.sin((frame - logoDelay) * 0.06),
    [-1, 1],
    [0.4, 0.9]
  );
  const glowScale = interpolate(
    Math.sin((frame - logoDelay) * 0.04),
    [-1, 1],
    [1.0, 1.2]
  );

  // Text entrance (delayed 2 seconds)
  const textDelay = Math.floor(2 * fps);
  const textSpring = spring({
    frame: frame - textDelay,
    fps,
    config: SMOOTH,
  });
  const textOpacity = interpolate(textSpring, [0, 1], [0, 1]);
  const textY = interpolate(textSpring, [0, 1], [20, 0]);

  // Subtitle entrance (delayed 2.5 seconds)
  const subtitleDelay = Math.floor(2.5 * fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SMOOTH,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);

  // Final fade out in last 2 seconds (frames 300-360)
  const fadeOutStart = durationInFrames - 2 * fps;
  const finalFadeOpacity = interpolate(
    frame,
    [fadeOutStart, durationInFrames],
    [1, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: final-chime when logo appears */}
      <Sequence from={logoDelay} durationInFrames={Math.floor(3 * fps)}>
        <Audio src={staticFile("sfx/final-chime.mp3")} volume={0.6} />
      </Sequence>

      {/* Content wrapper with final fade */}
      <div style={{ opacity: finalFadeOpacity }}>
        {/* Wallpaper Background */}
        <div
          className="absolute inset-0"
          style={{
            transform: "scale(1.05)",
            transformOrigin: "center center",
          }}
        >
          <Img
            src={staticFile("einundzwanzig-wallpaper.png")}
            className="absolute inset-0 w-full h-full object-cover"
            style={{ opacity: backgroundOpacity }}
          />
        </div>

        {/* Dark gradient overlay */}
        <div
          className="absolute inset-0"
          style={{
            background:
              "radial-gradient(circle at center, rgba(24, 24, 27, 0.6) 0%, rgba(24, 24, 27, 0.9) 70%, rgba(24, 24, 27, 0.98) 100%)",
          }}
        />

        {/* Bitcoin particle effect */}
        <BitcoinEffect />

        {/* Content container */}
        <div className="absolute inset-0 flex flex-col items-center justify-center">
          {/* Outer glow effect behind logo */}
          <div
            className="absolute"
            style={{
              width: 800,
              height: 400,
              background:
                "radial-gradient(ellipse, rgba(247, 147, 26, 0.35) 0%, transparent 60%)",
              opacity: glowIntensity * logoSpring,
              transform: `scale(${glowScale * logoScale})`,
              filter: "blur(60px)",
            }}
          />

          {/* Horizontal Logo */}
          <div
            style={{
              opacity: logoOpacity,
              transform: `scale(${logoScale}) translateY(${logoY}px)`,
            }}
          >
            <div
              style={{
                filter: `drop-shadow(0 0 ${40 * glowIntensity}px rgba(247, 147, 26, 0.5))`,
              }}
            >
              <Img
                src={staticFile("einundzwanzig-horizontal-inverted.svg")}
                style={{
                  width: 600,
                  height: "auto",
                }}
              />
            </div>
          </div>

          {/* EINUNDZWANZIG text */}
          <div
            className="mt-12 text-center"
            style={{
              opacity: textOpacity,
              transform: `translateY(${textY}px)`,
            }}
          >
            <h1
              className="text-5xl font-bold text-white tracking-widest"
              style={{
                textShadow: `0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.4), 0 2px 20px rgba(0, 0, 0, 0.5)`,
              }}
            >
              EINUNDZWANZIG
            </h1>
          </div>

          {/* Subtitle */}
          <div
            className="mt-6 text-center"
            style={{
              opacity: subtitleOpacity,
            }}
          >
            <p className="text-2xl text-orange-500 font-medium tracking-wide">
              Die deutschsprachige Bitcoin-Community
            </p>
          </div>
        </div>

        {/* Ambient glow at bottom */}
        <div
          className="absolute inset-x-0 bottom-0 h-64 pointer-events-none"
          style={{
            background:
              "linear-gradient(to top, rgba(247, 147, 26, 0.08) 0%, transparent 100%)",
          }}
        />

        {/* Vignette overlay */}
        <div
          className="absolute inset-0 pointer-events-none"
          style={{
            boxShadow: "inset 0 0 250px 100px rgba(0, 0, 0, 0.8)",
          }}
        />
      </div>
    </AbsoluteFill>
  );
};
