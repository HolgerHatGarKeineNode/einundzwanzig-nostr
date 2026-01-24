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
import { ActivityItem } from "../../../components/ActivityItem";

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };

// Stagger delay between activity items (in frames)
const ACTIVITY_STAGGER_DELAY = 18;

// Activity feed data from the screenshot
const ACTIVITY_FEED_DATA = [
  {
    eventName: "EINUNDZWANZIG Kempten",
    timestamp: "vor 13 Stunden",
    badgeText: "Neuer Termin",
  },
  {
    eventName: "EINUNDZWANZIG Darmstadt",
    timestamp: "vor 21 Stunden",
    badgeText: "Neuer Termin",
  },
  {
    eventName: "EINUNDZWANZIG Vulkaneifel",
    timestamp: "vor 2 Tagen",
    badgeText: "Neuer Termin",
  },
  {
    eventName: "BitcoinWalk Würzburg",
    timestamp: "vor 2 Tagen",
    badgeText: "Neuer Termin",
  },
];

/**
 * ActivityFeedSceneMobile - Scene 7: Activity Feed for Mobile (10 seconds / 300 frames @ 30fps)
 *
 * Mobile layout adaptations:
 * - Narrower activity cards (400px vs 480px)
 * - Adjusted text sizes and spacing
 * - Centered layout for portrait orientation
 */
export const ActivityFeedSceneMobile: React.FC = () => {
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

  // Base delay for activity items
  const activityBaseDelay = Math.floor(1 * fps);

  // Subtle pulse for live indicator
  const pulseIntensity = interpolate(
    Math.sin(frame * 0.1),
    [-1, 1],
    [0.5, 1]
  );

  return (
    <AbsoluteFill className="bg-zinc-900 overflow-hidden">
      {/* Audio: button-click for each activity entrance */}
      {ACTIVITY_FEED_DATA.map((_, index) => (
        <Sequence
          key={`audio-${index}`}
          from={activityBaseDelay + index * ACTIVITY_STAGGER_DELAY}
          durationInFrames={Math.floor(0.5 * fps)}
        >
          <Audio src={staticFile("sfx/button-click.mp3")} volume={0.4} />
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
            "radial-gradient(circle at 50% 50%, transparent 0%, rgba(24, 24, 27, 0.5) 40%, rgba(24, 24, 27, 0.95) 100%)",
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
        <div className="absolute inset-0 flex flex-col items-center justify-center px-6">
          {/* Section Header */}
          <div
            className="text-center mb-8"
            style={{
              opacity: headerOpacity,
              transform: `translateY(${headerY}px)`,
            }}
          >
            <div className="flex items-center justify-center gap-3 mb-3">
              <h1 className="text-4xl font-bold text-white">Aktivitäten</h1>
              {/* Live indicator dot */}
              <div
                className="flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-green-500/20 border border-green-500/30"
                style={{
                  opacity: pulseIntensity,
                }}
              >
                <div
                  className="w-2 h-2 rounded-full bg-green-500"
                  style={{
                    boxShadow: `0 0 ${8 * pulseIntensity}px rgba(34, 197, 94, 0.8)`,
                  }}
                />
                <span className="text-xs text-green-400 font-medium">LIVE</span>
              </div>
            </div>
            <p
              className="text-lg text-zinc-400"
              style={{
                opacity: subtitleOpacity,
                transform: `translateY(${subtitleY}px)`,
              }}
            >
              Der Puls der Bitcoin-Community
            </p>
          </div>

          {/* Activity Feed List */}
          <div className="w-full max-w-md">
            <div className="flex flex-col gap-3">
              {ACTIVITY_FEED_DATA.map((activity, index) => (
                <ActivityItemWrapperMobile
                  key={activity.eventName}
                  activity={activity}
                  delay={activityBaseDelay + index * ACTIVITY_STAGGER_DELAY}
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
 * Wrapper component for activity items for mobile
 */
type ActivityItemWrapperMobileProps = {
  activity: {
    eventName: string;
    timestamp: string;
    badgeText: string;
  };
  delay: number;
};

const ActivityItemWrapperMobile: React.FC<ActivityItemWrapperMobileProps> = ({
  activity,
  delay,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Container spring for the wrapper
  const containerSpring = spring({
    frame: frame - delay,
    fps,
    config: { damping: 15, stiffness: 80 },
  });

  const containerOpacity = interpolate(containerSpring, [0, 1], [0, 1]);

  return (
    <div
      style={{
        opacity: containerOpacity,
      }}
    >
      <ActivityItem
        eventName={activity.eventName}
        timestamp={activity.timestamp}
        badgeText={activity.badgeText}
        showBadge={true}
        delay={delay}
        width={400}
        accentColor="#f7931a"
      />
    </div>
  );
};
