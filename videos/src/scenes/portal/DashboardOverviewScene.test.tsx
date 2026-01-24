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
  random: vi.fn((seed: string) => 0.5),
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

// Mock SparklineChart component
vi.mock("../../components/SparklineChart", () => ({
  SparklineChart: vi.fn(({ data, width, height, delay, showFill, strokeColor }) => (
    <div
      data-testid="sparkline-chart"
      data-points={data.length}
      data-width={width}
      data-height={height}
      data-delay={delay}
      data-show-fill={showFill}
      data-stroke-color={strokeColor}
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
    expect(sidebar).toHaveAttribute("data-width", "220");
    expect(sidebar).toHaveAttribute("data-delay", "0");
    // Should have navigation items
    expect(parseInt(sidebar?.getAttribute("data-nav-items") || "0")).toBeGreaterThan(0);
  });

  it("renders the 'Meine nächsten Meetup Termine' section", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const terminHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Meine nächsten Meetup Termine"
    );
    expect(terminHeader).toBeInTheDocument();
  });

  it("renders the upcoming Kempten meetup", () => {
    const { container } = render(<DashboardOverviewScene />);
    expect(container.textContent).toContain("Einundzwanzig Kempten");
    expect(container.textContent).toContain("06.02.2026 19:00 (CET)");
  });

  it("renders the 'Top Länder' section with countries", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const countryHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Top Länder"
    );
    expect(countryHeader).toBeInTheDocument();

    // Check for country names
    expect(container.textContent).toContain("Germany");
    expect(container.textContent).toContain("Austria");
    expect(container.textContent).toContain("Switzerland");
  });

  it("renders the 'Top Meetups' section", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const meetupHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Top Meetups"
    );
    expect(meetupHeader).toBeInTheDocument();

    // Check for meetup names
    expect(container.textContent).toContain("Einundzwanzig Saarland");
    expect(container.textContent).toContain("Einundzwanzig Frankfurt am Main");
  });

  it("renders the 'Meine Meetups' section", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const myMeetupsHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Meine Meetups"
    );
    expect(myMeetupsHeader).toBeInTheDocument();
  });

  it("renders SparklineChart components for country and meetup trends", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    // 5 countries + 5 meetups = 10 sparklines
    expect(sparklines.length).toBe(10);
  });

  it("renders SparklineCharts with green stroke color", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    sparklines.forEach((sparkline) => {
      expect(sparkline).toHaveAttribute("data-stroke-color", "#22c55e");
    });
  });

  it("renders ActivityItem components for activities", () => {
    const { container } = render(<DashboardOverviewScene />);
    const activityItems = container.querySelectorAll('[data-testid="activity-item"]');
    expect(activityItems.length).toBe(7);
  });

  it("renders the 'Aktivitäten' section", () => {
    const { container } = render(<DashboardOverviewScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const activityHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Aktivitäten"
    );
    expect(activityHeader).toBeInTheDocument();
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

  it("renders country flags", () => {
    const { container } = render(<DashboardOverviewScene />);
    expect(container.textContent).toContain("🇩🇪");
    expect(container.textContent).toContain("🇦🇹");
    expect(container.textContent).toContain("🇨🇭");
  });

  it("renders action buttons for user meetups", () => {
    const { container } = render(<DashboardOverviewScene />);
    expect(container.textContent).toContain("Neues Event erstellen");
    expect(container.textContent).toContain("Bearbeiten");
  });

  it("applies 3D perspective transform styles", () => {
    const { container } = render(<DashboardOverviewScene />);
    // Look for perspective transform in style attributes
    const elements = container.querySelectorAll('[style*="perspective"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders film grain overlay", () => {
    const { container } = render(<DashboardOverviewScene />);
    // Film grain uses mixBlendMode: overlay
    const grainElement = container.querySelector('[style*="overlay"]');
    expect(grainElement).toBeInTheDocument();
  });
});
