import {
  AbsoluteFill,
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
  staticFile,
  Sequence,
  random,
} from "remotion";
import { Audio } from "@remotion/media";
import {
  DashboardSidebar,
  SidebarNavItem,
} from "../../components/DashboardSidebar";
import { SparklineChart } from "../../components/SparklineChart";
import { ActivityItem } from "../../components/ActivityItem";
import {
  SPRING_CONFIGS,
  STAGGER_DELAYS,
  TIMING,
  GLOW_CONFIG,
  secondsToFrames,
} from "../../config/timing";

// Navigation items for the sidebar - matching screenshot
const NAV_ITEMS: SidebarNavItem[] = [
  { label: "App", isSection: true },
  { label: "Dashboard", icon: "dashboard", isActive: true },
  { label: "Meetups", isSection: true },
  { label: "Meetups", icon: "meetups", badgeCount: 204 },
  { label: "Alle Meetups", icon: "settings", badgeCount: 278 },
  { label: "Karte", icon: "interface" },
  { label: "Welt-Karte", icon: "language", badgeCount: 278 },
  { label: "Community & Dienste", isSection: true },
  { label: "Self Hosted Services", icon: "provider", badgeCount: 13 },
  { label: "Kurse", isSection: true },
  { label: "Kurse", icon: "events", badgeCount: 38 },
  { label: "Dozenten", icon: "users", badgeCount: 19 },
];

// Sample sparkline data for countries (ascending trend like screenshot)
const COUNTRY_SPARKLINE_DATA = {
  germany: [35, 42, 48, 52, 58, 65, 72, 80, 88, 95, 105, 120],
  austria: [12, 15, 18, 20, 24, 28, 32, 36, 42, 48, 52, 58],
  switzerland: [8, 10, 12, 14, 18, 21, 24, 28, 30, 32, 34, 38],
  luxembourg: [2, 3, 4, 4, 5, 6, 6, 7, 7, 8, 8, 9],
  bulgaria: [2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8],
};

// Sample sparkline data for meetups
const MEETUP_SPARKLINE_DATA = {
  saarland: [8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30],
  frankfurt: [6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28],
  kempten: [4, 6, 8, 9, 11, 13, 15, 17, 19, 21, 22, 24],
  pfalz: [3, 4, 5, 6, 8, 9, 10, 12, 14, 15, 17, 18],
  trier: [2, 3, 4, 5, 6, 7, 8, 10, 11, 13, 14, 16],
};

// Top countries data
const TOP_COUNTRIES = [
  { name: "Germany", flag: "🇩🇪", users: 459, data: COUNTRY_SPARKLINE_DATA.germany },
  { name: "Austria", flag: "🇦🇹", users: 58, data: COUNTRY_SPARKLINE_DATA.austria },
  { name: "Switzerland", flag: "🇨🇭", users: 34, data: COUNTRY_SPARKLINE_DATA.switzerland },
  { name: "Luxembourg", flag: "🇱🇺", users: 8, data: COUNTRY_SPARKLINE_DATA.luxembourg },
  { name: "Bulgaria", flag: "🇧🇬", users: 7, data: COUNTRY_SPARKLINE_DATA.bulgaria },
];

// Top meetups data
const TOP_MEETUPS = [
  { name: "Einundzwanzig Saarland", users: 26, data: MEETUP_SPARKLINE_DATA.saarland },
  { name: "Einundzwanzig Frankfurt am Main", users: 26, data: MEETUP_SPARKLINE_DATA.frankfurt },
  { name: "Einundzwanzig Kempten", users: 20, data: MEETUP_SPARKLINE_DATA.kempten },
  { name: "Einundzwanzig Pfalz", users: 17, data: MEETUP_SPARKLINE_DATA.pfalz },
  { name: "Einundzwanzig Trier", users: 15, data: MEETUP_SPARKLINE_DATA.trier },
];

/**
 * DashboardOverviewScene - Scene 3: Dashboard Overview (12 seconds / 360 frames @ 30fps)
 *
 * Layout matching screenshot:
 * - Left column: "Meine nächsten Meetup Termine" + "Top Länder"
 * - Middle column: "Meine Meetups" + "Top Meetups"
 * - Right column: "Aktivitäten"
 *
 * Cinematic effects:
 * - Film grain overlay
 * - Subtle camera drift/breathing
 * - Light rays from top
 * - Enhanced orange accent glows
 * - Animated vignette
 * - Bloom on highlights
 */
