import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
} from "remotion";

export type ActivityItemProps = {
  /** Name of the event/meetup */
  eventName: string;
  /** Timestamp display (e.g., "vor 13 Stunden", "vor 2 Tagen") */
  timestamp: string;
  /** Badge text (default: "Neuer Termin") */
  badgeText?: string;
  /** Whether to show the badge (default: true) */
  showBadge?: boolean;
  /** Delay in frames before animation starts */
  delay?: number;
  /** Width of the component in pixels (default: 400) */
  width?: number;
  /** Custom color for accent elements (default: #f7931a - Bitcoin orange) */
  accentColor?: string;
};

export const ActivityItem: React.FC<ActivityItemProps> = ({
  eventName,
  timestamp,
  badgeText = "Neuer Termin",
  showBadge = true,
  delay = 0,
  width = 400,
  accentColor = "#f7931a",
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Card entrance animation (slide in from right)
  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 15, stiffness: 80 },
  });

  const cardTranslateX = interpolate(cardSpring, [0, 1], [100, 0]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);

  // Badge bounce animation (slightly delayed, bouncier)
  const badgeSpring = spring({
    frame: adjustedFrame - 8,
    fps,
    config: { damping: 8, stiffness: 150 },
  });

  const badgeScale = interpolate(badgeSpring, [0, 1], [0, 1]);

  // Event name animation (staggered after badge)
  const nameSpring = spring({
    frame: adjustedFrame - 12,
    fps,
    config: { damping: 15, stiffness: 90 },
  });

  const nameOpacity = interpolate(nameSpring, [0, 1], [0, 1]);
  const nameTranslateY = interpolate(nameSpring, [0, 1], [15, 0]);

  // Timestamp fade in (last element)
  const timestampSpring = spring({
    frame: adjustedFrame - 18,
    fps,
    config: { damping: 15, stiffness: 90 },
  });

  const timestampOpacity = interpolate(timestampSpring, [0, 1], [0, 1]);

  // Subtle glow pulse
  const glowIntensity = interpolate(
    Math.sin(adjustedFrame * 0.08),
    [-1, 1],
    [0.3, 0.5]
  );

  const padding = width * 0.05;
  const badgePaddingX = width * 0.03;
  const badgePaddingY = width * 0.015;

  return (
    <div
      className="flex flex-col rounded-xl bg-zinc-900/90 backdrop-blur-sm border border-zinc-700/50"
      style={{
        width,
        padding,
        gap: padding * 0.6,
        transform: `translateX(${cardTranslateX}px)`,
        opacity: cardOpacity,
        boxShadow: `0 0 ${25 * glowIntensity}px ${accentColor}25, 0 4px 16px rgba(0, 0, 0, 0.4)`,
      }}
    >
      {/* Badge */}
      {showBadge && (
        <div
          className="self-start rounded-full font-semibold text-white"
          style={{
            backgroundColor: accentColor,
            paddingLeft: badgePaddingX,
            paddingRight: badgePaddingX,
            paddingTop: badgePaddingY,
            paddingBottom: badgePaddingY,
            fontSize: width * 0.04,
            transform: `scale(${badgeScale})`,
            transformOrigin: "left center",
            boxShadow: `0 0 ${12 * glowIntensity}px ${accentColor}50`,
          }}
        >
          {badgeText}
        </div>
      )}

      {/* Event Name */}
      <div
        className="font-bold text-white truncate"
        style={{
          fontSize: width * 0.06,
          opacity: nameOpacity,
          transform: `translateY(${nameTranslateY}px)`,
          lineHeight: 1.2,
        }}
      >
        {eventName}
      </div>

      {/* Timestamp */}
      <div
        className="text-zinc-400 flex items-center"
        style={{
          fontSize: width * 0.04,
          opacity: timestampOpacity,
          fontFamily: "Inconsolata, monospace",
        }}
      >
        {/* Clock icon */}
        <svg
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          strokeWidth={2}
          strokeLinecap="round"
          strokeLinejoin="round"
          style={{
            width: width * 0.04,
            height: width * 0.04,
            marginRight: width * 0.02,
            flexShrink: 0,
          }}
        >
          <circle cx="12" cy="12" r="10" />
          <polyline points="12,6 12,12 16,14" />
        </svg>
        <span className="timestamp-text">{timestamp}</span>
      </div>
    </div>
  );
};
