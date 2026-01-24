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
import { DashboardSidebar, SidebarNavItem } from "../../components/DashboardSidebar";
import { StatsCounter } from "../../components/StatsCounter";
import { SparklineChart } from "../../components/SparklineChart";
import { ActivityItem } from "../../components/ActivityItem";
import {
  SPRING_CONFIGS,
  STAGGER_DELAYS,
  TIMING,
  GLOW_CONFIG,
  secondsToFrames,
} from "../../config/timing";

// Navigation items for the sidebar
const NAV_ITEMS: SidebarNavItem[] = [
  { label: "Dashboard", icon: "dashboard", isActive: true },
  { label: "EINSTELLUNGEN", isSection: true },
  { label: "Nostr Relays", icon: "nostr" },
  { label: "Meetups", icon: "meetups", badgeCount: 204 },
  { label: "Benutzer", icon: "users", badgeCount: 1247 },
  { label: "Events", icon: "events", badgeCount: 89 },
  { label: "KONFIGURATION", isSection: true },
  { label: "Sprache", icon: "language" },
  { label: "Interface", icon: "interface" },
  { label: "Provider", icon: "provider" },
];

// Sample sparkline data
const MEETUP_TREND_DATA = [12, 15, 18, 14, 22, 25, 28, 32, 35, 42, 48, 55];
const USER_TREND_DATA = [100, 145, 180, 220, 280, 350, 420, 510, 620, 780, 950, 1100];
const EVENT_TREND_DATA = [5, 8, 12, 15, 18, 22, 28, 35, 42, 55, 68, 89];

/**
 * DashboardOverviewScene - Scene 3: Dashboard Overview (12 seconds / 360 frames @ 30fps)
 *
 * Animation sequence:
 * 1. 3D perspective entrance (rotateX from 30° to 0°, scale from 0.85 to 1.0)
 * 2. Sidebar slides in from left with spring animation
 * 3. Header animates in with fade and translateY
 * 4. Content cards appear with staggered spring animations
 * 5. Stats counters animate up
 * 6. Audio: card-slide.mp3 for card entrances
 */
