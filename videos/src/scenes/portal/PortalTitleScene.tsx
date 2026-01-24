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

// Spring configurations from PRD
const SMOOTH = { damping: 200 };

// Typing animation configuration
const CHAR_FRAMES = 2; // Frames per character
const CURSOR_BLINK_FRAMES = 16;

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

  // Calculate typed characters for title
  const typedTitleChars = Math.min(
    titleText.length,
    Math.floor(frame / CHAR_FRAMES)
  );
  const typedTitle = titleText.slice(0, typedTitleChars);

  // Title typing complete frame
  const titleCompleteFrame = titleText.length * CHAR_FRAMES;

  // Cursor blink effect - only show while typing or shortly after
  const showCursor = frame < titleCompleteFrame + fps; // Show cursor for 1 second after typing completes
  const cursorOpacity = showCursor
    ? interpolate(
        frame % CURSOR_BLINK_FRAMES,
        [0, CURSOR_BLINK_FRAMES / 2, CURSOR_BLINK_FRAMES],
        [1, 0, 1],
        { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
      )
    : 0;

  // Subtitle entrance - after title typing completes
  const subtitleDelay = titleCompleteFrame + Math.floor(0.3 * fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SMOOTH,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);
  const subtitleY = interpolate(subtitleSpring, [0, 1], [20, 0]);

  // Background glow that pulses subtly
  const glowIntensity = interpolate(
    Math.sin(frame * 0.05),
    [-1, 1],
    [0.3, 0.5]
  );

  // Scene entrance fade from intro scene
  const entranceFade = interpolate(frame, [0, Math.floor(0.3 * fps)], [0, 1], {
    extrapolateRight: "clamp",
  });

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
