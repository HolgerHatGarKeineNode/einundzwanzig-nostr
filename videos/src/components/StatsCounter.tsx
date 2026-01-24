import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Easing,
} from "remotion";

export interface StatsCounterProps {
  /** The target number to count to (default: 204) */
  targetNumber?: number;
  /** Delay in frames before animation starts */
  delay?: number;
  /** Duration of the counting animation in frames (default: 60) */
  duration?: number;
  /** Label text displayed below the number */
  label?: string;
  /** Font size for the number in pixels (default: 120) */
  fontSize?: number;
  /** Whether to use spring animation (smoother) or linear interpolation */
  useSpring?: boolean;
  /** Prefix to display before the number (e.g., "$", "€") */
  prefix?: string;
  /** Suffix to display after the number (e.g., "%", "+") */
  suffix?: string;
  /** Number of decimal places to show (default: 0) */
  decimals?: number;
  /** Custom color for the number (default: #f7931a - Bitcoin orange) */
  color?: string;
}

export const StatsCounter: React.FC<StatsCounterProps> = ({
  targetNumber = 204,
  delay = 0,
  duration = 60,
  label,
  fontSize = 120,
  useSpring: useSpringAnimation = true,
  prefix = "",
  suffix = "",
  decimals = 0,
  color = "#f7931a",
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Calculate animation progress
  const adjustedFrame = Math.max(0, frame - delay);

  let progress: number;

  if (useSpringAnimation) {
    // Spring-based animation for smoother, more natural feel
    const springValue = spring({
      frame: adjustedFrame,
      fps,
      config: {
        damping: 20,
        stiffness: 80,
        mass: 1,
      },
      durationInFrames: duration,
    });
    progress = springValue;
  } else {
    // Linear interpolation with easeOut for consistent timing
    progress = interpolate(adjustedFrame, [0, duration], [0, 1], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
      easing: Easing.out(Easing.cubic),
    });
  }

  // Calculate the current displayed number
  const currentNumber = progress * targetNumber;
  const displayNumber = currentNumber.toFixed(decimals);

  // Scale animation for entrance
  const scaleSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const scale = interpolate(scaleSpring, [0, 1], [0.5, 1]);
  const opacity = interpolate(scaleSpring, [0, 1], [0, 1]);

  // Subtle glow effect that pulses with the counting
  const glowIntensity = interpolate(progress, [0, 0.5, 1], [0.3, 0.6, 0.4]);

  return (
    <div
      className="flex flex-col items-center justify-center"
      style={{
        transform: `scale(${scale})`,
        opacity,
      }}
    >
      {/* Number with glow effect */}
      <div
        className="relative font-bold tabular-nums"
        style={{
          fontSize,
          color,
          textShadow: `0 0 ${20 * glowIntensity}px ${color}, 0 0 ${40 * glowIntensity}px ${color}40`,
          fontFamily: "Inconsolata, monospace",
          letterSpacing: "-0.02em",
        }}
      >
        {prefix}
        {displayNumber}
        {suffix}
      </div>

      {/* Label */}
      {label && (
        <div
          className="mt-4 text-zinc-300 font-medium tracking-wide"
          style={{
            fontSize: fontSize * 0.2,
            opacity: interpolate(scaleSpring, [0, 1], [0, 0.9]),
          }}
        >
          {label}
        </div>
      )}
    </div>
  );
};
