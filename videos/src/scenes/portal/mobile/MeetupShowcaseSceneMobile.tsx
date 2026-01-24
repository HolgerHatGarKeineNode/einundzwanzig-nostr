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
import { MeetupCard } from "../../../components/MeetupCard";

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };

// Stagger delay between meetup list items
const LIST_ITEM_STAGGER = 10;

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
 * MeetupShowcaseSceneMobile - Scene 4: Meine nächsten Meetup Termine for Mobile (12 seconds / 360 frames @ 30fps)
 *
 * Mobile layout adaptations:
 * - Vertical layout for featured meetup card (stacked info)
 * - Smaller card widths optimized for 1080px width
 * - Reduced text sizes and spacing
 * - Action buttons stacked vertically
 */
export const MeetupShowcaseSceneMobile: React.FC = () => {
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

  // Featured card entrance delay
  const featuredCardDelay = Math.floor(0.8 * fps);

  // Featured card 3D shadow animation
  const featuredSpring = spring({
    frame: frame - featuredCardDelay,
    fps,
    config: { damping: 18, stiffness: 70 },
  });
  const featuredScale = interpolate(featuredSpring, [0, 1], [0.85, 1]);
  const featuredOpacity = interpolate(featuredSpring, [0, 1], [0, 1]);
  const featuredRotateX = interpolate(featuredSpring, [0, 1], [15, 0]);
  const featuredShadowY = interpolate(featuredSpring, [0, 1], [20, 40]);
  const featuredShadowBlur = interpolate(featuredSpring, [0, 1], [10, 60]);

  // Date/time info animation (after featured card)
  const dateDelay = featuredCardDelay + Math.floor(0.4 * fps);
  const dateSpring = spring({
    frame: frame - dateDelay,
    fps,
    config: SNAPPY,
  });
  const dateOpacity = interpolate(dateSpring, [0, 1], [0, 1]);
  const dateY = interpolate(dateSpring, [0, 1], [20, 0]);

  // Upcoming list header delay
  const listHeaderDelay = featuredCardDelay + Math.floor(1 * fps);
  const listHeaderSpring = spring({
    frame: frame - listHeaderDelay,
    fps,
    config: SNAPPY,
  });
  const listHeaderOpacity = interpolate(listHeaderSpring, [0, 1], [0, 1]);
  const listHeaderX = interpolate(listHeaderSpring, [0, 1], [-30, 0]);

  // Subtle glow pulse
  const glowIntensity = interpolate(
    Math.sin(frame * 0.05),
    [-1, 1],
    [0.3, 0.6]
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
      <Sequence from={listHeaderDelay + LIST_ITEM_STAGGER} durationInFrames={Math.floor(0.5 * fps)}>
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
            "radial-gradient(circle at 50% 40%, transparent 0%, rgba(24, 24, 27, 0.5) 40%, rgba(24, 24, 27, 0.95) 100%)",
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
        <div className="absolute inset-0 flex flex-col items-center justify-center px-8">
          {/* Section Header */}
          <div
            className="text-center mb-8"
            style={{
              opacity: headerOpacity,
              transform: `translateY(${headerY}px)`,
            }}
          >
            <h1 className="text-3xl font-bold text-white mb-2">
              Meine nächsten Meetups
            </h1>
            <p className="text-base text-zinc-400">
              Kommende Bitcoin-Treffen in deiner Region
            </p>
          </div>

          {/* Featured Meetup Card with 3D Shadow - Mobile optimized */}
          <div
            className="relative mb-6 w-full max-w-md"
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
                bottom: -15,
                width: "80%",
                height: 15,
                background: `radial-gradient(ellipse at center, rgba(0,0,0,0.5) 0%, transparent 70%)`,
                transform: `translateY(${featuredShadowY * 0.7}px)`,
                filter: `blur(${featuredShadowBlur / 4}px)`,
              }}
            />

            {/* Featured Meetup Card - Vertical layout for mobile */}
            <div
              className="rounded-2xl bg-zinc-800/90 backdrop-blur-md border border-zinc-700/50 p-6"
              style={{
                boxShadow: `0 ${featuredShadowY}px ${featuredShadowBlur}px rgba(0, 0, 0, 0.5),
                            0 0 ${40 * glowIntensity}px rgba(247, 147, 26, 0.2)`,
              }}
            >
              {/* Meetup Card Component */}
              <MeetupCard
                logoSrc={staticFile(featuredMeetup.logoPath)}
                name={featuredMeetup.name}
                location={featuredMeetup.location}
                delay={featuredCardDelay + 5}
                width={360}
                accentColor="#f7931a"
              />

              {/* Date/Time Info - Below card for mobile */}
              <div
                className="flex flex-col gap-3 pt-5 mt-5 border-t border-zinc-600/50"
                style={{
                  opacity: dateOpacity,
                  transform: `translateY(${dateY}px)`,
                }}
              >
                {/* Date */}
                <div className="flex items-center gap-3">
                  <CalendarIcon />
                  <span className="text-xl font-semibold text-white">
                    {featuredMeetup.date}
                  </span>
                </div>
                {/* Time */}
                <div className="flex items-center gap-3">
                  <ClockIcon />
                  <span className="text-lg text-zinc-300">
                    {featuredMeetup.time}
                  </span>
                </div>
                {/* Badge */}
                <div
                  className="mt-1 inline-flex items-center gap-2 px-4 py-2 rounded-full text-sm font-semibold self-start"
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

          {/* Upcoming Meetups Section */}
          <div className="w-full max-w-md">
            {/* Section Header */}
            <h3
              className="text-sm font-semibold text-zinc-400 mb-3 uppercase tracking-wider"
              style={{
                opacity: listHeaderOpacity,
                transform: `translateX(${listHeaderX}px)`,
              }}
            >
              Weitere Termine
            </h3>

            {/* Upcoming Meetups List - Vertical for mobile */}
            <div className="flex flex-col gap-3">
              {upcomingMeetups.map((meetup, index) => (
                <UpcomingMeetupItemMobile
                  key={meetup.name}
                  meetup={meetup}
                  delay={listHeaderDelay + (index + 1) * LIST_ITEM_STAGGER}
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
 * Upcoming meetup list item for mobile
 */
type UpcomingMeetupItemMobileProps = {
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

const UpcomingMeetupItemMobile: React.FC<UpcomingMeetupItemMobileProps> = ({
  meetup,
  delay,
  glowIntensity,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  const itemSpring = spring({
    frame: adjustedFrame,
    fps,
    config: SNAPPY,
  });

  const itemOpacity = interpolate(itemSpring, [0, 1], [0, 1]);
  const itemY = interpolate(itemSpring, [0, 1], [30, 0]);
  const itemScale = interpolate(itemSpring, [0, 1], [0.9, 1]);

  return (
    <div
      className="rounded-xl bg-zinc-800/70 backdrop-blur-sm border border-zinc-700/40 p-4"
      style={{
        opacity: itemOpacity,
        transform: `translateY(${itemY}px) scale(${itemScale})`,
        boxShadow: `0 4px 20px rgba(0, 0, 0, 0.3), 0 0 ${15 * glowIntensity}px rgba(247, 147, 26, 0.1)`,
      }}
    >
      <div className="flex items-center gap-4">
        {/* Mini Logo */}
        <div
          className="w-12 h-12 rounded-lg bg-zinc-700/50 flex items-center justify-center overflow-hidden flex-shrink-0"
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
    style={{ width: 22, height: 22 }}
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
    style={{ width: 20, height: 20 }}
  >
    <circle cx="12" cy="12" r="10" />
    <polyline points="12 6 12 12 16 14" />
  </svg>
);