export const DashboardOverviewScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width: videoWidth, height: videoHeight } = useVideoConfig();

  // 3D Perspective entrance animation
  const perspectiveSpring = spring({
    frame,
    fps,
    config: SPRING_CONFIGS.PERSPECTIVE,
  });

  const perspectiveX = interpolate(perspectiveSpring, [0, 1], [20, 0]);
  const perspectiveScale = interpolate(perspectiveSpring, [0, 1], [0.92, 1]);
  const perspectiveOpacity = interpolate(perspectiveSpring, [0, 1], [0, 1]);

  // Cinematic camera breathing/drift effect
  const cameraBreathX = interpolate(
    Math.sin(frame * 0.015),
    [-1, 1],
    [-3, 3]
  );
  const cameraBreathY = interpolate(
    Math.cos(frame * 0.012),
    [-1, 1],
    [-2, 2]
  );
  const cameraZoom = interpolate(
    Math.sin(frame * 0.008),
    [-1, 1],
    [1, 1.008]
  );

  // Subtle background glow pulse
  const glowIntensity = interpolate(
    Math.sin(frame * GLOW_CONFIG.FREQUENCY.SLOW),
    [-1, 1],
    GLOW_CONFIG.INTENSITY.SUBTLE
  );

  // Light ray animation
  const lightRayOpacity = interpolate(
    Math.sin(frame * 0.03),
    [-1, 1],
    [0.03, 0.08]
  );
  const lightRayAngle = interpolate(frame, [0, 360], [0, 5]);

  // Sidebar dimensions
  const sidebarWidth = 220;
  const contentGap = 20;
  const contentPadding = 24;

  // Card entrance delays
  const cardBaseDelay = secondsToFrames(TIMING.CONTENT_BASE_DELAY, fps);

  return (
    <AbsoluteFill className="bg-[#121214] overflow-hidden">
      {/* Audio: card-slide for initial entrance */}
      <Sequence from={cardBaseDelay} durationInFrames={Math.floor(1 * fps)}>
        <Audio src={staticFile("sfx/card-slide.mp3")} volume={0.5} />
      </Sequence>

      {/* Audio: ui-appear for stats */}
      <Sequence from={cardBaseDelay + Math.floor(0.5 * fps)} durationInFrames={Math.floor(0.5 * fps)}>
        <Audio src={staticFile("sfx/ui-appear.mp3")} volume={0.4} />
      </Sequence>

      {/* Deep dark background with subtle texture */}
      <div
        className="absolute inset-0"
        style={{
          background: "radial-gradient(ellipse at 30% 20%, #1a1a1f 0%, #121214 50%, #0a0a0c 100%)",
        }}
      />

      {/* Wallpaper Background - very subtle */}
      <div className="absolute inset-0">
        <Img
          src={staticFile("einundzwanzig-wallpaper.png")}
          className="absolute inset-0 w-full h-full object-cover"
          style={{ opacity: 0.04, filter: "blur(1px)" }}
        />
      </div>

      {/* Cinematic light rays from top */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background: `linear-gradient(${170 + lightRayAngle}deg,
            rgba(247, 147, 26, ${lightRayOpacity * 0.5}) 0%,
            transparent 30%,
            transparent 70%,
            rgba(247, 147, 26, ${lightRayOpacity * 0.3}) 100%)`,
          opacity: lightRayOpacity * 8,
        }}
      />

      {/* Ambient orange glow spots */}
      <div
        className="absolute pointer-events-none"
        style={{
          width: 600,
          height: 600,
          left: "20%",
          top: "-10%",
          background: "radial-gradient(circle, rgba(247, 147, 26, 0.08) 0%, transparent 70%)",
          filter: "blur(60px)",
          opacity: glowIntensity * 2,
        }}
      />
      <div
        className="absolute pointer-events-none"
        style={{
          width: 400,
          height: 400,
          right: "10%",
          bottom: "20%",
          background: "radial-gradient(circle, rgba(247, 147, 26, 0.05) 0%, transparent 70%)",
          filter: "blur(40px)",
          opacity: glowIntensity * 1.5,
        }}
      />

      {/* Camera drift container */}
      <div
        className="absolute inset-0"
        style={{
          transform: `translate(${cameraBreathX}px, ${cameraBreathY}px) scale(${cameraZoom})`,
        }}
      >
        {/* 3D Perspective Container */}
        <div
          className="absolute inset-0"
          style={{
            transform: `perspective(1400px) rotateX(${perspectiveX}deg) scale(${perspectiveScale})`,
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
              height={videoHeight}
              delay={0}
              staggerItems={true}
              staggerDelay={2}
            />

            {/* Main Content Area - 3 column layout */}
            <div
              className="flex-1 flex"
              style={{
                padding: contentPadding,
                gap: contentGap,
              }}
            >
              {/* Left Column */}
              <div className="flex flex-col" style={{ width: 420, gap: contentGap }}>
                {/* Meine nächsten Meetup Termine */}
                <DashboardCard
                  title="Meine nächsten Meetup Termine"
                  delay={cardBaseDelay}
                  glowIntensity={glowIntensity}
                >
                  <UpcomingMeetupItem
                    name="Einundzwanzig Kempten"
                    location="Kempten, Germany"
                    date="06.02.2026 19:00 (CET)"
                    delay={cardBaseDelay + 10}
                  />
                </DashboardCard>

                {/* Top Länder */}
                <DashboardCard
                  title="Top Länder"
                  subtitle="Länder mit den meisten Usern"
                  delay={cardBaseDelay + STAGGER_DELAYS.CARD}
                  glowIntensity={glowIntensity}
                  height={580}
                >
                  {TOP_COUNTRIES.map((country, index) => (
                    <CountryRow
                      key={country.name}
                      flag={country.flag}
                      name={country.name}
                      users={country.users}
                      sparklineData={country.data}
                      delay={cardBaseDelay + STAGGER_DELAYS.CARD + 10 + index * 4}
                    />
                  ))}
                </DashboardCard>
              </div>

              {/* Middle Column */}
              <div className="flex flex-col" style={{ width: 480, gap: contentGap }}>
                {/* Meine Meetups */}
                <DashboardCard
                  title="Meine Meetups"
                  delay={cardBaseDelay + 5}
                  glowIntensity={glowIntensity}
                >
                  <UserMeetupsList delay={cardBaseDelay + 15} />
                </DashboardCard>

                {/* Top Meetups */}
                <DashboardCard
                  title="Top Meetups"
                  subtitle="Meetups mit den meisten Usern"
                  delay={cardBaseDelay + STAGGER_DELAYS.CARD + 5}
                  glowIntensity={glowIntensity}
                  height={460}
                >
                  {TOP_MEETUPS.map((meetup, index) => (
                    <MeetupRow
                      key={meetup.name}
                      name={meetup.name}
                      users={meetup.users}
                      sparklineData={meetup.data}
                      delay={cardBaseDelay + STAGGER_DELAYS.CARD + 15 + index * 4}
                    />
                  ))}
                </DashboardCard>
              </div>

              {/* Right Column - Activities */}
              <div className="flex-1 flex flex-col" style={{ gap: contentGap }}>
                <DashboardCard
                  title="Aktivitäten"
                  subtitle="Neue Meetups und Termine"
                  delay={cardBaseDelay + 10}
                  glowIntensity={glowIntensity}
                  height={780}
                >
                  <ActivityFeed delay={cardBaseDelay + 20} />
                </DashboardCard>
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Film grain overlay */}
      <FilmGrain frame={frame} />

      {/* Subtle scan lines for CRT/cinematic effect */}
      <ScanLines opacity={0.015} />

      {/* Enhanced vignette with breathing */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          boxShadow: `inset 0 0 ${250 + glowIntensity * 50}px ${80 + glowIntensity * 20}px rgba(0, 0, 0, 0.7)`,
        }}
      />

      {/* Chromatic aberration on edges */}
      <ChromaticAberration intensity={0.3} />

      {/* Top edge highlight */}
      <div
        className="absolute top-0 left-0 right-0 pointer-events-none"
        style={{
          height: 2,
          background: `linear-gradient(90deg, transparent, rgba(247, 147, 26, ${0.3 * glowIntensity}), transparent)`,
        }}
      />

      {/* Animated lens flare from top-right */}
      <LensFlare frame={frame} />
    </AbsoluteFill>
  );
};

