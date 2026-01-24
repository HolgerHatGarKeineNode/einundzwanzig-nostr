import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { DashboardOverviewScene } from "./DashboardOverviewScene";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 60),
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1920, height: 1080 })),
  interpolate: vi.fn((value, inputRange, outputRange, options) => {
    const [inMin, inMax] = inputRange;
    const [outMin, outMax] = outputRange;
    let progress = (value - inMin) / (inMax - inMin);
    if (options?.extrapolateLeft === "clamp") {
      progress = Math.max(0, progress);
    }
    if (options?.extrapolateRight === "clamp") {
      progress = Math.min(1, progress);
    }
    return outMin + progress * (outMax - outMin);
  }),
  spring: vi.fn(() => 1),
  AbsoluteFill: vi.fn(({ children, className, style }) => (
    <div data-testid="absolute-fill" className={className} style={style}>
      {children}
    </div>
  )),
  Img: vi.fn(({ src, className, style }) => (
    <img data-testid="remotion-img" src={src} className={className} style={style} />
  )),
  staticFile: vi.fn((path: string) => `/static/${path}`),
  Sequence: vi.fn(({ children, from, durationInFrames }) => (
    <div data-testid="sequence" data-from={from} data-duration={durationInFrames}>
      {children}
    </div>
  )),
  Easing: {
    out: vi.fn((fn) => fn),
    cubic: vi.fn((t: number) => t),
  },
}));

// Mock @remotion/media
vi.mock("@remotion/media", () => ({
  Audio: vi.fn(({ src, volume }) => (
    <audio data-testid="audio" src={src} data-volume={volume} />
  )),
}));
/* eslint-enable @remotion/warn-native-media-tag */

// Mock DashboardSidebar component
vi.mock("../../components/DashboardSidebar", () => ({
  DashboardSidebar: vi.fn(({ logoSrc, navItems, width, height, delay }) => (
    <div
      data-testid="dashboard-sidebar"
      data-logo-src={logoSrc}
      data-nav-items={navItems.length}
      data-width={width}
      data-height={height}
      data-delay={delay}
    >
      DashboardSidebar
    </div>
  )),
}));

// Mock StatsCounter component
vi.mock("../../components/StatsCounter", () => ({
  StatsCounter: vi.fn(({ targetNumber, delay, label, fontSize, color }) => (
    <div
      data-testid="stats-counter"
      data-target={targetNumber}
      data-delay={delay}
      data-label={label}
      data-font-size={fontSize}
      data-color={color}
    >
      {targetNumber}
    </div>
  )),
}));

// Mock SparklineChart component
vi.mock("../../components/SparklineChart", () => ({
  SparklineChart: vi.fn(({ data, width, height, delay, showFill }) => (
    <div
      data-testid="sparkline-chart"
      data-points={data.length}
      data-width={width}
      data-height={height}
      data-delay={delay}
      data-show-fill={showFill}
    >
      SparklineChart
    </div>
  )),
}));

// Mock ActivityItem component
vi.mock("../../components/ActivityItem", () => ({
  ActivityItem: vi.fn(({ eventName, timestamp, badgeText, delay, width }) => (
    <div
      data-testid="activity-item"
      data-event-name={eventName}
      data-timestamp={timestamp}
      data-badge-text={badgeText}
      data-delay={delay}
      data-width={width}
    >
      {eventName}
    </div>
  )),
}));

