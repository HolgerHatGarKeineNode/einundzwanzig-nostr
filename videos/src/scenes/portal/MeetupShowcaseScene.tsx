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
import { MeetupCard } from "../../components/MeetupCard";
import {
  SPRING_CONFIGS,
  STAGGER_DELAYS,
  TIMING,
  GLOW_CONFIG,
  secondsToFrames,
} from "../../config/timing";

// Upcoming meetup data
const UPCOMING_MEETUPS = [
  {
    name: "EINUNDZWANZIG Kempten",
    location: "Kempten im Allgäu",
    date: "Di, 28. Jan 2025",
    time: "19:00 Uhr",
    logoPath: "logos/EinundzwanzigKempten.jpg",
    isFeatured: true,
  },
  {
    name: "EINUNDZWANZIG Memmingen",
    location: "Memmingen",
    date: "Mi, 29. Jan 2025",
    time: "19:30 Uhr",
    logoPath: "logos/EinundzwanzigMemmingen.jpg",
    isFeatured: false,
  },
  {
    name: "EINUNDZWANZIG Friedrichshafen",
    location: "Friedrichshafen",
    date: "Do, 30. Jan 2025",
    time: "20:00 Uhr",
    logoPath: "logos/EinundzwanzigFriedrichshafen.png",
    isFeatured: false,
  },
];

/**
 * MeetupShowcaseScene - Scene 4: Meine nächsten Meetup Termine (12 seconds / 360 frames @ 30fps)
 *
 * Animation sequence:
 * 1. Section header animates in with fade + translateY
 * 2. Featured meetup card (Kempten) appears with 3D perspective and shadow
 * 3. Date/time info animates below the featured card
 * 4. List of upcoming meetups (Memmingen, Friedrichshafen) fade in staggered
 * 5. Action buttons appear at the end
 * 6. Audio: slide-in.mp3, badge-appear.mp3
 */
