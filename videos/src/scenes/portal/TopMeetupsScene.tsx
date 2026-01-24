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
import { SparklineChart } from "../../components/SparklineChart";

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };
const BOUNCY = { damping: 12 };

// Stagger delay between meetup items (in frames)
const MEETUP_STAGGER_DELAY = 15;

// Top meetups data with sparkline growth data
const TOP_MEETUPS_DATA = [
  {
    name: "EINUNDZWANZIG Saarland",
    logoFile: "EinundzwanzigSaarbrucken.png",
    userCount: 26,
    location: "Saarbrücken",
    sparklineData: [5, 8, 10, 12, 14, 16, 18, 20, 22, 24, 25, 26],
    rank: 1,
  },
  {
    name: "EINUNDZWANZIG Frankfurt am Main",
    logoFile: "EinundzwanzigFrankfurtAmMain.png",
    userCount: 26,
    location: "Frankfurt",
    sparklineData: [4, 7, 10, 13, 15, 17, 19, 21, 23, 24, 25, 26],
    rank: 2,
  },
  {
    name: "EINUNDZWANZIG Kempten",
    logoFile: "EinundzwanzigKempten.png",
    userCount: 20,
    location: "Kempten",
    sparklineData: [3, 5, 7, 9, 11, 13, 15, 16, 17, 18, 19, 20],
    rank: 3,
  },
  {
    name: "EINUNDZWANZIG Pfalz",
    logoFile: "EinundzwanzigRheinhessen.png",
    userCount: 17,
    location: "Pfalz",
    sparklineData: [2, 4, 6, 8, 9, 11, 12, 13, 14, 15, 16, 17],
    rank: 4,
  },
  {
    name: "EINUNDZWANZIG Trier",
    logoFile: "EinundzwanzigTrier.png",
    userCount: 15,
    location: "Trier",
    sparklineData: [2, 3, 5, 6, 8, 9, 10, 11, 12, 13, 14, 15],
    rank: 5,
  },
];

// Maximum count for calculating relative bar widths
const MAX_USER_COUNT = Math.max(...TOP_MEETUPS_DATA.map((m) => m.userCount));

/**
 * TopMeetupsScene - Scene 6: Top Meetups (10 seconds / 300 frames @ 30fps)
 *
 * Animation sequence:
 * 1. 3D perspective entrance with smooth transition
 * 2. Section header animates in with fade + translateY
 * 3. Meetups appear sequentially with staggered entrance:
 *    - Rank badge bounces in
 *    - Logo and meetup info slide in from left
 *    - SparklineChart draws next to each meetup
 *    - User counts animate up
 * 4. Leading meetup gets highlighted with glow effect
 * 5. Audio: checkmark-pop.mp3 plays per meetup entrance
 */
