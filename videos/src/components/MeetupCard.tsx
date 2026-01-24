import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
} from "remotion";

export type MeetupCardProps = {
  /** URL or staticFile path for the meetup logo */
  logoSrc: string;
  /** Name of the meetup */
  name: string;
  /** Location of the meetup */
  location: string;
  /** Delay in frames before animation starts */
  delay?: number;
  /** Width of the card in pixels (default: 400) */
  width?: number;
  /** Custom color for accent elements (default: #f7931a - Bitcoin orange) */
  accentColor?: string;
};

export const MeetupCard: React.FC<MeetupCardProps> = ({
  logoSrc,
  name,
  location,
  delay = 0,
  width = 400,
  accentColor = "#f7931a",
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Card entrance animation
  const cardSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 15, stiffness: 80 },
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.8, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);

  // Logo animation (slightly delayed)
  const logoSpring = spring({
    frame: adjustedFrame - 5,
    fps,
    config: { damping: 12, stiffness: 100 },
  });

  const logoScale = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoRotation = interpolate(logoSpring, [0, 1], [0, 360]);

  // Text animations (staggered)
  const nameSpring = spring({
    frame: adjustedFrame - 10,
    fps,
    config: { damping: 15, stiffness: 90 },
  });

  const nameOpacity = interpolate(nameSpring, [0, 1], [0, 1]);
  const nameTranslateY = interpolate(nameSpring, [0, 1], [20, 0]);

  const locationSpring = spring({
    frame: adjustedFrame - 15,
    fps,
    config: { damping: 15, stiffness: 90 },
  });

  const locationOpacity = interpolate(locationSpring, [0, 1], [0, 1]);
  const locationTranslateY = interpolate(locationSpring, [0, 1], [15, 0]);

  // Subtle glow pulse
  const glowIntensity = interpolate(
    Math.sin(adjustedFrame * 0.08),
    [-1, 1],
    [0.3, 0.5]
  );

  const logoSize = width * 0.25;
  const padding = width * 0.06;

  return (
    <div
      className="flex items-center rounded-2xl bg-zinc-900/90 backdrop-blur-sm border border-zinc-700/50"
      style={{
        width,
        padding,
        gap: padding,
        transform: `scale(${cardScale})`,
        opacity: cardOpacity,
        boxShadow: `0 0 ${30 * glowIntensity}px ${accentColor}30, 0 4px 20px rgba(0, 0, 0, 0.4)`,
      }}
    >
      {/* Logo Container */}
      <div
        className="relative flex-shrink-0 rounded-xl bg-zinc-800/80 flex items-center justify-center overflow-hidden"
        style={{
          width: logoSize,
          height: logoSize,
          transform: `scale(${logoScale}) rotate(${logoRotation}deg)`,
          boxShadow: `0 0 ${15 * glowIntensity}px ${accentColor}40`,
        }}
      >
        <Img
          src={logoSrc}
          style={{
            width: logoSize * 0.8,
            height: logoSize * 0.8,
            objectFit: "contain",
          }}
        />
      </div>

      {/* Text Content */}
      <div className="flex flex-col justify-center min-w-0 flex-1">
        {/* Meetup Name */}
        <div
          className="font-bold text-white truncate"
          style={{
            fontSize: width * 0.065,
            opacity: nameOpacity,
            transform: `translateY(${nameTranslateY}px)`,
            lineHeight: 1.2,
          }}
        >
          {name}
        </div>

        {/* Location */}
        <div
          className="flex items-center mt-1"
          style={{
            opacity: locationOpacity,
            transform: `translateY(${locationTranslateY}px)`,
          }}
        >
          {/* Location pin icon */}
          <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke={accentColor}
            strokeWidth={2}
            strokeLinecap="round"
            strokeLinejoin="round"
            style={{
              width: width * 0.045,
              height: width * 0.045,
              marginRight: width * 0.015,
              flexShrink: 0,
            }}
          >
            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z" />
            <circle cx="12" cy="10" r="3" />
          </svg>
          <span
            className="text-zinc-400 truncate"
            style={{
              fontSize: width * 0.05,
              lineHeight: 1.3,
            }}
          >
            {location}
          </span>
        </div>
      </div>
    </div>
  );
};
