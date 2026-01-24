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

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };

// Stagger delay between content cards
const CARD_STAGGER_DELAY = 5;

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

  // 3D Perspective entrance animation (0-60 frames)
  const perspectiveSpring = spring({
    frame,
    fps,
    config: { damping: 20, stiffness: 60 },
  });

  const perspectiveX = interpolate(perspectiveSpring, [0, 1], [30, 0]);
  const perspectiveScale = interpolate(perspectiveSpring, [0, 1], [0.85, 1]);
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

  // Sidebar dimensions
  const sidebarWidth = 280;

  // Content area calculations
  const contentPadding = 40;
  const cardWidth = 380;
  const cardGap = 24;

  // Card entrance delays (staggered)
  const cardBaseDelay = Math.floor(1 * fps); // Start after 1 second

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
            logoSrc={staticFile("einundzwanzig-logo.png")}
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
              {/* Meetups Card */}
              <StatsCard
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
                  fontSize={72}
                  color="#f7931a"
                />
                <div className="mt-4">
                  <SparklineChart
                    data={MEETUP_TREND_DATA}
                    width={cardWidth - 48}
                    height={60}
                    delay={cardBaseDelay + 30}
                    showFill={true}
                    fillOpacity={0.15}
                  />
                </div>
              </StatsCard>

              {/* Users Card */}
              <StatsCard
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
                  fontSize={72}
                  color="#f7931a"
                />
                <div className="mt-4">
                  <SparklineChart
                    data={USER_TREND_DATA}
                    width={cardWidth - 48}
                    height={60}
                    delay={cardBaseDelay + CARD_STAGGER_DELAY + 30}
                    showFill={true}
                    fillOpacity={0.15}
                  />
                </div>
              </StatsCard>

              {/* Events Card */}
              <StatsCard
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
                  fontSize={72}
                  color="#f7931a"
                />
                <div className="mt-4">
                  <SparklineChart
                    data={EVENT_TREND_DATA}
                    width={cardWidth - 48}
                    height={60}
                    delay={cardBaseDelay + CARD_STAGGER_DELAY * 2 + 30}
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
                delay={cardBaseDelay + CARD_STAGGER_DELAY * 3}
                glowIntensity={glowIntensity}
              />

              {/* Quick Stats */}
              <QuickStatsSection
                delay={cardBaseDelay + CARD_STAGGER_DELAY * 4}
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

  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.8, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);
  const cardY = interpolate(cardSpring, [0, 1], [30, 0]);

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

  const sectionSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const sectionOpacity = interpolate(sectionSpring, [0, 1], [0, 1]);
  const sectionY = interpolate(sectionSpring, [0, 1], [30, 0]);

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
            delay={delay + 10 + index * 8}
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

  const sectionSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const sectionOpacity = interpolate(sectionSpring, [0, 1], [0, 1]);
  const sectionY = interpolate(sectionSpring, [0, 1], [30, 0]);

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
          delay={delay + 18}
        />
        <QuickStatRow
          label="Aktive Nutzer"
          value={847}
          delay={delay + 26}
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

  const rowSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 15, stiffness: 90 },
  });

  const rowOpacity = interpolate(rowSpring, [0, 1], [0, 1]);
  const rowX = interpolate(rowSpring, [0, 1], [-20, 0]);

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