export const MeetupShowcaseScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // 3D Perspective entrance animation using centralized config
  const perspectiveSpring = spring({
    frame,
    fps,
    config: SPRING_CONFIGS.PERSPECTIVE,
  });

  // Fine-tuned: Reduced initial rotation for smoother entrance
  const perspectiveX = interpolate(perspectiveSpring, [0, 1], [18, 0]);
  const perspectiveScale = interpolate(perspectiveSpring, [0, 1], [0.92, 1]);
  const perspectiveOpacity = interpolate(perspectiveSpring, [0, 1], [0, 1]);

  // Header entrance animation (delayed) - Fine-tuned timing
  const headerDelay = secondsToFrames(0.35, fps);
  const headerSpring = spring({
    frame: frame - headerDelay,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });
  const headerOpacity = interpolate(headerSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced Y translation
  const headerY = interpolate(headerSpring, [0, 1], [-35, 0]);

  // Featured card entrance delay using centralized timing
  const featuredCardDelay = secondsToFrames(TIMING.FEATURED_DELAY, fps);

  // Featured card 3D shadow animation using centralized config
  const featuredSpring = spring({
    frame: frame - featuredCardDelay,
    fps,
    config: SPRING_CONFIGS.FEATURED,
  });
  // Fine-tuned: Smoother scale transition
  const featuredScale = interpolate(featuredSpring, [0, 1], [0.88, 1]);
  const featuredOpacity = interpolate(featuredSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced initial rotation for less dramatic entrance
  const featuredRotateX = interpolate(featuredSpring, [0, 1], [12, 0]);
  const featuredShadowY = interpolate(featuredSpring, [0, 1], [20, 40]);
  const featuredShadowBlur = interpolate(featuredSpring, [0, 1], [10, 60]);

  // Date/time info animation (after featured card) - Fine-tuned timing
  const dateDelay = featuredCardDelay + secondsToFrames(0.45, fps);
  const dateSpring = spring({
    frame: frame - dateDelay,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });
  const dateOpacity = interpolate(dateSpring, [0, 1], [0, 1]);
  const dateY = interpolate(dateSpring, [0, 1], [18, 0]);

  // Upcoming list header delay - Fine-tuned timing
  const listHeaderDelay = featuredCardDelay + secondsToFrames(1.1, fps);
  const listHeaderSpring = spring({
    frame: frame - listHeaderDelay,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });
  const listHeaderOpacity = interpolate(listHeaderSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced X translation
  const listHeaderX = interpolate(listHeaderSpring, [0, 1], [-25, 0]);

  // Action buttons animation - Fine-tuned timing
  const buttonsDelay = secondsToFrames(3.2, fps);
  const buttonsSpring = spring({
    frame: frame - buttonsDelay,
    fps,
    config: SPRING_CONFIGS.BOUNCY,
  });
  const buttonsOpacity = interpolate(buttonsSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced Y translation
  const buttonsY = interpolate(buttonsSpring, [0, 1], [25, 0]);

  // Subtle glow pulse using centralized config
  const glowIntensity = interpolate(
    Math.sin(frame * GLOW_CONFIG.FREQUENCY.SLOW),
    [-1, 1],
    [0.35, 0.6]
  );

  // Featured meetup data
  const featuredMeetup = UPCOMING_MEETUPS[0];
  const upcomingMeetups = UPCOMING_MEETUPS.slice(1);

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: slide-in for section entrance */}
      <Sequence from={headerDelay} durationInFrames={Math.floor(1 * fps)}>
        <Audio src={staticFile("sfx/slide-in.mp3")} volume={0.5} />
      </Sequence>

      {/* Audio: card-slide for featured card */}
      <Sequence from={featuredCardDelay} durationInFrames={Math.floor(0.8 * fps)}>
        <Audio src={staticFile("sfx/card-slide.mp3")} volume={0.5} />
      </Sequence>

      {/* Audio: badge-appear for list items */}
      <Sequence from={listHeaderDelay + STAGGER_DELAYS.LIST_ITEM} durationInFrames={Math.floor(0.5 * fps)}>
        <Audio src={staticFile("sfx/badge-appear.mp3")} volume={0.4} />
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
            "radial-gradient(circle at 60% 40%, transparent 0%, rgba(24, 24, 27, 0.5) 40%, rgba(24, 24, 27, 0.95) 100%)",
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
            className="text-center mb-12"
            style={{
              opacity: headerOpacity,
              transform: `translateY(${headerY}px)`,
            }}
          >
            <h1 className="text-5xl font-bold text-white mb-3">
              Meine nächsten Meetup Termine
            </h1>
            <p className="text-xl text-zinc-400">
              Deine kommenden Bitcoin-Treffen in der Region
            </p>
          </div>

          {/* Featured Meetup Card with 3D Shadow */}
          <div
            className="relative mb-8"
            style={{
              opacity: featuredOpacity,
              transform: `perspective(800px) rotateX(${featuredRotateX}deg) scale(${featuredScale})`,
              transformOrigin: "center bottom",
            }}
          >
            {/* 3D Shadow beneath card */}
            <div
              className="absolute left-1/2 -translate-x-1/2 rounded-full"
              style={{
                bottom: -20,
                width: "80%",
                height: 20,
                background: `radial-gradient(ellipse at center, rgba(0,0,0,0.5) 0%, transparent 70%)`,
                transform: `translateY(${featuredShadowY}px)`,
                filter: `blur(${featuredShadowBlur / 3}px)`,
              }}
            />

            {/* Featured Meetup Card */}
            <div
              className="rounded-3xl bg-zinc-800/90 backdrop-blur-md border border-zinc-700/50 p-8"
              style={{
                boxShadow: `0 ${featuredShadowY}px ${featuredShadowBlur}px rgba(0, 0, 0, 0.5),
                            0 0 ${40 * glowIntensity}px rgba(247, 147, 26, 0.2)`,
              }}
            >
              <div className="flex items-center gap-8">
                {/* Meetup Card Component */}
                <MeetupCard
                  logoSrc={staticFile(featuredMeetup.logoPath)}
                  name={featuredMeetup.name}
                  location={featuredMeetup.location}
                  delay={featuredCardDelay + 5}
                  width={450}
                  accentColor="#f7931a"
                />

                {/* Date/Time Info */}
                <div
                  className="flex flex-col gap-3 pl-8 border-l border-zinc-600/50"
                  style={{
                    opacity: dateOpacity,
                    transform: `translateY(${dateY}px)`,
                  }}
                >
                  {/* Date */}
                  <div className="flex items-center gap-3">
                    <CalendarIcon />
                    <span className="text-2xl font-semibold text-white">
                      {featuredMeetup.date}
                    </span>
                  </div>
                  {/* Time */}
                  <div className="flex items-center gap-3">
                    <ClockIcon />
                    <span className="text-xl text-zinc-300">
                      {featuredMeetup.time}
                    </span>
                  </div>
                  {/* Badge */}
                  <div
                    className="mt-2 inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold"
                    style={{
                      backgroundColor: "rgba(247, 147, 26, 0.2)",
                      color: "#f7931a",
                      border: "1px solid rgba(247, 147, 26, 0.3)",
                      boxShadow: `0 0 ${20 * glowIntensity}px rgba(247, 147, 26, 0.3)`,
                    }}
                  >
                    <span className="w-2 h-2 rounded-full bg-green-500 animate-pulse" />
                    Nächster Termin
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Upcoming Meetups Section */}
          <div className="w-full max-w-4xl">
            {/* Section Header */}
            <h3
              className="text-lg font-semibold text-zinc-400 mb-4 uppercase tracking-wider"
              style={{
                opacity: listHeaderOpacity,
                transform: `translateX(${listHeaderX}px)`,
              }}
            >
              Weitere Termine
            </h3>

            {/* Upcoming Meetups List */}
            <div className="flex gap-6">
              {upcomingMeetups.map((meetup, index) => (
                <UpcomingMeetupItem
                  key={meetup.name}
                  meetup={meetup}
                  delay={listHeaderDelay + (index + 1) * STAGGER_DELAYS.LIST_ITEM}
                  glowIntensity={glowIntensity}
                />
              ))}
            </div>
          </div>

          {/* Action Buttons */}
          <div
            className="flex gap-4 mt-10"
            style={{
              opacity: buttonsOpacity,
              transform: `translateY(${buttonsY}px)`,
            }}
          >
            <ActionButton
              label="Zum Kalender hinzufügen"
              icon="calendar"
              isPrimary={true}
              delay={buttonsDelay}
              glowIntensity={glowIntensity}
            />
            <ActionButton
              label="Alle Meetups anzeigen"
              icon="list"
              isPrimary={false}
              delay={buttonsDelay + 5}
              glowIntensity={glowIntensity}
            />
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
 * Upcoming meetup list item
 */
type UpcomingMeetupItemProps = {
  meetup: {
    name: string;
    location: string;
    date: string;
    time: string;
    logoPath: string;
  };
  delay: number;
  glowIntensity: number;
};

const UpcomingMeetupItem: React.FC<UpcomingMeetupItemProps> = ({
  meetup,
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Fine-tuned: Using centralized SNAPPY config
  const itemSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.SNAPPY,
  });

  const itemOpacity = interpolate(itemSpring, [0, 1], [0, 1]);
  // Fine-tuned: Reduced Y translation for subtler entrance
  const itemY = interpolate(itemSpring, [0, 1], [25, 0]);
  // Fine-tuned: Increased initial scale for smoother transition
  const itemScale = interpolate(itemSpring, [0, 1], [0.92, 1]);

  return (
    <div
      className="flex-1 rounded-xl bg-zinc-800/70 backdrop-blur-sm border border-zinc-700/40 p-5"
      style={{
        opacity: itemOpacity,
        transform: `translateY(${itemY}px) scale(${itemScale})`,
        boxShadow: `0 4px 20px rgba(0, 0, 0, 0.3), 0 0 ${15 * glowIntensity}px rgba(247, 147, 26, 0.1)`,
      }}
    >
      <div className="flex items-center gap-4">
        {/* Mini Logo */}
        <div
          className="w-12 h-12 rounded-lg bg-zinc-700/50 flex items-center justify-center overflow-hidden"
          style={{
            boxShadow: `0 0 ${10 * glowIntensity}px rgba(247, 147, 26, 0.2)`,
          }}
        >
          <Img
            src={staticFile(meetup.logoPath)}
            style={{
              width: 36,
              height: 36,
              objectFit: "contain",
            }}
          />
        </div>

        {/* Meetup Info */}
        <div className="flex-1 min-w-0">
          <div className="font-semibold text-white text-base truncate">
            {meetup.name}
          </div>
          <div className="text-sm text-zinc-400 flex items-center gap-2 mt-1">
            <span>{meetup.date}</span>
            <span className="text-zinc-600">•</span>
            <span>{meetup.time}</span>
          </div>
        </div>
      </div>
    </div>
  );
};

/**
 * Action button component
 */
type ActionButtonProps = {
  label: string;
  icon: "calendar" | "list";
  isPrimary: boolean;
  delay: number;
  glowIntensity: number;
};

const ActionButton: React.FC<ActionButtonProps> = ({
  label,
  icon,
  isPrimary,
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Fine-tuned: Using centralized BUTTON config
  const buttonSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SPRING_CONFIGS.BUTTON,
  });

  // Fine-tuned: Increased initial scale for smoother transition
  const buttonScale = interpolate(buttonSpring, [0, 1], [0.85, 1]);
  const buttonOpacity = interpolate(buttonSpring, [0, 1], [0, 1]);

  const baseClasses = "flex items-center gap-3 px-6 py-3 rounded-xl font-semibold text-base transition-all";

  const primaryStyles = {
    backgroundColor: "#f7931a",
    color: "#000",
    boxShadow: `0 4px 20px rgba(247, 147, 26, 0.4), 0 0 ${30 * glowIntensity}px rgba(247, 147, 26, 0.3)`,
  };

  const secondaryStyles = {
    backgroundColor: "rgba(63, 63, 70, 0.8)",
    color: "#fff",
    border: "1px solid rgba(113, 113, 122, 0.5)",
    boxShadow: "0 4px 15px rgba(0, 0, 0, 0.3)",
  };

  return (
    <div
      className={baseClasses}
      style={{
        ...(isPrimary ? primaryStyles : secondaryStyles),
        transform: `scale(${buttonScale})`,
        opacity: buttonOpacity,
      }}
    >
      {icon === "calendar" ? <CalendarPlusIcon /> : <ListIcon />}
      {label}
    </div>
  );
};

