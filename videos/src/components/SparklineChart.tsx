import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Easing,
} from "remotion";
import { useMemo, useId } from "react";

export interface SparklineChartProps {
  /** Data points for the sparkline (array of numbers) */
  data: number[];
  /** Width of the chart in pixels (default: 100) */
  width?: number;
  /** Height of the chart in pixels (default: 30) */
  height?: number;
  /** Stroke color (default: #f7931a - Bitcoin orange) */
  color?: string;
  /** Stroke width in pixels (default: 2) */
  strokeWidth?: number;
  /** Delay in frames before animation starts (default: 0) */
  delay?: number;
  /** Duration of the draw animation in frames (default: 45) */
  duration?: number;
  /** Whether to use spring animation (default: true) */
  useSpring?: boolean;
  /** Whether to show a fill gradient below the line (default: false) */
  showFill?: boolean;
  /** Fill opacity (0-1, default: 0.2) */
  fillOpacity?: number;
  /** Whether to show a glow effect (default: true) */
  showGlow?: boolean;
  /** Padding inside the chart (default: 2) */
  padding?: number;
}

/**
 * Generates SVG path data for a sparkline from data points
 */
function generatePath(
  data: number[],
  width: number,
  height: number,
  padding: number
): string {
  if (data.length === 0) return "";
  if (data.length === 1) {
    const y = height / 2;
    return `M ${padding} ${y} L ${width - padding} ${y}`;
  }

  const min = Math.min(...data);
  const max = Math.max(...data);
  const range = max - min || 1;

  const innerWidth = width - padding * 2;
  const innerHeight = height - padding * 2;

  const points = data.map((value, index) => {
    const x = padding + (index / (data.length - 1)) * innerWidth;
    const normalizedValue = (value - min) / range;
    // Invert Y axis (SVG 0,0 is top-left)
    const y = padding + (1 - normalizedValue) * innerHeight;
    return { x, y };
  });

  // Create path with smooth curves (Catmull-Rom to Bezier conversion)
  const pathParts = points.map((point, i) => {
    if (i === 0) {
      return `M ${point.x} ${point.y}`;
    }
    return `L ${point.x} ${point.y}`;
  });

  return pathParts.join(" ");
}

/**
 * Generates a closed path for the fill area below the line
 */
function generateFillPath(
  data: number[],
  width: number,
  height: number,
  padding: number
): string {
  if (data.length === 0) return "";

  const linePath = generatePath(data, width, height, padding);
  if (!linePath) return "";

  // Close the path at the bottom
  const firstX = padding;
  const lastX = width - padding;
  const bottomY = height - padding;

  return `${linePath} L ${lastX} ${bottomY} L ${firstX} ${bottomY} Z`;
}

/**
 * Calculate the total length of an SVG path
 */
function calculatePathLength(
  data: number[],
  width: number,
  height: number,
  padding: number
): number {
  if (data.length < 2) return 0;

  const min = Math.min(...data);
  const max = Math.max(...data);
  const range = max - min || 1;

  const innerWidth = width - padding * 2;
  const innerHeight = height - padding * 2;

  let totalLength = 0;

  for (let i = 1; i < data.length; i++) {
    const x1 = padding + ((i - 1) / (data.length - 1)) * innerWidth;
    const x2 = padding + (i / (data.length - 1)) * innerWidth;

    const y1 = padding + (1 - (data[i - 1] - min) / range) * innerHeight;
    const y2 = padding + (1 - (data[i] - min) / range) * innerHeight;

    const dx = x2 - x1;
    const dy = y2 - y1;
    totalLength += Math.sqrt(dx * dx + dy * dy);
  }

  return totalLength;
}

export const SparklineChart: React.FC<SparklineChartProps> = ({
  data,
  width = 100,
  height = 30,
  color = "#f7931a",
  strokeWidth = 2,
  delay = 0,
  duration = 45,
  useSpring: useSpringAnimation = true,
  showFill = false,
  fillOpacity = 0.2,
  showGlow = true,
  padding = 2,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Generate path data
  const pathData = useMemo(
    () => generatePath(data, width, height, padding),
    [data, width, height, padding]
  );

  const fillPathData = useMemo(
    () => (showFill ? generateFillPath(data, width, height, padding) : ""),
    [data, width, height, padding, showFill]
  );

  // Calculate path length for stroke-dasharray animation
  const pathLength = useMemo(
    () => calculatePathLength(data, width, height, padding),
    [data, width, height, padding]
  );

  // Calculate animation progress
  const adjustedFrame = Math.max(0, frame - delay);

  let progress: number;

  if (useSpringAnimation) {
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
    progress = interpolate(adjustedFrame, [0, duration], [0, 1], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
      easing: Easing.out(Easing.cubic),
    });
  }

  // Calculate stroke-dashoffset for line draw animation
  const dashOffset = interpolate(progress, [0, 1], [pathLength, 0]);

  // Opacity animation for entrance
  const opacity = interpolate(progress, [0, 0.2], [0, 1], {
    extrapolateRight: "clamp",
  });

  // Fill opacity animation (slightly delayed)
  const fillProgress = interpolate(progress, [0.3, 1], [0, 1], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // Glow intensity
  const glowIntensity = interpolate(progress, [0.5, 1], [0.3, 0.6], {
    extrapolateLeft: "clamp",
    extrapolateRight: "clamp",
  });

  // Generate unique ID for gradient using React's useId for deterministic IDs
  const reactId = useId();
  const gradientId = `sparkline-gradient-${reactId.replace(/:/g, "")}`;

  if (data.length === 0) {
    return null;
  }

  return (
    <svg
      width={width}
      height={height}
      viewBox={`0 0 ${width} ${height}`}
      style={{ opacity }}
    >
      <defs>
        {/* Gradient for fill area */}
        <linearGradient id={gradientId} x1="0%" y1="0%" x2="0%" y2="100%">
          <stop offset="0%" stopColor={color} stopOpacity={fillOpacity} />
          <stop offset="100%" stopColor={color} stopOpacity={0} />
        </linearGradient>

        {/* Glow filter */}
        {showGlow && (
          <filter id={`${gradientId}-glow`} x="-50%" y="-50%" width="200%" height="200%">
            <feGaussianBlur
              stdDeviation={2 * glowIntensity}
              result="coloredBlur"
            />
            <feMerge>
              <feMergeNode in="coloredBlur" />
              <feMergeNode in="SourceGraphic" />
            </feMerge>
          </filter>
        )}
      </defs>

      {/* Fill area (animated separately) */}
      {showFill && fillPathData && (
        <path
          d={fillPathData}
          fill={`url(#${gradientId})`}
          style={{
            opacity: fillProgress * fillOpacity,
          }}
        />
      )}

      {/* Main line with draw animation */}
      <path
        d={pathData}
        fill="none"
        stroke={color}
        strokeWidth={strokeWidth}
        strokeLinecap="round"
        strokeLinejoin="round"
        strokeDasharray={pathLength}
        strokeDashoffset={dashOffset}
        filter={showGlow ? `url(#${gradientId}-glow)` : undefined}
      />
    </svg>
  );
};