/**
 * Film grain effect for cinematic look
 */
const FilmGrain: React.FC<{ frame: number }> = ({ frame }) => {
  // Generate pseudo-random grain pattern
  const grainOpacity = 0.04;

  return (
    <div
      className="absolute inset-0 pointer-events-none"
      style={{
        backgroundImage: `url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)'/%3E%3C/svg%3E")`,
        opacity: grainOpacity,
        mixBlendMode: "overlay",
        transform: `translate(${random(`grain-${frame}`) * 4}px, ${random(`grain-y-${frame}`) * 4}px)`,
      }}
    />
  );
};

/**
 * Scan lines effect for retro/cinematic feel
 */
const ScanLines: React.FC<{ opacity?: number }> = ({ opacity = 0.02 }) => {
  return (
    <div
      className="absolute inset-0 pointer-events-none"
      style={{
        backgroundImage: `repeating-linear-gradient(
          0deg,
          transparent,
          transparent 2px,
          rgba(0, 0, 0, ${opacity}) 2px,
          rgba(0, 0, 0, ${opacity}) 4px
        )`,
        backgroundSize: "100% 4px",
      }}
    />
  );
};

/**
 * Chromatic aberration effect on edges
 */
const ChromaticAberration: React.FC<{ intensity?: number }> = ({
  intensity = 0.5,
}) => {
  return (
    <>
      {/* Red channel shift - left edge */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background: `linear-gradient(90deg, rgba(255, 0, 0, ${0.02 * intensity}) 0%, transparent 5%)`,
          mixBlendMode: "screen",
        }}
      />
      {/* Cyan channel shift - right edge */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          background: `linear-gradient(270deg, rgba(0, 255, 255, ${0.02 * intensity}) 0%, transparent 5%)`,
          mixBlendMode: "screen",
        }}
      />
    </>
  );
};