export const DashboardOverviewScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // 3D Perspective entrance animation using centralized config
  // Fine-tuned: slightly reduced initial rotation for less dramatic entrance
  const perspectiveSpring = spring({
    frame,
    fps,
    config: SPRING_CONFIGS.PERSPECTIVE,
  });

  const perspectiveX = interpolate(perspectiveSpring, [0, 1], [25, 0]); // Reduced from 30
  const perspectiveScale = interpolate(perspectiveSpring, [0, 1], [0.88, 1]); // Increased from 0.85
  const perspectiveOpacity = interpolate(perspectiveSpring, [0, 1], [0, 1]);

  // Header entrance animation (delayed) using centralized timing
  const headerDelay = secondsToFrames(TIMING.HEADER_DELAY, fps);
  const headerSpring = spring({
    frame: frame - headerDelay,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });
  const headerOpacity = interpolate(headerSpring, [0, 1], [0, 1]);
  const headerY = interpolate(headerSpring, [0, 1], [-30, 0]);

  // Subtle background glow pulse using centralized config
  const glowIntensity = interpolate(
    Math.sin(frame * GLOW_CONFIG.FREQUENCY.SLOW),
    [-1, 1],
    GLOW_CONFIG.INTENSITY.SUBTLE
  );

  // Sidebar dimensions
  const sidebarWidth = 280;

  // Content area calculations
  const contentPadding = 40;
  const cardWidth = 380;
  const cardGap = 24;

  // Card entrance delays (staggered) using centralized timing
  const cardBaseDelay = secondsToFrames(TIMING.CONTENT_BASE_DELAY, fps);

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
        {/* Main Layout: Sidebar + Content */}
        <div className="absolute inset-0 flex">
          {/* Sidebar */}
          <DashboardSidebar
            logoSrc={staticFile("einundzwanzig-square-inverted.svg")}
            navItems={NAV_ITEMS}
            width={sidebarWidth}
            height={1080}
            delay={0}
            staggerItems={true}
            staggerDelay={3}
          />

          {/* Main Content Area */}
          <div
            className="flex-1 flex flex-col"
            style={{
              padding: contentPadding,
              paddingLeft: contentPadding,
            }}
          >
            {/* Header */}
            <div
              className="mb-8"
              style={{
                opacity: headerOpacity,
                transform: `translateY(${headerY}px)`,
              }}
            >
              <h1 className="text-5xl font-bold text-white mb-2">Dashboard</h1>
              <p className="text-xl text-zinc-400">
                Willkommen im Einundzwanzig Portal
              </p>
            </div>

            {/* Stats Cards Row */}
            <div
              className="flex gap-6 mb-8"
              style={{ gap: cardGap }}
            >
              {/* Meetups Card - Fine-tuned: increased stagger delay between cards for better visual flow */}
              <StatsCard
                title="Meetups"
                delay={cardBaseDelay}
                width={cardWidth}
                glowIntensity={glowIntensity}
              >
                <StatsCounter
                  targetNumber={204}
                  delay={cardBaseDelay + TIMING.COUNTER_PRE_DELAY}
                  duration={TIMING.COUNTER_DURATION}
                  label="Aktive Gruppen"
                  fontSize={72}
                  color="#f7931a"
                />
                <div className="mt-4">
                  <SparklineChart
                    data={MEETUP_TREND_DATA}
                    width={cardWidth - 48}
                    height={60}
                    delay={cardBaseDelay + TIMING.SPARKLINE_PRE_DELAY}
                    showFill={true}
                    fillOpacity={0.15}
                  />
                </div>
              </StatsCard>

              {/* Users Card */}
              <StatsCard
                title="Benutzer"
                delay={cardBaseDelay + STAGGER_DELAYS.CARD}
                width={cardWidth}
                glowIntensity={glowIntensity}
              >
                <StatsCounter
                  targetNumber={1247}
                  delay={cardBaseDelay + STAGGER_DELAYS.CARD + TIMING.COUNTER_PRE_DELAY}
                  duration={TIMING.COUNTER_DURATION}
                  label="Registrierte Nutzer"
                  fontSize={72}
                  color="#f7931a"
                />
                <div className="mt-4">
                  <SparklineChart
                    data={USER_TREND_DATA}
                    width={cardWidth - 48}
                    height={60}
                    delay={cardBaseDelay + STAGGER_DELAYS.CARD + TIMING.SPARKLINE_PRE_DELAY}
                    showFill={true}
                    fillOpacity={0.15}
                  />
                </div>
              </StatsCard>

              {/* Events Card */}
              <StatsCard
                title="Events"
                delay={cardBaseDelay + STAGGER_DELAYS.CARD * 2}
                width={cardWidth}
                glowIntensity={glowIntensity}
              >
                <StatsCounter
                  targetNumber={89}
                  delay={cardBaseDelay + STAGGER_DELAYS.CARD * 2 + TIMING.COUNTER_PRE_DELAY}
                  duration={TIMING.COUNTER_DURATION}
                  label="Diese Woche"
                  fontSize={72}
                  color="#f7931a"
                />
                <div className="mt-4">
                  <SparklineChart
                    data={EVENT_TREND_DATA}
                    width={cardWidth - 48}
                    height={60}
                    delay={cardBaseDelay + STAGGER_DELAYS.CARD * 2 + TIMING.SPARKLINE_PRE_DELAY}
                    showFill={true}
                    fillOpacity={0.15}
                  />
                </div>
              </StatsCard>
            </div>

            {/* Activity Section */}
            <div className="flex gap-6" style={{ gap: cardGap }}>
              {/* Activity Feed */}
              <ActivitySection
                delay={cardBaseDelay + STAGGER_DELAYS.CARD * 3}
                glowIntensity={glowIntensity}
              />

              {/* Quick Stats */}
              <QuickStatsSection
                delay={cardBaseDelay + STAGGER_DELAYS.CARD * 4}
                glowIntensity={glowIntensity}
              />
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
 * Stats Card container with animated entrance
 */
type StatsCardProps = {
  title: string;
  delay: number;
  width: number;
  glowIntensity: number;
  children: React.ReactNode;
};

