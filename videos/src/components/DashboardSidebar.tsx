import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
} from "remotion";

export type SidebarNavItem = {
  /** Label for the navigation item */
  label: string;
  /** Optional icon name (dashboard, nostr, meetups, users, events, settings, language, interface, provider) */
  icon?: string;
  /** Optional badge count to display */
  badgeCount?: number;
  /** Whether this item is currently active/selected */
  isActive?: boolean;
  /** Whether this is a section header */
  isSection?: boolean;
  /** Indent level for nested items (0 = top level) */
  indentLevel?: number;
};

export type DashboardSidebarProps = {
  /** URL or staticFile path for the logo */
  logoSrc: string;
  /** Navigation items to display */
  navItems: SidebarNavItem[];
  /** Width of the sidebar in pixels (default: 280) */
  width?: number;
  /** Height of the sidebar in pixels (default: 1080) */
  height?: number;
  /** Delay in frames before animation starts */
  delay?: number;
  /** Custom accent color (default: #f7931a - Bitcoin orange) */
  accentColor?: string;
  /** Whether to animate items with stagger effect (default: true) */
  staggerItems?: boolean;
  /** Stagger delay in frames between items (default: 3) */
  staggerDelay?: number;
};

/**
 * SVG icons for navigation items
 */
const NavIcon: React.FC<{
  name: string;
  size: number;
  color: string;
}> = ({ name, size, color }) => {
  const iconProps = {
    width: size,
    height: size,
    viewBox: "0 0 24 24",
    fill: "none",
    stroke: color,
    strokeWidth: 2,
    strokeLinecap: "round" as const,
    strokeLinejoin: "round" as const,
  };

  switch (name) {
    case "dashboard":
      return (
        <svg {...iconProps}>
          <rect x="3" y="3" width="7" height="7" />
          <rect x="14" y="3" width="7" height="7" />
          <rect x="14" y="14" width="7" height="7" />
          <rect x="3" y="14" width="7" height="7" />
        </svg>
      );
    case "nostr":
      return (
        <svg {...iconProps}>
          <circle cx="12" cy="12" r="10" />
          <path d="M8 14s1.5 2 4 2 4-2 4-2" />
          <line x1="9" y1="9" x2="9.01" y2="9" />
          <line x1="15" y1="9" x2="15.01" y2="9" />
        </svg>
      );
    case "meetups":
      return (
        <svg {...iconProps}>
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      );
    case "users":
      return (
        <svg {...iconProps}>
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
          <circle cx="12" cy="7" r="4" />
        </svg>
      );
    case "events":
      return (
        <svg {...iconProps}>
          <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
          <line x1="16" y1="2" x2="16" y2="6" />
          <line x1="8" y1="2" x2="8" y2="6" />
          <line x1="3" y1="10" x2="21" y2="10" />
        </svg>
      );
    case "settings":
      return (
        <svg {...iconProps}>
          <circle cx="12" cy="12" r="3" />
          <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z" />
        </svg>
      );
    case "language":
      return (
        <svg {...iconProps}>
          <circle cx="12" cy="12" r="10" />
          <line x1="2" y1="12" x2="22" y2="12" />
          <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>
      );
    case "interface":
      return (
        <svg {...iconProps}>
          <rect x="2" y="3" width="20" height="14" rx="2" ry="2" />
          <line x1="8" y1="21" x2="16" y2="21" />
          <line x1="12" y1="17" x2="12" y2="21" />
        </svg>
      );
    case "provider":
      return (
        <svg {...iconProps}>
          <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
        </svg>
      );
    case "chevron":
      return (
        <svg {...iconProps}>
          <polyline points="9 18 15 12 9 6" />
        </svg>
      );
    default:
      return (
        <svg {...iconProps}>
          <circle cx="12" cy="12" r="10" />
        </svg>
      );
  }
};