/**
 * Animated lens flare effect
 */
const LensFlare: React.FC<{ frame: number }> = ({ frame }) => {
  // Subtle pulsing animation
  const flareIntensity = interpolate(
    Math.sin(frame * 0.02),
    [-1, 1],
    [0.02, 0.06]
  );

  const flareX = interpolate(
    Math.sin(frame * 0.008),
    [-1, 1],
    [75, 85]
  );

  const flareY = interpolate(
    Math.cos(frame * 0.006),
    [-1, 1],
    [5, 15]
  );

  return (
    <div
      className="absolute pointer-events-none"
      style={{
        width: 300,
        height: 300,
        left: `${flareX}%`,
        top: `${flareY}%`,
        transform: "translate(-50%, -50%)",
        background: `radial-gradient(circle, rgba(247, 147, 26, ${flareIntensity}) 0%, rgba(247, 147, 26, ${flareIntensity * 0.5}) 30%, transparent 70%)`,
        filter: "blur(30px)",
      }}
    />
  );
};

/**
 * Dashboard Card container matching screenshot style
 */
type DashboardCardProps = {
  title: string;
  subtitle?: string;
  delay: number;
  glowIntensity: number;
  children: React.ReactNode;
  height?: number;
};

const DashboardCard: React.FC<DashboardCardProps> = ({
  title,
  subtitle,
  delay,
  glowIntensity,
  children,
  height,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.92, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);
  const cardY = interpolate(cardSpring, [0, 1], [20, 0]);

  return (
    <div
      className="rounded-xl bg-[#1a1a1f]/90 backdrop-blur-sm border border-zinc-800/60"
      style={{
        transform: `translateY(${cardY}px) scale(${cardScale})`,
        opacity: cardOpacity,
        boxShadow: `0 0 ${25 * glowIntensity}px rgba(247, 147, 26, 0.05), 0 4px 24px rgba(0, 0, 0, 0.5)`,
        height: height || "auto",
        padding: 20,
        display: "flex",
        flexDirection: "column",
      }}
    >
      <div className="mb-3">
        <h3 className="text-base font-semibold text-zinc-200">{title}</h3>
        {subtitle && (
          <p className="text-xs text-zinc-500 mt-0.5">{subtitle}</p>
        )}
      </div>
      <div className="flex-1 flex flex-col gap-2 overflow-hidden">
        {children}
      </div>
    </div>
  );
};

/**
 * Upcoming meetup item for "Meine nächsten Meetup Termine"
 */
type UpcomingMeetupItemProps = {
  name: string;
  location: string;
  date: string;
  delay: number;
};

