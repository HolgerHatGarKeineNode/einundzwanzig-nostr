import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { CountryStatsSceneMobile } from "./CountryStatsSceneMobile";

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

// Mock CountryBar component
vi.mock("../../../components/CountryBar", () => ({
  CountryBar: vi.fn(({ countryName, userCount, width }) => (
    <div data-testid="country-bar" data-country={countryName} data-count={userCount} data-width={width}>
      {countryName}: {userCount}
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

describe("CountryStatsSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the header with mobile-optimized size", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Community nach Ländern");
    // Mobile uses text-4xl vs desktop text-5xl
    expect(header).toHaveClass("text-4xl");
  });

  it("renders six country bars", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const countryBars = container.querySelectorAll('[data-testid="country-bar"]');
    expect(countryBars.length).toBe(6);
  });

  it("renders country bars with mobile-optimized width (280px)", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const countryBars = container.querySelectorAll('[data-testid="country-bar"]');
    countryBars.forEach((bar) => {
      // Mobile uses 280px vs desktop 380px
      expect(bar).toHaveAttribute("data-width", "280");
    });
  });

  it("renders sparkline charts for each country", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    expect(sparklines.length).toBe(6);
  });

  it("renders sparklines with mobile-optimized dimensions", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    sparklines.forEach((sparkline) => {
      // Mobile uses 80px width vs desktop 120px
      expect(sparkline).toHaveAttribute("data-width", "80");
      expect(sparkline).toHaveAttribute("data-height", "32");
    });
  });

  it("renders total users badge", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const badge = container.querySelector('[class*="rounded-xl"]');
    expect(badge).toBeInTheDocument();
  });

  it("renders success-chime audio for each country", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const audioSequences = Array.from(sequences).filter((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("success-chime.mp3");
    });
    expect(audioSequences.length).toBe(6);
  });

  it("renders wallpaper background", () => {
    const { container } = render(<CountryStatsSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });
});
