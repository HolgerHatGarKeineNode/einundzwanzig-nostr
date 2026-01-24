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
import { StatsCounter } from "../../../components/StatsCounter";
import { SparklineChart } from "../../../components/SparklineChart";

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };

// Stagger delay between content cards
const CARD_STAGGER_DELAY = 8;

// Sample sparkline data
const MEETUP_TREND_DATA = [12, 15, 18, 14, 22, 25, 28, 32, 35, 42, 48, 55];
const USER_TREND_DATA = [100, 145, 180, 220, 280, 350, 420, 510, 620, 780, 950, 1100];
const EVENT_TREND_DATA = [5, 8, 12, 15, 18, 22, 28, 35, 42, 55, 68, 89];

/**
 * DashboardOverviewSceneMobile - Scene 3: Dashboard Overview for Mobile (12 seconds / 360 frames @ 30fps)
 *
 * Mobile layout adaptations:
 * - No sidebar (removed for portrait orientation)
 * - Vertical card stacking instead of horizontal row
 * - Smaller card widths optimized for 1080px width
 * - Reduced padding and spacing
 */
export const DashboardOverviewSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // 3D Perspective entrance animation (0-60 frames)
  const perspectiveSpring = spring({
    frame,
    fps,
    config: { damping: 20, stiffness: 60 },
  });

  const perspectiveX = interpolate(perspectiveSpring, [0, 1], [25, 0]);
  const perspectiveScale = interpolate(perspectiveSpring, [0, 1], [0.88, 1]);
  const perspectiveOpacity = interpolate(perspectiveSpring, [0, 1], [0, 1]);

  // Header entrance animation (delayed)
  const headerDelay = Math.floor(0.5 * fps);
  const headerSpring = spring({
    frame: frame - headerDelay,
    fps,
    config: SNAPPY,
  });
  const headerOpacity = interpolate(headerSpring, [0, 1], [0, 1]);
  const headerY = interpolate(headerSpring, [0, 1], [-30, 0]);

  // Subtle background glow pulse
  const glowIntensity = interpolate(
    Math.sin(frame * 0.04),
    [-1, 1],
    [0.3, 0.5]
  );

  // Mobile card width
  const cardWidth = 420;

  // Card entrance delays (staggered)
  const cardBaseDelay = Math.floor(1 * fps);

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: card-slide for initial entrance */}
      <Sequence from={cardBaseDelay} durationInFrames={Math.floor(1 * fps)}>
        <Audio src={staticFile("sfx/card-slide.mp3")} volume={0.5} />
      </Sequence>

      {/* Audio: ui-appear for stats */}
      <Sequence from={cardBaseDelay + Math.floor(0.5 * fps)} durationInFrames={Math.floor(0.5 * fps)}>
        <Audio src={staticFile("sfx/ui-appear.mp3")} volume={0.4} />
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
            "radial-gradient(circle at center, transparent 0%, rgba(24, 24, 27, 0.6) 50%, rgba(24, 24, 27, 0.95) 100%)",
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
        {/* Main Content - Centered for mobile */}
        <div className="absolute inset-0 flex flex-col items-center px-8 pt-16">
          {/* Header */}
          <div
            className="mb-8 text-center"
            style={{
              opacity: headerOpacity,
              transform: `translateY(${headerY}px)`,
            }}
          >
            <h1 className="text-4xl font-bold text-white mb-2">Dashboard</h1>
            <p className="text-lg text-zinc-400">
              Willkommen im Einundzwanzig Portal
            </p>
          </div>

          {/* Stats Cards - Vertical stack for mobile */}
          <div className="flex flex-col gap-5 w-full items-center">
            {/* Meetups Card */}
            <StatsCardMobile
              title="Meetups"
              delay={cardBaseDelay}
              width={cardWidth}
              glowIntensity={glowIntensity}
            >
              <StatsCounter
                targetNumber={204}
                delay={cardBaseDelay + 15}
                duration={60}
                label="Aktive Gruppen"
                fontSize={56}
                color="#f7931a"
              />
              <div className="mt-3">
                <SparklineChart
                  data={MEETUP_TREND_DATA}
                  width={cardWidth - 48}
                  height={50}
                  delay={cardBaseDelay + 30}
                  showFill={true}
                  fillOpacity={0.15}
                />
              </div>
            </StatsCardMobile>

            {/* Users Card */}
            <StatsCardMobile
              title="Benutzer"
              delay={cardBaseDelay + CARD_STAGGER_DELAY}
              width={cardWidth}
              glowIntensity={glowIntensity}
            >
              <StatsCounter
                targetNumber={1247}
                delay={cardBaseDelay + CARD_STAGGER_DELAY + 15}
                duration={60}
                label="Registrierte Nutzer"
                fontSize={56}
                color="#f7931a"
              />
              <div className="mt-3">
                <SparklineChart
                  data={USER_TREND_DATA}
                  width={cardWidth - 48}
                  height={50}
                  delay={cardBaseDelay + CARD_STAGGER_DELAY + 30}
                  showFill={true}
                  fillOpacity={0.15}
                />
              </div>
            </StatsCardMobile>

            {/* Events Card */}
            <StatsCardMobile
              title="Events"
              delay={cardBaseDelay + CARD_STAGGER_DELAY * 2}
              width={cardWidth}
              glowIntensity={glowIntensity}
            >
              <StatsCounter
                targetNumber={89}
                delay={cardBaseDelay + CARD_STAGGER_DELAY * 2 + 15}
                duration={60}
                label="Diese Woche"
                fontSize={56}
                color="#f7931a"
              />
              <div className="mt-3">
                <SparklineChart
                  data={EVENT_TREND_DATA}
                  width={cardWidth - 48}
                  height={50}
                  delay={cardBaseDelay + CARD_STAGGER_DELAY * 2 + 30}
                  showFill={true}
                  fillOpacity={0.15}
                />
              </div>
            </StatsCardMobile>
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
 * Stats Card container with animated entrance for mobile
 */
type StatsCardMobileProps = {
  title: string;
  delay: number;
  width: number;
  glowIntensity: number;
  children: React.ReactNode;
};

const StatsCardMobile: React.FC<StatsCardMobileProps> = ({
  title,
  delay,
  width,
  glowIntensity,
  children,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.85, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);
  const cardY = interpolate(cardSpring, [0, 1], [30, 0]);

  return (
    <div
      className="rounded-2xl bg-zinc-800/80 backdrop-blur-md border border-zinc-700/50 p-5"
      style={{
        width,
        transform: `translateY(${cardY}px) scale(${cardScale})`,
        opacity: cardOpacity,
        boxShadow: `0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.1), 0 8px 32px rgba(0, 0, 0, 0.4)`,
      }}
    >
      <h3 className="text-base font-semibold text-zinc-300 mb-3">{title}</h3>
      {children}
    </div>
  );
};