const UpcomingMeetupItem: React.FC<UpcomingMeetupItemProps> = ({
  name,
  location,
  date,
  delay,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const itemSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });

  const itemOpacity = interpolate(itemSpring, [0, 1], [0, 1]);
  const itemX = interpolate(itemSpring, [0, 1], [-15, 0]);

  // Badge pulse animation
  const badgePulse = interpolate(
    Math.sin(frame * 0.08),
    [-1, 1],
    [0.9, 1.05]
  );

  return (
    <div
      className="flex items-center gap-4"
      style={{
        opacity: itemOpacity,
        transform: `translateX(${itemX}px)`,
      }}
    >
      {/* Meetup avatar */}
      <div
        className="w-14 h-14 rounded-lg bg-zinc-800 flex items-center justify-center overflow-hidden border border-zinc-700/50"
      >
        <Img
          src={staticFile("einundzwanzig-square-inverted.svg")}
          style={{ width: 40, height: 40, objectFit: "contain" }}
        />
      </div>

      {/* Meetup info */}
      <div className="flex-1">
        <div className="flex items-center gap-2">
          <span className="text-white font-medium text-sm">{name}</span>
          <span className="text-base">🇩🇪</span>
        </div>
        <p className="text-zinc-500 text-xs">{location}</p>

        {/* Date badge */}
        <div
          className="inline-flex items-center mt-2 px-2 py-1 rounded text-xs font-medium"
          style={{
            backgroundColor: "rgba(247, 147, 26, 0.15)",
            color: "#f7931a",
            transform: `scale(${badgePulse})`,
            boxShadow: "0 0 10px rgba(247, 147, 26, 0.2)",
          }}
        >
          {date}
        </div>
      </div>
    </div>
  );
};

/**
 * Country row for "Top Länder"
 */
type CountryRowProps = {
  flag: string;
  name: string;
  users: number;
  sparklineData: number[];
  delay: number;
};

const CountryRow: React.FC<CountryRowProps> = ({
  flag,
  name,
  users,
  sparklineData,
  delay,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const rowSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.ROW,
  });

  const rowOpacity = interpolate(rowSpring, [0, 1], [0, 1]);
  const rowX = interpolate(rowSpring, [0, 1], [-20, 0]);

  // Animated user count
  const displayUsers = Math.round(interpolate(rowSpring, [0, 1], [0, users]));

  return (
    <div
      className="flex items-center py-2"
      style={{
        opacity: rowOpacity,
        transform: `translateX(${rowX}px)`,
      }}
    >
      {/* Flag */}
      <span className="text-xl mr-3">{flag}</span>

      {/* Country name and user count */}
      <div className="flex-1">
        <span className="text-white text-sm font-medium">{name}</span>
        <p className="text-zinc-500 text-xs">{displayUsers} User</p>
      </div>

      {/* Sparkline */}
      <div style={{ width: 100, height: 30 }}>
        <SparklineChart
          data={sparklineData}
          width={100}
          height={30}
          delay={delay + 5}
          strokeColor="#22c55e"
          showFill={true}
          fillOpacity={0.1}
        />
      </div>
    </div>
  );
};

/**
 * Meetup row for "Top Meetups"
 */
type MeetupRowProps = {
  name: string;
  users: number;
  sparklineData: number[];
  delay: number;
};

const MeetupRow: React.FC<MeetupRowProps> = ({
  name,
  users,
  sparklineData,
  delay,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const rowSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.ROW,
  });

  const rowOpacity = interpolate(rowSpring, [0, 1], [0, 1]);
  const rowX = interpolate(rowSpring, [0, 1], [-20, 0]);

  // Animated user count
  const displayUsers = Math.round(interpolate(rowSpring, [0, 1], [0, users]));

  return (
    <div
      className="flex items-center py-2"
      style={{
        opacity: rowOpacity,
        transform: `translateX(${rowX}px)`,
      }}
    >
      {/* Meetup logo */}
      <div className="w-8 h-8 rounded-md bg-zinc-800 flex items-center justify-center mr-3 border border-zinc-700/40">
        <Img
          src={staticFile("einundzwanzig-square-inverted.svg")}
          style={{ width: 20, height: 20, objectFit: "contain" }}
        />
      </div>

      {/* Meetup name and user count */}
      <div className="flex-1 min-w-0">
        <span className="text-white text-sm font-medium truncate block">{name}</span>
        <div className="flex items-center gap-1 text-xs text-zinc-500">
          <span>{displayUsers} User</span>
          <span className="text-base">🇩🇪</span>
        </div>
      </div>

      {/* Sparkline */}
      <div style={{ width: 90, height: 28 }}>
        <SparklineChart
          data={sparklineData}
          width={90}
          height={28}
          delay={delay + 5}
          strokeColor="#22c55e"
          showFill={true}
          fillOpacity={0.1}
        />
      </div>
    </div>
  );
};