export const TopMeetupsScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // 3D Perspective entrance animation (0-60 frames)
  const perspectiveSpring = spring({
    frame,
    fps,
    config: { damping: 20, stiffness: 60 },
  });

  const perspectiveX = interpolate(perspectiveSpring, [0, 1], [20, 0]);
  const perspectiveScale = interpolate(perspectiveSpring, [0, 1], [0.9, 1]);
  const perspectiveOpacity = interpolate(perspectiveSpring, [0, 1], [0, 1]);

  // Header entrance animation (delayed)
  const headerDelay = Math.floor(0.3 * fps);
  const headerSpring = spring({
    frame: frame - headerDelay,
    fps,
    config: SNAPPY,
  });
  const headerOpacity = interpolate(headerSpring, [0, 1], [0, 1]);
  const headerY = interpolate(headerSpring, [0, 1], [-40, 0]);

  // Subtitle animation (slightly more delayed)
  const subtitleDelay = Math.floor(0.5 * fps);
  const subtitleSpring = spring({
    frame: frame - subtitleDelay,
    fps,
    config: SNAPPY,
  });
  const subtitleOpacity = interpolate(subtitleSpring, [0, 1], [0, 1]);
  const subtitleY = interpolate(subtitleSpring, [0, 1], [20, 0]);

  // Base delay for meetup items
  const meetupBaseDelay = Math.floor(1 * fps);

  // Subtle glow pulse for leading meetup
  const glowIntensity = interpolate(
    Math.sin(frame * 0.05),
    [-1, 1],
    [0.4, 0.8]
  );

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: checkmark-pop for each meetup entrance */}
      {TOP_MEETUPS_DATA.map((_, index) => (
        <Sequence
          key={`audio-${index}`}
          from={meetupBaseDelay + index * MEETUP_STAGGER_DELAY}
          durationInFrames={Math.floor(0.5 * fps)}
        >
          <Audio src={staticFile("sfx/checkmark-pop.mp3")} volume={0.4} />
        </Sequence>
      ))}

      {/* Audio: slide-in for section entrance */}
      <Sequence from={headerDelay} durationInFrames={Math.floor(1 * fps)}>
        <Audio src={staticFile("sfx/slide-in.mp3")} volume={0.5} />
      </Sequence>

      {/* Wallpaper Background */}
      <div className="absolute inset-0">
        <Img
          src={staticFile("einundzwanzig-wallpaper.png")}
          className="absolute inset-0 w-full h-full object-cover"
          style={{ opacity: 0.1 }}
        />
      </div>

      {/* Dark gradient overlay */}
      <div
        className="absolute inset-0"
        style={{
          background:
            "radial-gradient(circle at 40% 50%, transparent 0%, rgba(24, 24, 27, 0.5) 40%, rgba(24, 24, 27, 0.95) 100%)",
        }}
      />

      {/* 3D Perspective Container */}
      <div
        className="absolute inset-0"
        style={{
          transform: `perspective(1200px) rotateX(${perspectiveX}deg) scale(${perspectiveScale})`,
          transformOrigin: "center center",
          opacity: perspectiveOpacity,
        }}
      >
        {/* Main Content */}
        <div className="absolute inset-0 flex flex-col items-center justify-center px-20">
          {/* Section Header */}
          <div
            className="text-center mb-10"
            style={{
              opacity: headerOpacity,
              transform: `translateY(${headerY}px)`,
            }}
          >
            <h1 className="text-5xl font-bold text-white mb-3">Top Meetups</h1>
            <p
              className="text-xl text-zinc-400"
              style={{
                opacity: subtitleOpacity,
                transform: `translateY(${subtitleY}px)`,
              }}
            >
              Die aktivsten lokalen Bitcoin-Communities
            </p>
          </div>

          {/* Meetups List */}
          <div className="w-full max-w-4xl">
            <div className="flex flex-col gap-4">
              {TOP_MEETUPS_DATA.map((meetup, index) => (
                <MeetupRow
                  key={meetup.name}
                  meetup={meetup}
                  maxCount={MAX_USER_COUNT}
                  delay={meetupBaseDelay + index * MEETUP_STAGGER_DELAY}
                  glowIntensity={glowIntensity}
                  isLeading={index === 0}
                />
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Vignette overlay */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          boxShadow: "inset 0 0 200px 50px rgba(0, 0, 0, 0.6)",
        }}
      />
    </AbsoluteFill>
  );
};

/**
 * Meetup row with rank, logo, info, progress bar, and sparkline
 */
type MeetupRowProps = {
  meetup: {
    name: string;
    logoFile: string;
    userCount: number;
    location: string;
    sparklineData: number[];
    rank: number;
  };
  maxCount: number;
  delay: number;
  glowIntensity: number;
  isLeading: boolean;
};

