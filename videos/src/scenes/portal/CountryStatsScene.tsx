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
import { CountryBar } from "../../components/CountryBar";
import { SparklineChart } from "../../components/SparklineChart";

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };
const BOUNCY = { damping: 12 };

// Stagger delay between country items (in frames)
const COUNTRY_STAGGER_DELAY = 12;

// Country statistics data
const COUNTRY_DATA = [
  {
    name: "Germany",
    flagEmoji: "🇩🇪",
    userCount: 458,
    sparklineData: [80, 120, 180, 220, 280, 320, 350, 390, 420, 445, 455, 458],
  },
  {
    name: "Austria",
    flagEmoji: "🇦🇹",
    userCount: 59,
    sparklineData: [10, 15, 22, 28, 35, 40, 45, 50, 54, 57, 58, 59],
  },
  {
    name: "Switzerland",
    flagEmoji: "🇨🇭",
    userCount: 34,
    sparklineData: [5, 8, 12, 15, 18, 22, 25, 28, 30, 32, 33, 34],
  },
  {
    name: "Luxembourg",
    flagEmoji: "🇱🇺",
    userCount: 8,
    sparklineData: [1, 2, 2, 3, 4, 5, 5, 6, 7, 7, 8, 8],
  },
  {
    name: "Bulgaria",
    flagEmoji: "🇧🇬",
    userCount: 7,
    sparklineData: [1, 1, 2, 2, 3, 4, 4, 5, 5, 6, 7, 7],
  },
  {
    name: "Spain",
    flagEmoji: "🇪🇸",
    userCount: 3,
    sparklineData: [0, 0, 1, 1, 1, 2, 2, 2, 2, 3, 3, 3],
  },
];

// Maximum count for calculating relative bar widths
const MAX_USER_COUNT = Math.max(...COUNTRY_DATA.map((c) => c.userCount));

/**
 * CountryStatsScene - Scene 5: Top Länder (12 seconds / 360 frames @ 30fps)
 *
 * Animation sequence:
 * 1. Smooth transition from previous scene (3D perspective entrance)
 * 2. Section header animates in with fade + translateY
 * 3. Countries appear sequentially with staggered entrance:
 *    - CountryBar component slides in from left
 *    - SparklineChart draws in next to each country
 *    - User counts animate up
 * 4. Audio: success-chime.mp3 plays per country entrance
 */