/**
 * Calendar icon SVG
 */
const CalendarIcon: React.FC = () => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="#f7931a"
    strokeWidth={2}
    strokeLinecap="round"
    strokeLinejoin="round"
    style={{ width: 24, height: 24 }}
  >
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
    <line x1="16" y1="2" x2="16" y2="6" />
    <line x1="8" y1="2" x2="8" y2="6" />
    <line x1="3" y1="10" x2="21" y2="10" />
  </svg>
);

/**
 * Clock icon SVG
 */
const ClockIcon: React.FC = () => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="#a1a1aa"
    strokeWidth={2}
    strokeLinecap="round"
    strokeLinejoin="round"
    style={{ width: 22, height: 22 }}
  >
    <circle cx="12" cy="12" r="10" />
    <polyline points="12 6 12 12 16 14" />
  </svg>
);

/**
 * Calendar Plus icon SVG for action button
 */
const CalendarPlusIcon: React.FC = () => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth={2}
    strokeLinecap="round"
    strokeLinejoin="round"
    style={{ width: 20, height: 20 }}
  >
    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
    <line x1="16" y1="2" x2="16" y2="6" />
    <line x1="8" y1="2" x2="8" y2="6" />
    <line x1="3" y1="10" x2="21" y2="10" />
    <line x1="12" y1="14" x2="12" y2="18" />
    <line x1="10" y1="16" x2="14" y2="16" />
  </svg>
);

/**
 * List icon SVG for action button
 */
const ListIcon: React.FC = () => (
  <svg
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    strokeWidth={2}
    strokeLinecap="round"
    strokeLinejoin="round"
    style={{ width: 20, height: 20 }}
  >
    <line x1="8" y1="6" x2="21" y2="6" />
    <line x1="8" y1="12" x2="21" y2="12" />
    <line x1="8" y1="18" x2="21" y2="18" />
    <line x1="3" y1="6" x2="3.01" y2="6" />
    <line x1="3" y1="12" x2="3.01" y2="12" />
    <line x1="3" y1="18" x2="3.01" y2="18" />
  </svg>
);