const MeetupRow: React.FC<MeetupRowProps> = ({
  meetup,
  maxCount,
  delay,
  glowIntensity,
  isLeading,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Row entrance spring
  const rowSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const rowOpacity = interpolate(rowSpring, [0, 1], [0, 1]);
  const rowX = interpolate(rowSpring, [0, 1], [-80, 0]);

  // Rank badge bounce
  const rankSpring = spring({
    frame: adjustedFrame - 5,
    fps,
    config: BOUNCY,
  });
  const rankScale = interpolate(rankSpring, [0, 1], [0, 1]);

  // User count animation
  const countSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 18, stiffness: 70 },
    durationInFrames: 45,
  });
  const displayCount = Math.round(countSpring * meetup.userCount);

  // Progress bar animation
  const barProgress = interpolate(rowSpring, [0, 1], [0, meetup.userCount / maxCount]);

  // Sparkline delay (appears slightly after the bar)
  const sparklineDelay = delay + 25;

  // Dynamic glow for leading meetup
  const leadingGlow = isLeading
    ? `0 0 ${40 * glowIntensity}px rgba(247, 147, 26, 0.4), 0 0 ${20 * glowIntensity}px rgba(247, 147, 26, 0.3)`
    : "none";

  return (
    <div
      className="flex items-center gap-4 p-4 rounded-xl"
      style={{
        opacity: rowOpacity,
        transform: `translateX(${rowX}px)`,
        backgroundColor: isLeading
          ? "rgba(247, 147, 26, 0.1)"
          : "rgba(39, 39, 42, 0.6)",
        border: isLeading
          ? "1px solid rgba(247, 147, 26, 0.3)"
          : "1px solid rgba(63, 63, 70, 0.5)",
        boxShadow: leadingGlow,
      }}
    >
      {/* Rank Badge */}
      <div
        className="flex-shrink-0 w-12 h-12 rounded-full flex items-center justify-center font-bold text-xl"
        style={{
          backgroundColor: isLeading ? "#f7931a" : "rgba(63, 63, 70, 0.8)",
          color: isLeading ? "#18181b" : "#a1a1aa",
          transform: `scale(${rankScale})`,
          boxShadow: isLeading
            ? `0 0 ${15 * glowIntensity}px rgba(247, 147, 26, 0.5)`
            : "none",
        }}
      >
        {meetup.rank}
      </div>

      {/* Logo */}
      <div
        className="flex-shrink-0 w-14 h-14 rounded-lg overflow-hidden bg-zinc-800 flex items-center justify-center"
        style={{
          boxShadow: isLeading
            ? `0 0 ${10 * glowIntensity}px rgba(247, 147, 26, 0.3)`
            : "none",
        }}
      >
        <Img
          src={staticFile(`logos/${meetup.logoFile}`)}
          style={{
            width: 48,
            height: 48,
            objectFit: "contain",
          }}
        />
      </div>

      {/* Meetup Info + Progress Bar */}
      <div className="flex-1 min-w-0">
        {/* Name and Location */}
        <div className="flex items-center gap-2 mb-2">
          <span
            className="font-bold text-lg truncate"
            style={{
              color: isLeading ? "#f7931a" : "#ffffff",
            }}
          >
            {meetup.name}
          </span>
          <span className="text-zinc-500 text-sm flex-shrink-0">
            • {meetup.location}
          </span>
        </div>

        {/* Progress Bar */}
        <div className="h-2 rounded-full bg-zinc-700/50 overflow-hidden">
          <div
            className="h-full rounded-full"
            style={{
              width: `${barProgress * 100}%`,
              backgroundColor: isLeading ? "#f7931a" : "#71717a",
              boxShadow: isLeading
                ? `0 0 ${10 * glowIntensity}px rgba(247, 147, 26, 0.5)`
                : "none",
              transition: "width 0.1s ease-out",
            }}
          />
        </div>
      </div>

      {/* User Count */}
      <div
        className="flex-shrink-0 text-right font-bold tabular-nums"
        style={{
          color: isLeading ? "#f7931a" : "#a1a1aa",
          fontSize: "1.5rem",
          fontFamily: "Inconsolata, monospace",
          minWidth: 50,
          textShadow: isLeading
            ? `0 0 ${10 * glowIntensity}px rgba(247, 147, 26, 0.5)`
            : "none",
        }}
      >
        {displayCount}
      </div>

      {/* Sparkline Chart */}
      <div
        className="flex-shrink-0 rounded-lg bg-zinc-800/50 p-2"
        style={{
          boxShadow: isLeading
            ? `0 0 ${10 * glowIntensity}px rgba(247, 147, 26, 0.2)`
            : "none",
        }}
      >
        <SparklineChart
          data={meetup.sparklineData}
          width={100}
          height={36}
          delay={sparklineDelay}
          color={isLeading ? "#f7931a" : "#71717a"}
          strokeWidth={2}
          showFill={true}
          fillOpacity={0.2}
          showGlow={isLeading}
        />
      </div>
    </div>
  );
};
