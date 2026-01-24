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
import {
  SPRING_CONFIGS,
  TIMING,
  GLOW_CONFIG,
  secondsToFrames,
} from "../../config/timing";

/**
 * PortalTitleScene - Scene 2: Title Card (4 seconds / 120 frames @ 30fps)
 *
 * Animation sequence:
 * 1. "EINUNDZWANZIG PORTAL" types in character by character
 * 2. Blinking cursor during typing
 * 3. Subtitle fades in after title completes
 * 4. Audio: typing sound during type animation, ui-appear for subtitle
 */
export const PortalTitleScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Main title text
  const titleText = "EINUNDZWANZIG PORTAL";
  const subtitleText = "Das Herzstück der deutschsprachigen Bitcoin-Community";

  // Calculate typed characters for title using centralized timing
  const typedTitleChars = Math.min(
    titleText.length,
    Math.floor(frame / TIMING.CHAR_FRAMES)
  );
  const typedTitle = titleText.slice(0, typedTitleChars);

  // Title typing complete frame
  const titleCompleteFrame = titleText.length * TIMING.CHAR_FRAMES;

  // Cursor blink effect - only show while typing or shortly after
  // Fine-tuned: cursor stays visible longer for better visual continuity
  const cursorVisibleDuration = secondsToFrames(1.2, fps);
  const showCursor = frame < titleCompleteFrame + cursorVisibleDuration;
  const cursorOpacity = showCursor
    ? interpolate(
        frame % TIMING.CURSOR_BLINK_FRAMES,
        [0, TIMING.CURSOR_BLINK_FRAMES / 2, TIMING.CURSOR_BLINK_FRAMES],
        [1, 0, 1],
        { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
      )
    : 0;

  // Subtitle entrance - after title typing completes with a pause
  // Fine-tuned: slightly longer pause (0.4s instead of 0.3s) for better pacing
  const subtitleDelay = titleCompleteFrame + secondsToFrames(0.4, fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SPRING_CONFIGS.SMOOTH,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);
  const subtitleY = interpolate(subtitleSpring, [0, 1], [20, 0]);

  // Background glow that pulses subtly using centralized config
  const glowIntensity = interpolate(
    Math.sin(frame * GLOW_CONFIG.FREQUENCY.SLOW),
    [-1, 1],
    GLOW_CONFIG.INTENSITY.SUBTLE
  );

  // Scene entrance fade from intro scene
  // Fine-tuned: faster entrance (0.25s) for snappier transition
  const entranceFade = interpolate(
    frame,
    [0, secondsToFrames(0.25, fps)],
    [0, 1],
    {
      extrapolateRight: "clamp",
    }
  );

  return (
    <AbsoluteFill
      className="bg-zinc-900 overflow-hidden"
      style={{ opacity: entranceFade }}
    >
      {/* Audio: typing sound */}
      <Sequence durationInFrames={titleCompleteFrame + Math.floor(0.5 * fps)}>
        <Audio src={staticFile("sfx/typing.mp3")} volume={0.5} />
      </Sequence>

      {/* Audio: ui-appear for subtitle */}
      <Sequence from={subtitleDelay} durationInFrames={Math.floor(1 * fps)}>
        <Audio src={staticFile("sfx/ui-appear.mp3")} volume={0.6} />
      </Sequence>

      {/* Wallpaper Background */}
      <div className="absolute inset-0">
        <Img
          src={staticFile("einundzwanzig-wallpaper.png")}
          className="absolute inset-0 w-full h-full object-cover"
          style={{ opacity: 0.15 }}
        />
      </div>

      {/* Dark gradient overlay */}
      <div
        className="absolute inset-0"
        style={{
          background:
            "radial-gradient(circle at center, transparent 0%, rgba(24, 24, 27, 0.8) 60%, rgba(24, 24, 27, 0.98) 100%)",
        }}
      />

      {/* Center glow effect */}
      <div
        className="absolute inset-0 flex items-center justify-center"
        style={{ opacity: glowIntensity }}
      >
        <div
          style={{
            width: 800,
            height: 400,
            background:
              "radial-gradient(ellipse, rgba(247, 147, 26, 0.2) 0%, transparent 70%)",
            filter: "blur(60px)",
          }}
        />
      </div>

      {/* Content container */}
      <div className="absolute inset-0 flex flex-col items-center justify-center">
        {/* Title with typing animation */}
        <div className="text-center">
          <h1 className="text-7xl font-bold text-white tracking-wider">
            <span>{typedTitle}</span>
            <span
              className="text-orange-500"
              style={{ opacity: cursorOpacity }}
            >
              |
            </span>
          </h1>
        </div>

        {/* Subtitle */}
        <div
          className="mt-8 text-center"
          style={{
            opacity: subtitleOpacity,
            transform: `translateY(${subtitleY}px)`,
          }}
        >
          <p className="text-2xl text-zinc-300 font-light tracking-wide">
            {subtitleText}
          </p>
        </div>

        {/* Decorative line under subtitle */}
        <div
          className="mt-6"
          style={{
            opacity: subtitleOpacity,
            transform: `scaleX(${subtitleSpring})`,
          }}
        >
          <div
            className="h-0.5 bg-gradient-to-r from-transparent via-orange-500 to-transparent"
            style={{ width: 300 }}
          />
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
