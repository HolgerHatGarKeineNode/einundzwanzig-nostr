import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
} from "remotion";

export interface CountryBarProps {
  /** Country name (e.g., "Germany") */
  countryName: string;
  /** Country flag emoji (e.g., "🇩🇪") */
  flagEmoji: string;
  /** Number of users in this country */
  userCount: number;
  /** Maximum user count for calculating bar width (default: same as userCount for full bar) */
  maxCount?: number;
  /** Total width of the component in pixels (default: 500) */
  width?: number;
  /** Delay in frames before animation starts */
  delay?: number;
  /** Custom accent color for the bar (default: #f7931a - Bitcoin orange) */
  accentColor?: string;
  /** Whether to show the user count number (default: true) */
  showCount?: boolean;
}

export const CountryBar: React.FC<CountryBarProps> = ({
  countryName,
  flagEmoji,
  userCount,
  maxCount,
  width = 500,
  delay = 0,
  accentColor = "#f7931a",
  showCount = true,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Calculate bar fill percentage
  const effectiveMax = maxCount ?? userCount;
  const fillPercentage = effectiveMax > 0 ? userCount / effectiveMax : 0;

  // Card entrance animation
  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 15, stiffness: 80 },
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.8, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);

  // Flag animation (slightly delayed)
  const flagSpring = spring({
    frame: adjustedFrame - 5,
    fps,
    config: { damping: 12, stiffness: 100 },
  });

  const flagScale = interpolate(flagSpring, [0, 1], [0, 1]);

  // Country name animation
  const nameSpring = spring({
    frame: adjustedFrame - 8,
    fps,
    config: { damping: 15, stiffness: 90 },
  });

  const nameOpacity = interpolate(nameSpring, [0, 1], [0, 1]);
  const nameTranslateX = interpolate(nameSpring, [0, 1], [-20, 0]);

  // Bar fill animation (delayed further)
  const barSpring = spring({
    frame: adjustedFrame - 15,
    fps,
    config: { damping: 20, stiffness: 60 },
  });

  const barWidth = interpolate(barSpring, [0, 1], [0, fillPercentage]);

  // Count animation (animates the number up)
  const countSpring = spring({
    frame: adjustedFrame - 20,
    fps,
    config: { damping: 20, stiffness: 80 },
    durationInFrames: 45,
  });

  const displayCount = Math.round(countSpring * userCount);

  // Subtle glow pulse
  const glowIntensity = interpolate(
    Math.sin(adjustedFrame * 0.08),
    [-1, 1],
    [0.3, 0.5]
  );

  const padding = width * 0.04;
  const flagSize = width * 0.1;
  const barHeight = width * 0.06;

  return (
    <div
      className="flex flex-col rounded-xl bg-zinc-900/90 backdrop-blur-sm border border-zinc-700/50"
      style={{
        width,
        padding,
        transform: `scale(${cardScale})`,
        opacity: cardOpacity,
        boxShadow: `0 0 ${20 * glowIntensity}px ${accentColor}20, 0 4px 15px rgba(0, 0, 0, 0.3)`,
      }}
    >
      {/* Top row: Flag + Country name + User count */}
      <div
        className="flex items-center"
        style={{
          gap: padding * 0.75,
          marginBottom: padding * 0.75,
        }}
      >
        {/* Flag */}
        <div
          className="flex-shrink-0 flex items-center justify-center"
          style={{
            fontSize: flagSize,
            transform: `scale(${flagScale})`,
            lineHeight: 1,
          }}
        >
          {flagEmoji}
        </div>

        {/* Country name */}
        <div
          className="font-semibold text-white truncate flex-1 min-w-0"
          style={{
            fontSize: width * 0.055,
            opacity: nameOpacity,
            transform: `translateX(${nameTranslateX}px)`,
            lineHeight: 1.2,
          }}
        >
          {countryName}
        </div>

        {/* User count */}
        {showCount && (
          <div
            className="flex-shrink-0 font-bold tabular-nums"
            style={{
              fontSize: width * 0.055,
              color: accentColor,
              opacity: nameOpacity,
              fontFamily: "Inconsolata, monospace",
              textShadow: `0 0 ${10 * glowIntensity}px ${accentColor}60`,
            }}
          >
            {displayCount}
          </div>
        )}
      </div>

      {/* Bar container */}
      <div
        className="relative rounded-full overflow-hidden bg-zinc-800/80"
        style={{
          height: barHeight,
        }}
      >
        {/* Animated fill bar */}
        <div
          className="absolute inset-y-0 left-0 rounded-full"
          style={{
            width: `${barWidth * 100}%`,
            background: `linear-gradient(90deg, ${accentColor}cc, ${accentColor})`,
            boxShadow: `0 0 ${15 * glowIntensity}px ${accentColor}60`,
          }}
        />
      </div>
    </div>
  );
};
