import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
  staticFile,
  Sequence,
  Easing,
} from "remotion";
import { Audio } from "@remotion/media";
import { BitcoinEffect } from "../../components/BitcoinEffect";
import { LogoMatrix3D } from "../../components/LogoMatrix3D";
import {
  SPRING_CONFIGS,
  GLOW_CONFIG,
} from "../../config/timing";

/**
 * PortalOutroScene - Scene 9: Cinematic Logo Matrix Outro (30 seconds / 900 frames @ 30fps)
 *
 * Animation sequence:
 * 1. 3D Logo Matrix materializes with all 230+ meetup logos
 * 2. Camera flies through the matrix cinematically
 * 3. Random meetups spotlight with name reveals
 * 4. All logos converge to center
 * 5. Einundzwanzig main logo emerges triumphantly
 * 6. Final fade to black
 */
export const PortalOutroScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();
  const durationInFrames = 30 * fps; // 900 frames

  // Phase timing (in seconds)
  const PHASE = {
    MATRIX: { start: 0, end: 26 },
    FINAL_LOGO: { start: 24, end: 30 },
    FADE_OUT: { start: 28, end: 30 },
  };

  // Background ambient glow
  const backgroundGlow = interpolate(
    Math.sin(frame * 0.04),
    [-1, 1],
    [0.1, 0.25]
  );

  // Final logo entrance (emerges from convergence)
  const logoEntranceFrame = PHASE.FINAL_LOGO.start * fps;
  const logoSpring = spring({
    frame: frame - logoEntranceFrame,
    fps,
    config: { damping: 12, stiffness: 60 },
  });
  const logoOpacity = interpolate(
    frame,
    [logoEntranceFrame, logoEntranceFrame + fps],
    [0, 1],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );
  const logoScale = interpolate(logoSpring, [0, 1], [0.3, 1]);

  // Logo glow pulse
  const glowIntensity = interpolate(
    Math.sin((frame - logoEntranceFrame) * GLOW_CONFIG.FREQUENCY.NORMAL),
    [-1, 1],
    GLOW_CONFIG.INTENSITY.STRONG
  );

  // Text entrances
  const textDelay = logoEntranceFrame + fps;
  const textSpring = spring({
    frame: frame - textDelay,
    fps,
    config: SPRING_CONFIGS.SMOOTH,
  });
  const textOpacity = interpolate(textSpring, [0, 1], [0, 1]);
  const textY = interpolate(textSpring, [0, 1], [30, 0]);

  const subtitleDelay = textDelay + Math.floor(0.5 * fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SPRING_CONFIGS.SMOOTH,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);

  // Final fade out
  const fadeOutStart = PHASE.FADE_OUT.start * fps;
  const finalFadeOpacity = interpolate(
    frame,
    [fadeOutStart, durationInFrames],
    [1, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp", easing: Easing.out(Easing.cubic) }
  );

  // Matrix visibility (fade out during final logo)
  const matrixOpacity = interpolate(
    frame,
    [PHASE.MATRIX.end * fps - fps * 2, PHASE.MATRIX.end * fps],
    [1, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: epic-whoosh at matrix flight, final-chime at logo reveal */}
      <Sequence from={Math.floor(4 * fps)} durationInFrames={Math.floor(2 * fps)}>
        <Audio src={staticFile("sfx/logo-whoosh.mp3")} volume={0.3} />
      </Sequence>
      <Sequence from={logoEntranceFrame} durationInFrames={Math.floor(3 * fps)}>
        <Audio src={staticFile("sfx/final-chime.mp3")} volume={0.7} />
      </Sequence>

      {/* Content wrapper with final fade */}
      <div style={{ opacity: finalFadeOpacity }}>
        {/* Deep space background */}
        <div
          className="absolute inset-0"
          style={{
            background: `
              radial-gradient(ellipse at 30% 20%, rgba(247, 147, 26, ${backgroundGlow * 0.5}) 0%, transparent 40%),
              radial-gradient(ellipse at 70% 80%, rgba(247, 147, 26, ${backgroundGlow * 0.3}) 0%, transparent 35%),
              radial-gradient(ellipse at 50% 50%, rgba(24, 24, 27, 1) 0%, rgba(0, 0, 0, 1) 100%)
            `,
          }}
        />

        {/* Wallpaper subtle background */}
        <Img
          src={staticFile("einundzwanzig-wallpaper.png")}
          className="absolute inset-0 w-full h-full object-cover"
          style={{ opacity: 0.08 }}
        />

        {/* 3D Logo Matrix */}
        <div style={{ opacity: matrixOpacity }}>
          <LogoMatrix3D />
        </div>

        {/* Bitcoin particle effect (always visible) */}
        <BitcoinEffect />

        {/* Final Logo Reveal */}
        {frame >= logoEntranceFrame && (
          <div
            className="absolute inset-0 flex flex-col items-center justify-center"
            style={{ opacity: logoOpacity }}
          >
            {/* Massive outer glow */}
            <div
              className="absolute"
              style={{
                width: 1000,
                height: 500,
                background: `radial-gradient(ellipse, rgba(247, 147, 26, ${0.4 * glowIntensity}) 0%, transparent 60%)`,
                filter: "blur(80px)",
                transform: `scale(${logoScale})`,
              }}
            />

            {/* Secondary glow ring */}
            <div
              className="absolute"
              style={{
                width: 700,
                height: 350,
                background: `radial-gradient(ellipse, rgba(247, 147, 26, ${0.6 * glowIntensity}) 0%, transparent 50%)`,
                filter: "blur(40px)",
                transform: `scale(${logoScale})`,
              }}
            />

            {/* Main Logo */}
            <div
              style={{
                transform: `scale(${logoScale})`,
              }}
            >
              <div
                style={{
                  filter: `
                    drop-shadow(0 0 ${60 * glowIntensity}px rgba(247, 147, 26, 0.6))
                    drop-shadow(0 0 ${120 * glowIntensity}px rgba(247, 147, 26, 0.3))
                  `,
                }}
              >
                <Img
                  src={staticFile("einundzwanzig-horizontal-inverted.svg")}
                  style={{
                    width: 700,
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
                className="text-6xl font-bold text-white tracking-[0.3em]"
                style={{
                  textShadow: `
                    0 0 ${40 * glowIntensity}px rgba(247, 147, 26, 0.5),
                    0 0 ${80 * glowIntensity}px rgba(247, 147, 26, 0.3),
                    0 4px 30px rgba(0, 0, 0, 0.7)
                  `,
                }}
              >
                EINUNDZWANZIG
              </h1>
            </div>

            {/* Subtitle */}
            <div
              className="mt-8 text-center"
              style={{ opacity: subtitleOpacity }}
            >
              <p
                className="text-3xl text-orange-400 font-medium tracking-widest"
                style={{
                  textShadow: "0 0 20px rgba(247, 147, 26, 0.5)",
                }}
              >
                Die Bitcoin-Community
              </p>
            </div>

            {/* Community count badge */}
            <div
              className="mt-6"
              style={{
                opacity: subtitleOpacity,
                transform: `scale(${subtitleSpring})`,
              }}
            >
              <div
                className="px-6 py-2 rounded-full"
                style={{
                  background: "rgba(247, 147, 26, 0.15)",
                  border: "1px solid rgba(247, 147, 26, 0.4)",
                }}
              >
                <span className="text-xl text-orange-300 font-medium">
                  230+ Meetups weltweit
                </span>
              </div>
            </div>
          </div>
        )}

        {/* Ambient bottom glow */}
        <div
          className="absolute inset-x-0 bottom-0 h-80 pointer-events-none"
          style={{
            background: `linear-gradient(to top, rgba(247, 147, 26, ${0.1 * glowIntensity}) 0%, transparent 100%)`,
          }}
        />

        {/* Heavy vignette for cinematic feel */}
        <div
          className="absolute inset-0 pointer-events-none"
          style={{
            boxShadow: "inset 0 0 300px 120px rgba(0, 0, 0, 0.85)",
          }}
        />
      </div>
    </AbsoluteFill>
  );
};
