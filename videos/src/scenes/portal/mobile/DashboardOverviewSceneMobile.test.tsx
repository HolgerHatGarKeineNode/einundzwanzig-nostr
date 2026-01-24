import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { DashboardOverviewSceneMobile } from "./DashboardOverviewSceneMobile";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 60),
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1080, height: 1920 })),
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
}));

// Mock @remotion/media
vi.mock("@remotion/media", () => ({
  Audio: vi.fn(({ src, volume }) => (
    <audio data-testid="audio" src={src} data-volume={volume} />
  )),
}));
/* eslint-enable @remotion/warn-native-media-tag */

// Mock StatsCounter component
vi.mock("../../../components/StatsCounter", () => ({
  StatsCounter: vi.fn(({ targetNumber, label, fontSize }) => (
    <div data-testid="stats-counter" data-target={targetNumber} data-label={label} data-fontsize={fontSize}>
      {targetNumber}
    </div>
  )),
}));

// Mock SparklineChart component
vi.mock("../../../components/SparklineChart", () => ({
  SparklineChart: vi.fn(({ width, height }) => (
    <div data-testid="sparkline-chart" data-width={width} data-height={height}>
      Sparkline
    </div>
  )),
}));

describe("DashboardOverviewSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the Dashboard header with mobile-optimized size", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Dashboard");
    // Mobile uses text-4xl vs desktop text-5xl
    expect(header).toHaveClass("text-4xl");
  });

  it("renders three stats cards (Meetups, Benutzer, Events)", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const statsCounters = container.querySelectorAll('[data-testid="stats-counter"]');
    expect(statsCounters.length).toBe(3);
  });

  it("renders stats counters with mobile-optimized font size (56px)", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const statsCounters = container.querySelectorAll('[data-testid="stats-counter"]');
    statsCounters.forEach((counter) => {
      // Mobile uses fontSize 56 vs desktop 72
      expect(counter).toHaveAttribute("data-fontsize", "56");
    });
  });

  it("renders sparkline charts for each stat", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    expect(sparklines.length).toBe(3);
  });

  it("does not render sidebar (mobile has no sidebar)", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const sidebar = container.querySelector('[data-testid="dashboard-sidebar"]');
    expect(sidebar).not.toBeInTheDocument();
  });

  it("renders card-slide audio", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const cardSlideAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("card-slide.mp3")
    );
    expect(cardSlideAudio).toBeInTheDocument();
  });

  it("renders ui-appear audio", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const uiAppearAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("ui-appear.mp3")
    );
    expect(uiAppearAudio).toBeInTheDocument();
  });

  it("renders wallpaper background", () => {
    const { container } = render(<DashboardOverviewSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });
});