const StatsCard: React.FC<StatsCardProps> = ({
  title,
  delay,
  width,
  glowIntensity,
  children,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Fine-tuned: Using centralized SNAPPY config for consistent card animations
  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });

  // Fine-tuned: Slightly increased initial scale for smoother entrance
  const cardScale = interpolate(cardSpring, [0, 1], [0.85, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced Y translation for subtler entrance
  const cardY = interpolate(cardSpring, [0, 1], [25, 0]);

  return (
    <div
      className="rounded-2xl bg-zinc-800/80 backdrop-blur-md border border-zinc-700/50 p-6"
      style={{
        width,
        transform: `translateY(${cardY}px) scale(${cardScale})`,
        opacity: cardOpacity,
        boxShadow: `0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.1), 0 8px 32px rgba(0, 0, 0, 0.4)`,
      }}
    >
      <h3 className="text-lg font-semibold text-zinc-300 mb-4">{title}</h3>
      {children}
    </div>
  );
};

/**
 * Activity Feed Section
 */
type ActivitySectionProps = {
  delay: number;
  glowIntensity: number;
};

const ActivitySection: React.FC<ActivitySectionProps> = ({
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Fine-tuned: Using centralized SNAPPY config
  const sectionSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });

  const sectionOpacity = interpolate(sectionSpring, [0, 1], [0, 1]);
  const sectionY = interpolate(sectionSpring, [0, 1], [25, 0]); // Fine-tuned: reduced Y

  const activities = [
    { eventName: "EINUNDZWANZIG Kempten", timestamp: "vor 13 Stunden", badgeText: "Neuer Termin" },
    { eventName: "EINUNDZWANZIG Frankfurt", timestamp: "vor 1 Tag", badgeText: "Update" },
    { eventName: "EINUNDZWANZIG Saarland", timestamp: "vor 2 Tagen", badgeText: "Neuer Termin" },
  ];

  return (
    <div
      className="flex-1 rounded-2xl bg-zinc-800/80 backdrop-blur-md border border-zinc-700/50 p-6"
      style={{
        transform: `translateY(${sectionY}px)`,
        opacity: sectionOpacity,
        boxShadow: `0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.1), 0 8px 32px rgba(0, 0, 0, 0.4)`,
      }}
    >
      <h3 className="text-lg font-semibold text-zinc-300 mb-4">Letzte Aktivitäten</h3>
      <div className="flex flex-col gap-3">
        {activities.map((activity, index) => (
          <ActivityItem
            key={index}
            eventName={activity.eventName}
            timestamp={activity.timestamp}
            badgeText={activity.badgeText}
            delay={delay + 10 + index * STAGGER_DELAYS.LIST_ITEM}
            width={480}
          />
        ))}
      </div>
    </div>
  );
};

/**
 * Quick Stats Section with additional metrics
 */
type QuickStatsSectionProps = {
  delay: number;
  glowIntensity: number;
};

const QuickStatsSection: React.FC<QuickStatsSectionProps> = ({
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Fine-tuned: Using centralized SNAPPY config
  const sectionSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });

  const sectionOpacity = interpolate(sectionSpring, [0, 1], [0, 1]);
  const sectionY = interpolate(sectionSpring, [0, 1], [25, 0]); // Fine-tuned: reduced Y

  return (
    <div
      className="rounded-2xl bg-zinc-800/80 backdrop-blur-md border border-zinc-700/50 p-6"
      style={{
        width: 320,
        transform: `translateY(${sectionY}px)`,
        opacity: sectionOpacity,
        boxShadow: `0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.1), 0 8px 32px rgba(0, 0, 0, 0.4)`,
      }}
    >
      <h3 className="text-lg font-semibold text-zinc-300 mb-4">Schnellübersicht</h3>
      <div className="flex flex-col gap-4">
        <QuickStatRow
          label="Länder"
          value={23}
          delay={delay + 10}
        />
        <QuickStatRow
          label="Neue diese Woche"
          value={12}
          delay={delay + 10 + STAGGER_DELAYS.QUICK_STAT}
        />
        <QuickStatRow
          label="Aktive Nutzer"
          value={847}
          delay={delay + 10 + STAGGER_DELAYS.QUICK_STAT * 2}
        />
      </div>
    </div>
  );
};

/**
 * Individual quick stat row
 */
type QuickStatRowProps = {
  label: string;
  value: number;
  delay: number;
};

const QuickStatRow: React.FC<QuickStatRowProps> = ({ label, value, delay }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Fine-tuned: Using centralized ROW config
  const rowSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.ROW,
  });

  const rowOpacity = interpolate(rowSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced X translation for subtler entrance
  const rowX = interpolate(rowSpring, [0, 1], [-15, 0]);

  // Animated counter
  const counterValue = interpolate(rowSpring, [0, 1], [0, value]);

  return (
    <div
      className="flex items-center justify-between"
      style={{
        opacity: rowOpacity,
        transform: `translateX(${rowX}px)`,
      }}
    >
      <span className="text-zinc-400 text-base">{label}</span>
      <span
        className="text-white font-bold text-xl tabular-nums"
        style={{ fontFamily: "Inconsolata, monospace" }}
      >
        {Math.round(counterValue)}
      </span>
    </div>
  );
};