export const DashboardSidebar: React.FC<DashboardSidebarProps> = ({
  logoSrc,
  navItems,
  width = 280,
  height = 1080,
  delay = 0,
  accentColor = "#f7931a",
  staggerItems = true,
  staggerDelay = 3,
}) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const adjustedFrame = Math.max(0, frame - delay);

  // Sidebar entrance animation (slide in from left)
  const sidebarSpring = spring({
    frame: adjustedFrame,
    fps,
    config: { damping: 15, stiffness: 80 },
  });

  const sidebarTranslateX = interpolate(sidebarSpring, [0, 1], [-width, 0]);
  const sidebarOpacity = interpolate(sidebarSpring, [0, 1], [0, 1]);

  // Logo animation (slightly delayed)
  const logoSpring = spring({
    frame: adjustedFrame - 10,
    fps,
    config: { damping: 12, stiffness: 100 },
  });

  const logoScale = interpolate(logoSpring, [0, 1], [0.5, 1]);
  const logoOpacity = interpolate(logoSpring, [0, 1], [0, 1]);

  // Subtle glow pulse
  const glowIntensity = interpolate(
    Math.sin(adjustedFrame * 0.06),
    [-1, 1],
    [0.2, 0.4]
  );

  const padding = width * 0.06;
  const logoHeight = width * 0.15;
  const itemHeight = width * 0.14;
  const iconSize = width * 0.07;
  const fontSize = width * 0.05;
  const badgeFontSize = width * 0.04;

  return (
    <div
      className="flex flex-col bg-zinc-900/95 backdrop-blur-md border-r border-zinc-700/50"
      style={{
        width,
        height,
        transform: `translateX(${sidebarTranslateX}px)`,
        opacity: sidebarOpacity,
        boxShadow: `${10 * glowIntensity}px 0 ${30 * glowIntensity}px rgba(0, 0, 0, 0.5)`,
      }}
    >
      {/* Logo Section */}
      <div
        className="flex items-center border-b border-zinc-700/50"
        style={{
          padding,
          height: logoHeight + padding * 2,
        }}
      >
        <div
          style={{
            transform: `scale(${logoScale})`,
            opacity: logoOpacity,
          }}
        >
          <Img
            src={logoSrc}
            style={{
              height: logoHeight,
              width: "auto",
              objectFit: "contain",
            }}
          />
        </div>
      </div>

      {/* Navigation Items */}
      <div
        className="flex-1 overflow-hidden"
        style={{
          paddingTop: padding * 0.5,
          paddingBottom: padding * 0.5,
        }}
      >
        {navItems.map((item, index) => {
          // Staggered animation for each item
          const itemDelay = staggerItems ? index * staggerDelay : 0;
          const itemFrame = adjustedFrame - 15 - itemDelay;

          const itemSpring = spring({
            frame: itemFrame,
            fps,
            config: { damping: 15, stiffness: 90 },
          });

          const itemOpacity = interpolate(itemSpring, [0, 1], [0, 1]);
          const itemTranslateX = interpolate(itemSpring, [0, 1], [-30, 0]);

          // Badge animation (delayed)
          const badgeSpring = spring({
            frame: itemFrame - 5,
            fps,
            config: { damping: 10, stiffness: 120 },
          });

          const badgeScale = interpolate(badgeSpring, [0, 1], [0, 1]);

          const indentPadding = (item.indentLevel || 0) * (padding * 0.8);

          // Section header styling
          if (item.isSection) {
            return (
              <div
                key={`section-${index}`}
                className="text-zinc-500 font-medium uppercase tracking-wider"
                style={{
                  fontSize: fontSize * 0.75,
                  paddingLeft: padding + indentPadding,
                  paddingRight: padding,
                  paddingTop: padding * 0.8,
                  paddingBottom: padding * 0.4,
                  opacity: itemOpacity,
                  transform: `translateX(${itemTranslateX}px)`,
                }}
              >
                {item.label}
              </div>
            );
          }

          return (
            <div
              key={`item-${index}`}
              className={`flex items-center cursor-pointer transition-colors ${
                item.isActive
                  ? "bg-zinc-800/80"
                  : "hover:bg-zinc-800/40"
              }`}
              style={{
                height: itemHeight,
                paddingLeft: padding + indentPadding,
                paddingRight: padding,
                opacity: itemOpacity,
                transform: `translateX(${itemTranslateX}px)`,
                borderLeft: item.isActive
                  ? `3px solid ${accentColor}`
                  : "3px solid transparent",
              }}
            >
              {/* Icon */}
              {item.icon && (
                <div
                  className="flex-shrink-0"
                  style={{
                    marginRight: padding * 0.6,
                    opacity: item.isActive ? 1 : 0.7,
                  }}
                >
                  <NavIcon
                    name={item.icon}
                    size={iconSize}
                    color={item.isActive ? accentColor : "#a1a1aa"}
                  />
                </div>
              )}

              {/* Label */}
              <span
                className={`flex-1 truncate ${
                  item.isActive ? "text-white font-semibold" : "text-zinc-300"
                }`}
                style={{
                  fontSize,
                  lineHeight: 1.2,
                }}
              >
                {item.label}
              </span>

              {/* Badge */}
              {item.badgeCount !== undefined && (
                <div
                  className="flex-shrink-0 rounded-full font-bold tabular-nums text-white"
                  style={{
                    backgroundColor: accentColor,
                    paddingLeft: padding * 0.4,
                    paddingRight: padding * 0.4,
                    paddingTop: padding * 0.15,
                    paddingBottom: padding * 0.15,
                    fontSize: badgeFontSize,
                    transform: `scale(${badgeScale})`,
                    minWidth: padding * 1.2,
                    textAlign: "center",
                    boxShadow: `0 0 ${8 * glowIntensity}px ${accentColor}60`,
                    fontFamily: "Inconsolata, monospace",
                  }}
                >
                  {item.badgeCount}
                </div>
              )}
            </div>
          );
        })}
      </div>

      {/* Footer gradient overlay */}
      <div
        className="pointer-events-none"
        style={{
          height: padding * 2,
          background: `linear-gradient(to top, rgba(24, 24, 27, 0.95), transparent)`,
        }}
      />
    </div>
  );
};
