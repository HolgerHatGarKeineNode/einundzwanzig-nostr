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
import { ActivityItem } from "../../components/ActivityItem";

// Spring configurations
const SNAPPY = { damping: 15, stiffness: 80 };

// Stagger delay between activity items (in frames)
const ACTIVITY_STAGGER_DELAY = 20;

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
 * ActivityFeedScene - Scene 7: Activity Feed (10 seconds / 300 frames @ 30fps)
 *
 * Animation sequence:
 * 1. 3D perspective entrance with smooth transition
 * 2. Section header "Aktivitäten" animates in with fade + translateY
 * 3. Activity items slide in from right with staggered timing:
 *    - "Neuer Termin" badge bounces in
 *    - Meetup name types out / fades in
 *    - Timestamp fades in
 * 4. Stack effect: New items push existing ones down
 * 5. Audio: button-click.mp3 plays per item entrance
 */
export const ActivityFeedScene: React.FC = () => {
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
            "radial-gradient(circle at 60% 50%, transparent 0%, rgba(24, 24, 27, 0.5) 40%, rgba(24, 24, 27, 0.95) 100%)",
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
            <div className="flex items-center justify-center gap-3 mb-3">
              <h1 className="text-5xl font-bold text-white">Aktivitäten</h1>
              {/* Live indicator dot */}
              <div
                className="flex items-center gap-2 px-3 py-1 rounded-full bg-green-500/20 border border-green-500/30"
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
                <span className="text-sm text-green-400 font-medium">LIVE</span>
              </div>
            </div>
            <p
              className="text-xl text-zinc-400"
              style={{
                opacity: subtitleOpacity,
                transform: `translateY(${subtitleY}px)`,
              }}
            >
              Der Puls der Bitcoin-Community
            </p>
          </div>

          {/* Activity Feed List */}
          <div className="w-full max-w-lg">
            <div className="flex flex-col gap-4">
              {ACTIVITY_FEED_DATA.map((activity, index) => (
                <ActivityItemWrapper
                  key={activity.eventName}
                  activity={activity}
                  delay={activityBaseDelay + index * ACTIVITY_STAGGER_DELAY}
                  index={index}
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
 * Wrapper component for activity items with stack push-down animation
 */
type ActivityItemWrapperProps = {
  activity: {
    eventName: string;
    timestamp: string;
    badgeText: string;
  };
  delay: number;
  index: number;
};

const ActivityItemWrapper: React.FC<ActivityItemWrapperProps> = ({
  activity,
  delay,
  index,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Calculate push-down effect from items appearing above
  // Each item that appears before this one causes a slight downward push
  let pushDownOffset = 0;
  for (let i = 0; i < index; i++) {
    const prevItemDelay = Math.floor(1 * fps) + i * ACTIVITY_STAGGER_DELAY;
    const prevItemSpring = spring({
      frame: frame - prevItemDelay,
      fps,
      config: { damping: 20, stiffness: 100 },
    });
    // Each previous item pushes this one down slightly when it appears
    pushDownOffset += interpolate(prevItemSpring, [0, 1], [0, 0]);
  }

  // Container spring for the wrapper itself
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
        transform: `translateY(${pushDownOffset}px)`,
      }}
    >
      <ActivityItem
        eventName={activity.eventName}
        timestamp={activity.timestamp}
        badgeText={activity.badgeText}
        showBadge={true}
        delay={delay}
        width={480}
        accentColor="#f7931a"
      />
    </div>
  );
};
