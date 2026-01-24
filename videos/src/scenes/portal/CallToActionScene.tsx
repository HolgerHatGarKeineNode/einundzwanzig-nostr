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

// Spring configurations
const SMOOTH = { damping: 200 };
const SNAPPY = { damping: 15, stiffness: 80 };
const BOUNCY = { damping: 12 };

// URL to display
const PORTAL_URL = "portal.einundzwanzig.space";

/**
 * CallToActionScene - Scene 8: Call to Action (12 seconds / 360 frames @ 30fps)
 *
 * Animation sequence:
 * 1. Dashboard blur + slight zoom out effect
 * 2. Glassmorphism overlay fades in
 * 3. "Werde Teil der Community" - spring entrance
 * 4. URL types out: `portal.einundzwanzig.space`
 * 5. URL pulses with orange glow
 * 6. EINUNDZWANZIG Logo appears center with glow
 * 7. Audio: success-fanfare
 */
export const CallToActionScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Background blur and zoom out animation (0-60 frames)
  const blurSpring = spring({
    frame,
    fps,
    config: SMOOTH,
  });

  const backgroundBlur = interpolate(blurSpring, [0, 1], [0, 8]);
  const backgroundScale = interpolate(blurSpring, [0, 1], [1, 0.95]);
  const backgroundOpacity = interpolate(blurSpring, [0, 1], [0.3, 0.15]);

  // Glassmorphism overlay entrance (delayed 15 frames)
  const overlayDelay = 15;
  const overlaySpring = spring({
    frame: frame - overlayDelay,
    fps,
    config: SNAPPY,
  });
  const overlayOpacity = interpolate(overlaySpring, [0, 1], [0, 1]);

  // Title entrance (delayed 30 frames)
  const titleDelay = Math.floor(1 * fps);
  const titleSpring = spring({
    frame: frame - titleDelay,
    fps,
    config: BOUNCY,
  });
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);
  const titleY = interpolate(titleSpring, [0, 1], [50, 0]);
  const titleScale = interpolate(titleSpring, [0, 1], [0.8, 1]);

  // Logo entrance (delayed 45 frames)
  const logoDelay = Math.floor(1.5 * fps);
  const logoSpring = spring({
    frame: frame - logoDelay,
    fps,
    config: BOUNCY,
  });
  const logoOpacity = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoScale = interpolate(logoSpring, [0, 1], [0.5, 1]);

  // Logo glow pulse
  const glowIntensity = interpolate(
    Math.sin((frame - logoDelay) * 0.08),
    [-1, 1],
    [0.4, 1]
  );

  // URL typing animation (delayed 2.5 seconds)
  const urlDelay = Math.floor(2.5 * fps);
  const urlTypingProgress = Math.max(
    0,
    Math.min(1, (frame - urlDelay) / (1.5 * fps))
  );
  const displayedUrlLength = Math.floor(urlTypingProgress * PORTAL_URL.length);
  const displayedUrl = PORTAL_URL.slice(0, displayedUrlLength);
  const showCursor = frame >= urlDelay && urlTypingProgress < 1;

  // URL container entrance
  const urlContainerSpring = spring({
    frame: frame - Math.floor(2.3 * fps),
    fps,
    config: SNAPPY,
  });
  const urlContainerOpacity = interpolate(urlContainerSpring, [0, 1], [0, 1]);
  const urlContainerY = interpolate(urlContainerSpring, [0, 1], [30, 0]);

  // URL pulse after typing complete
  const urlPulseActive = frame > urlDelay + 1.5 * fps;
  const urlPulseIntensity = urlPulseActive
    ? interpolate(Math.sin((frame - urlDelay - 1.5 * fps) * 0.1), [-1, 1], [0.6, 1])
    : 0;

  // Subtitle entrance
  const subtitleDelay = Math.floor(3.5 * fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SNAPPY,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);
  const subtitleY = interpolate(subtitleSpring, [0, 1], [20, 0]);

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: success-fanfare */}
      <Sequence from={titleDelay} durationInFrames={Math.floor(4 * fps)}>
        <Audio src={staticFile("sfx/success-fanfare.mp3")} volume={0.6} />
      </Sequence>

      {/* Audio: typing for URL */}
      <Sequence from={urlDelay} durationInFrames={Math.floor(1.5 * fps)}>
        <Audio src={staticFile("sfx/typing.mp3")} volume={0.3} />
      </Sequence>

      {/* Audio: url-emphasis when typing complete */}
      <Sequence
        from={urlDelay + Math.floor(1.6 * fps)}
        durationInFrames={Math.floor(1 * fps)}
      >
        <Audio src={staticFile("sfx/url-emphasis.mp3")} volume={0.5} />
      </Sequence>

      {/* Audio: logo reveal */}
      <Sequence from={logoDelay} durationInFrames={Math.floor(1 * fps)}>
        <Audio src={staticFile("sfx/logo-reveal.mp3")} volume={0.4} />
      </Sequence>

      {/* Blurred Wallpaper Background */}
      <div
        className="absolute inset-0"
        style={{
          filter: `blur(${backgroundBlur}px)`,
          transform: `scale(${backgroundScale})`,
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
            "radial-gradient(circle at 50% 50%, rgba(24, 24, 27, 0.7) 0%, rgba(24, 24, 27, 0.95) 100%)",
        }}
      />

      {/* Glassmorphism Overlay */}
      <div
        className="absolute inset-0 flex items-center justify-center"
        style={{
          opacity: overlayOpacity,
        }}
      >
        <div
          className="relative w-full max-w-2xl mx-8 rounded-3xl p-12 text-center"
          style={{
            backgroundColor: "rgba(39, 39, 42, 0.4)",
            backdropFilter: "blur(20px)",
            border: "1px solid rgba(247, 147, 26, 0.2)",
            boxShadow: `
              0 0 ${60 * glowIntensity}px rgba(247, 147, 26, 0.15),
              0 25px 50px -12px rgba(0, 0, 0, 0.5),
              inset 0 0 60px rgba(247, 147, 26, 0.05)
            `,
          }}
        >
          {/* Title */}
          <h1
            className="text-5xl font-bold text-white mb-8"
            style={{
              opacity: titleOpacity,
              transform: `translateY(${titleY}px) scale(${titleScale})`,
              textShadow: "0 2px 20px rgba(0, 0, 0, 0.5)",
            }}
          >
            Werde Teil der Community
          </h1>

          {/* EINUNDZWANZIG Logo */}
          <div
            className="flex justify-center mb-10"
            style={{
              opacity: logoOpacity,
              transform: `scale(${logoScale})`,
            }}
          >
            <div
              className="relative"
              style={{
                filter: `drop-shadow(0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.6))`,
              }}
            >
              <Img
                src={staticFile("einundzwanzig-square-inverted.svg")}
                style={{
                  width: 120,
                  height: 120,
                }}
              />
              {/* Glow ring */}
              <div
                className="absolute inset-0 rounded-full"
                style={{
                  background: `radial-gradient(circle, rgba(247, 147, 26, ${0.3 * glowIntensity}) 0%, transparent 70%)`,
                  transform: "scale(1.5)",
                }}
              />
            </div>
          </div>

          {/* URL Container */}
          <div
            className="mb-6"
            style={{
              opacity: urlContainerOpacity,
              transform: `translateY(${urlContainerY}px)`,
            }}
          >
            <div
              className="inline-block px-8 py-4 rounded-xl"
              style={{
                backgroundColor: "rgba(24, 24, 27, 0.8)",
                border: urlPulseActive
                  ? `2px solid rgba(247, 147, 26, ${0.5 + urlPulseIntensity * 0.5})`
                  : "2px solid rgba(63, 63, 70, 0.5)",
                boxShadow: urlPulseActive
                  ? `0 0 ${30 * urlPulseIntensity}px rgba(247, 147, 26, 0.4), inset 0 0 ${20 * urlPulseIntensity}px rgba(247, 147, 26, 0.1)`
                  : "none",
              }}
            >
              <span
                className="text-3xl font-mono font-bold"
                style={{
                  color: urlPulseActive ? "#f7931a" : "#a1a1aa",
                  textShadow: urlPulseActive
                    ? `0 0 ${15 * urlPulseIntensity}px rgba(247, 147, 26, 0.6)`
                    : "none",
                }}
              >
                {displayedUrl}
                {showCursor && (
                  <span
                    className="inline-block ml-1"
                    style={{
                      width: 3,
                      height: "1em",
                      backgroundColor: "#f7931a",
                      animation: "none",
                      opacity: Math.floor(frame * 0.15) % 2 === 0 ? 1 : 0,
                    }}
                  />
                )}
              </span>
            </div>
          </div>

          {/* Subtitle */}
          <p
            className="text-xl text-zinc-400"
            style={{
              opacity: subtitleOpacity,
              transform: `translateY(${subtitleY}px)`,
            }}
          >
            Die deutschsprachige Bitcoin-Community wartet auf dich
          </p>
        </div>
      </div>

      {/* Ambient glow effects */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background: `radial-gradient(ellipse at 50% 30%, rgba(247, 147, 26, ${0.08 * glowIntensity}) 0%, transparent 50%)`,
        }}
      />

      {/* Vignette overlay */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          boxShadow: "inset 0 0 200px 80px rgba(0, 0, 0, 0.7)",
        }}
      />
    </AbsoluteFill>
  );
};