export const CountryStatsScene: React.FC = () => {
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

  // Base delay for country items
  const countryBaseDelay = Math.floor(1 * fps);

  // Subtle glow pulse
  const glowIntensity = interpolate(
    Math.sin(frame * 0.05),
    [-1, 1],
    [0.3, 0.6]
  );

  // Total users count animation
  const totalUsers = COUNTRY_DATA.reduce((sum, c) => sum + c.userCount, 0);
  const totalDelay = Math.floor(0.8 * fps);
  const totalSpring = spring({
    frame: frame - totalDelay,
    fps,
    config: { damping: 18, stiffness: 70 },
    durationInFrames: 60,
  });
  const displayTotal = Math.round(totalSpring * totalUsers);

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: success-chime for each country entrance */}
      {COUNTRY_DATA.map((_, index) => (
        <Sequence
          key={`audio-${index}`}
          from={countryBaseDelay + index * COUNTRY_STAGGER_DELAY}
          durationInFrames={Math.floor(0.5 * fps)}
        >
          <Audio src={staticFile("sfx/success-chime.mp3")} volume={0.3} />
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
            className="text-center mb-8"
            style={{
              opacity: headerOpacity,
              transform: `translateY(${headerY}px)`,
            }}
          >
            <h1 className="text-5xl font-bold text-white mb-3">
              Community nach Ländern
            </h1>
            <p
              className="text-xl text-zinc-400"
              style={{
                opacity: subtitleOpacity,
                transform: `translateY(${subtitleY}px)`,
              }}
            >
              Die deutschsprachige Bitcoin-Community wächst überall
            </p>
          </div>

          {/* Total Users Badge */}
          <TotalUsersBadge
            totalUsers={displayTotal}
            delay={totalDelay}
            glowIntensity={glowIntensity}
          />

          {/* Countries Grid */}
          <div className="w-full max-w-5xl mt-8">
            <div className="grid grid-cols-2 gap-6">
              {COUNTRY_DATA.map((country, index) => (
                <CountryRow
                  key={country.name}
                  country={country}
                  maxCount={MAX_USER_COUNT}
                  delay={countryBaseDelay + index * COUNTRY_STAGGER_DELAY}
                  glowIntensity={glowIntensity}
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
 * Total users badge component
 */
type TotalUsersBadgeProps = {
  totalUsers: number;
  delay: number;
  glowIntensity: number;
};

const TotalUsersBadge: React.FC<TotalUsersBadgeProps> = ({
  totalUsers,
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const badgeSpring = spring({
    frame: adjustedFrame,
    fps,
    config: BOUNCY,
  });

  const badgeScale = interpolate(badgeSpring, [0, 1], [0.8, 1]);
  const badgeOpacity = interpolate(badgeSpring, [0, 1], [0, 1]);

  return (
    <div
      className="flex items-center gap-4 px-8 py-4 rounded-2xl"
      style={{
        backgroundColor: "rgba(247, 147, 26, 0.15)",
        border: "1px solid rgba(247, 147, 26, 0.3)",
        boxShadow: `0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.2)`,
        transform: `scale(${badgeScale})`,
        opacity: badgeOpacity,
      }}
    >
      <GlobeIcon />
      <div className="flex items-baseline gap-3">
        <span
          className="text-4xl font-bold tabular-nums"
          style={{
            color: "#f7931a",
            fontFamily: "Inconsolata, monospace",
            textShadow: `0 0 ${15 * glowIntensity}px rgba(247, 147, 26, 0.5)`,
          }}
        >
          {totalUsers}
        </span>
        <span className="text-xl text-zinc-300">Nutzer weltweit</span>
      </div>
    </div>
  );
};

/**
 * Country row with bar and sparkline
 */
type CountryRowProps = {
  country: {
    name: string;
    flagEmoji: string;
    userCount: number;
    sparklineData: number[];
  };
  maxCount: number;
  delay: number;
  glowIntensity: number;
};

const CountryRow: React.FC<CountryRowProps> = ({
  country,
  maxCount,
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const rowSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const rowOpacity = interpolate(rowSpring, [0, 1], [0, 1]);
  const rowX = interpolate(rowSpring, [0, 1], [-50, 0]);

  // Sparkline delay (appears slightly after the bar)
  const sparklineDelay = delay + 20;

  return (
    <div
      className="flex items-center gap-4"
      style={{
        opacity: rowOpacity,
        transform: `translateX(${rowX}px)`,
      }}
    >
      {/* Country Bar */}
      <div className="flex-1">
        <CountryBar
          countryName={country.name}
          flagEmoji={country.flagEmoji}
          userCount={country.userCount}
          maxCount={maxCount}
          width={380}
          delay={delay}
          accentColor="#f7931a"
          showCount={true}
        />
      </div>

      {/* Sparkline Chart */}
      <div
        className="rounded-lg bg-zinc-800/50 p-3"
        style={{
          boxShadow: `0 0 ${15 * glowIntensity}px rgba(247, 147, 26, 0.1)`,
        }}
      >
        <SparklineChart
          data={country.sparklineData}
          width={120}
          height={40}
          delay={sparklineDelay}
          color="#f7931a"
          strokeWidth={2}
          showFill={true}
          fillOpacity={0.2}
          showGlow={true}
        />
      </div>
    </div>
  );
};

/**
 * Globe icon SVG
 */
const GlobeIcon: React.FC = () => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="#f7931a"
    strokeWidth={2}
    strokeLinecap="round"
    strokeLinejoin="round"
    style={{ width: 32, height: 32 }}
  >
    <circle cx="12" cy="12" r="10" />
    <path d="M2 12h20" />
    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
  </svg>
);
