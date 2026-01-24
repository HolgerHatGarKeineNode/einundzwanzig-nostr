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
 * PortalTitleSceneMobile - Scene 2: Title Card for Mobile (4 seconds / 120 frames @ 30fps)
 *
 * Mobile layout adaptations:
 * - Smaller title text (text-5xl vs text-7xl)
 * - Title split across two lines for readability
 * - Adjusted spacing for portrait orientation
 */
export const PortalTitleSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Main title text - split for mobile
  const titleLine1 = "EINUNDZWANZIG";
  const titleLine2 = "PORTAL";
  const fullTitle = titleLine1 + " " + titleLine2;
  const subtitleText = "Das Herzstück der Bitcoin-Community";

  // Calculate typed characters for title
  const typedTitleChars = Math.min(
    fullTitle.length,
    Math.floor(frame / CHAR_FRAMES)
  );
  const typedTitle = fullTitle.slice(0, typedTitleChars);

  // Split typed text into two lines
  const typedLine1 = typedTitle.slice(0, Math.min(titleLine1.length, typedTitleChars));
  const typedLine2 = typedTitleChars > titleLine1.length + 1
    ? typedTitle.slice(titleLine1.length + 1)
    : "";

  // Title typing complete frame
  const titleCompleteFrame = fullTitle.length * CHAR_FRAMES;

  // Cursor blink effect - only show while typing or shortly after
  const showCursor = frame < titleCompleteFrame + fps;
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

  // Determine which line the cursor should be on
  const cursorOnLine2 = typedTitleChars > titleLine1.length;

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
            width: 600,
            height: 400,
            background:
              "radial-gradient(ellipse, rgba(247, 147, 26, 0.2) 0%, transparent 70%)",
            filter: "blur(60px)",
          }}
        />
      </div>

      {/* Content container */}
      <div className="absolute inset-0 flex flex-col items-center justify-center px-6">
        {/* Title with typing animation - two lines for mobile */}
        <div className="text-center">
          {/* Line 1 */}
          <h1 className="text-5xl font-bold text-white tracking-wider">
            <span>{typedLine1}</span>
            {!cursorOnLine2 && (
              <span
                className="text-orange-500"
                style={{ opacity: cursorOpacity }}
              >
                |
              </span>
            )}
          </h1>
          {/* Line 2 */}
          <h1 className="text-5xl font-bold text-white tracking-wider mt-2">
            <span>{typedLine2}</span>
            {cursorOnLine2 && (
              <span
                className="text-orange-500"
                style={{ opacity: cursorOpacity }}
              >
                |
              </span>
            )}
          </h1>
        </div>

        {/* Subtitle - smaller for mobile */}
        <div
          className="mt-8 text-center px-4"
          style={{
            opacity: subtitleOpacity,
            transform: `translateY(${subtitleY}px)`,
          }}
        >
          <p className="text-xl text-zinc-300 font-light tracking-wide">
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
            style={{ width: 250 }}
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