describe("DashboardOverviewScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<DashboardOverviewScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<DashboardOverviewScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<DashboardOverviewScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the DashboardSidebar component with correct props", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sidebar = container.querySelector('[data-testid="dashboard-sidebar"]');
    expect(sidebar).toBeInTheDocument();
    expect(sidebar).toHaveAttribute("data-width", "280");
    expect(sidebar).toHaveAttribute("data-height", "1080");
    expect(sidebar).toHaveAttribute("data-delay", "0");
    // Should have navigation items
    expect(parseInt(sidebar?.getAttribute("data-nav-items") || "0")).toBeGreaterThan(0);
  });

  it("renders the Dashboard header", () => {
    const { container } = render(<DashboardOverviewScene />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Dashboard");
    expect(header).toHaveClass("text-5xl");
    expect(header).toHaveClass("font-bold");
    expect(header).toHaveClass("text-white");
  });

  it("renders the welcome subtitle", () => {
    const { container } = render(<DashboardOverviewScene />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Willkommen im Einundzwanzig Portal");
    expect(subtitle).toHaveClass("text-zinc-400");
  });

  it("renders three StatsCounter components", () => {
    const { container } = render(<DashboardOverviewScene />);
    const statsCounters = container.querySelectorAll('[data-testid="stats-counter"]');
    expect(statsCounters.length).toBe(3);
  });

  it("renders StatsCounter for Meetups with target 204", () => {
    const { container } = render(<DashboardOverviewScene />);
    const statsCounters = container.querySelectorAll('[data-testid="stats-counter"]');
    const meetupsCounter = Array.from(statsCounters).find(
      (counter) => counter.getAttribute("data-target") === "204"
    );
    expect(meetupsCounter).toBeInTheDocument();
    expect(meetupsCounter).toHaveAttribute("data-label", "Aktive Gruppen");
  });

  it("renders StatsCounter for Users with target 1247", () => {
    const { container } = render(<DashboardOverviewScene />);
    const statsCounters = container.querySelectorAll('[data-testid="stats-counter"]');
    const usersCounter = Array.from(statsCounters).find(
      (counter) => counter.getAttribute("data-target") === "1247"
    );
    expect(usersCounter).toBeInTheDocument();
    expect(usersCounter).toHaveAttribute("data-label", "Registrierte Nutzer");
  });

  it("renders StatsCounter for Events with target 89", () => {
    const { container } = render(<DashboardOverviewScene />);
    const statsCounters = container.querySelectorAll('[data-testid="stats-counter"]');
    const eventsCounter = Array.from(statsCounters).find(
      (counter) => counter.getAttribute("data-target") === "89"
    );
    expect(eventsCounter).toBeInTheDocument();
    expect(eventsCounter).toHaveAttribute("data-label", "Diese Woche");
  });

  it("renders three SparklineChart components for trends", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    expect(sparklines.length).toBe(3);
  });

  it("renders SparklineCharts with fill enabled", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    sparklines.forEach((sparkline) => {
      expect(sparkline).toHaveAttribute("data-show-fill", "true");
    });
  });

  it("renders ActivityItem components for recent activities", () => {
    const { container } = render(<DashboardOverviewScene />);
    const activityItems = container.querySelectorAll('[data-testid="activity-item"]');
    expect(activityItems.length).toBe(3);
  });

  it("renders ActivityItem for EINUNDZWANZIG Kempten", () => {
    const { container } = render(<DashboardOverviewScene />);
    const activityItems = container.querySelectorAll('[data-testid="activity-item"]');
    const kemptenActivity = Array.from(activityItems).find(
      (item) => item.getAttribute("data-event-name") === "EINUNDZWANZIG Kempten"
    );
    expect(kemptenActivity).toBeInTheDocument();
    expect(kemptenActivity).toHaveAttribute("data-timestamp", "vor 13 Stunden");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBeGreaterThanOrEqual(2);
  });

  it("includes card-slide audio", () => {
    const { container } = render(<DashboardOverviewScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const cardSlideAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("card-slide.mp3")
    );
    expect(cardSlideAudio).toBeInTheDocument();
  });

  it("includes ui-appear audio", () => {
    const { container } = render(<DashboardOverviewScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const uiAppearAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("ui-appear.mp3")
    );
    expect(uiAppearAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<DashboardOverviewScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("renders the Letzte Aktivitäten section header", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const activityHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Letzte Aktivitäten"
    );
    expect(activityHeader).toBeInTheDocument();
  });

  it("renders the Schnellübersicht section header", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const quickStatsHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Schnellübersicht"
    );
    expect(quickStatsHeader).toBeInTheDocument();
  });

  it("renders quick stats labels", () => {
    const { container } = render(<DashboardOverviewScene />);
    expect(container.textContent).toContain("Länder");
    expect(container.textContent).toContain("Neue diese Woche");
    expect(container.textContent).toContain("Aktive Nutzer");
  });

  it("renders card section headers for stats", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const headerTexts = Array.from(sectionHeaders).map((h3) => h3.textContent);
    expect(headerTexts).toContain("Meetups");
    expect(headerTexts).toContain("Benutzer");
    expect(headerTexts).toContain("Events");
  });

  it("applies 3D perspective transform styles", () => {
    const { container } = render(<DashboardOverviewScene />);
    // Look for perspective transform in style attributes
    const elements = container.querySelectorAll('[style*="perspective"]');
    expect(elements.length).toBeGreaterThan(0);
  });
});