/**
 * User's meetups list for "Meine Meetups"
 */
const UserMeetupsList: React.FC<{ delay: number }> = ({ delay }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const meetups = [
    { name: "Einundzwanzig Kempten", location: "Kempten" },
    { name: "Einundzwanzig Memmingen", location: "Memmingen" },
    { name: "Einundzwanzig Friedrichshafen", location: "Friedrichshafen" },
  ];

  return (
    <div className="flex flex-col gap-2">
      {meetups.map((meetup, index) => {
        const itemDelay = delay + index * 4;
        const adjustedFrame = Math.max(0, frame - itemDelay);

        const itemSpring = spring({
          frame: adjustedFrame,
          fps,
          config: SPRING_CONFIGS.ROW,
        });

        const itemOpacity = interpolate(itemSpring, [0, 1], [0, 1]);
        const itemX = interpolate(itemSpring, [0, 1], [-15, 0]);

        return (
          <div
            key={meetup.name}
            className="flex items-center justify-between py-2 px-3 rounded-lg bg-zinc-800/40 border border-zinc-700/30"
            style={{
              opacity: itemOpacity,
              transform: `translateX(${itemX}px)`,
            }}
          >
            <div className="flex items-center gap-3">
              {/* Meetup logo */}
              <div className="w-8 h-8 rounded-md bg-zinc-700/50 flex items-center justify-center">
                <Img
                  src={staticFile("einundzwanzig-square-inverted.svg")}
                  style={{ width: 20, height: 20, objectFit: "contain" }}
                />
              </div>

              {/* Meetup info */}
              <div>
                <div className="flex items-center gap-1.5">
                  <span className="text-white text-sm font-medium">{meetup.name}</span>
                  <span className="text-sm">🇩🇪</span>
                </div>
                <p className="text-zinc-500 text-xs">{meetup.location}</p>
              </div>
            </div>

            {/* Action buttons */}
            <div className="flex items-center gap-2">
              <ActionButton label="Neues Event erstellen" variant="outline" />
              <ActionButton label="Bearbeiten" variant="ghost" />
            </div>
          </div>
        );
      })}
    </div>
  );
};

/**
 * Action button component
 */
type ActionButtonProps = {
  label: string;
  variant: "outline" | "ghost";
};

const ActionButton: React.FC<ActionButtonProps> = ({ label, variant }) => {
  const baseStyles = "px-2 py-1 rounded text-xs font-medium transition-colors";

  if (variant === "outline") {
    return (
      <div className={`${baseStyles} border border-zinc-600 text-zinc-300`}>
        {label}
      </div>
    );
  }

  return (
    <div className={`${baseStyles} text-zinc-400`}>
      {label}
    </div>
  );
};

/**
 * Activity feed for "Aktivitäten"
 */
const ActivityFeed: React.FC<{ delay: number }> = ({ delay }) => {
  const activities = [
    { name: "Einundzwanzig Kirchdorf/OÖ", date: "08.01.2026 10:00 (CET)", time: "vor 5 Stunden", type: "Neuer Termin" },
    { name: "Einundzwanzig Kempten", date: "06.02.2026 19:00 (CET)", time: "vor 21 Stunden", type: "Neuer Termin" },
    { name: "Einundzwanzig Darmstadt", date: "03.02.2026 18:30 (CET)", time: "vor 1 Tag", type: "Neuer Termin" },
    { name: "Einundzwanzig Vulkaneifel", date: "22.02.2026 18:00 (CET)", time: "vor 2 Tagen", type: "Neuer Termin" },
    { name: "BitcoinWalk Würzburg", date: "27.01.2026 11:30 (CET)", time: "vor 2 Tagen", type: "Neuer Termin" },
    { name: "Einundzwanzig Landkreis Tuttlingen", location: "Tuttlingen, Germany", time: "vor 1 Woche", type: "Neues Meetup" },
    { name: "Club Orange Meetup", location: "Wuppertal, Germany", time: "vor 1 Woche", type: "Neues Meetup" },
  ];

  return (
    <div className="flex flex-col gap-3">
      {activities.map((activity, index) => (
        <ActivityItem
          key={`${activity.name}-${index}`}
          eventName={activity.name}
          timestamp={activity.time}
          badgeText={activity.type}
          delay={delay + index * STAGGER_DELAYS.LIST_ITEM}
          width={320}
        />
      ))}
    </div>
  );
};

