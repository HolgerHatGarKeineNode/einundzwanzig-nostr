import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { CountryStatsScene } from "./CountryStatsScene";

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

// Mock CountryBar component
vi.mock("../../components/CountryBar", () => ({
  CountryBar: vi.fn(({ countryName, flagEmoji, userCount, maxCount, width, delay, accentColor, showCount }) => (
    <div
      data-testid="country-bar"
      data-country-name={countryName}
      data-flag-emoji={flagEmoji}
      data-user-count={userCount}
      data-max-count={maxCount}
      data-width={width}
      data-delay={delay}
      data-accent-color={accentColor}
      data-show-count={showCount}
    >
      {countryName}: {userCount}
    </div>
  )),
}));

// Mock SparklineChart component
vi.mock("../../components/SparklineChart", () => ({
  SparklineChart: vi.fn(({ data, width, height, delay, color, strokeWidth, showFill, fillOpacity, showGlow }) => (
    <svg
      data-testid="sparkline-chart"
      data-points={data.length}
      data-width={width}
      data-height={height}
      data-delay={delay}
      data-color={color}
      data-stroke-width={strokeWidth}
      data-show-fill={showFill}
      data-fill-opacity={fillOpacity}
      data-show-glow={showGlow}
    />
  )),
}));

describe("CountryStatsScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<CountryStatsScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<CountryStatsScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<CountryStatsScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the section header with correct text", () => {
    const { container } = render(<CountryStatsScene />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Community nach Ländern");
    expect(header).toHaveClass("text-5xl");
    expect(header).toHaveClass("font-bold");
    expect(header).toHaveClass("text-white");
  });

  it("renders the subtitle text", () => {
    const { container } = render(<CountryStatsScene />);
    expect(container.textContent).toContain("Die deutschsprachige Bitcoin-Community wächst überall");
  });

  it("renders all six countries", () => {
    const { container } = render(<CountryStatsScene />);
    const countryBars = container.querySelectorAll('[data-testid="country-bar"]');
    expect(countryBars.length).toBe(6);
  });

  it("renders Germany with correct data", () => {
    const { container } = render(<CountryStatsScene />);
    const germanyBar = container.querySelector('[data-country-name="Germany"]');
    expect(germanyBar).toBeInTheDocument();
    expect(germanyBar).toHaveAttribute("data-flag-emoji", "🇩🇪");
    expect(germanyBar).toHaveAttribute("data-user-count", "458");
  });

  it("renders Austria with correct data", () => {
    const { container } = render(<CountryStatsScene />);
    const austriaBar = container.querySelector('[data-country-name="Austria"]');
    expect(austriaBar).toBeInTheDocument();
    expect(austriaBar).toHaveAttribute("data-flag-emoji", "🇦🇹");
    expect(austriaBar).toHaveAttribute("data-user-count", "59");
  });

  it("renders Switzerland with correct data", () => {
    const { container } = render(<CountryStatsScene />);
    const switzerlandBar = container.querySelector('[data-country-name="Switzerland"]');
    expect(switzerlandBar).toBeInTheDocument();
    expect(switzerlandBar).toHaveAttribute("data-flag-emoji", "🇨🇭");
    expect(switzerlandBar).toHaveAttribute("data-user-count", "34");
  });

  it("renders Luxembourg with correct data", () => {
    const { container } = render(<CountryStatsScene />);
    const luxembourgBar = container.querySelector('[data-country-name="Luxembourg"]');
    expect(luxembourgBar).toBeInTheDocument();
    expect(luxembourgBar).toHaveAttribute("data-flag-emoji", "🇱🇺");
    expect(luxembourgBar).toHaveAttribute("data-user-count", "8");
  });

  it("renders Bulgaria with correct data", () => {
    const { container } = render(<CountryStatsScene />);
    const bulgariaBar = container.querySelector('[data-country-name="Bulgaria"]');
    expect(bulgariaBar).toBeInTheDocument();
    expect(bulgariaBar).toHaveAttribute("data-flag-emoji", "🇧🇬");
    expect(bulgariaBar).toHaveAttribute("data-user-count", "7");
  });

  it("renders Spain with correct data", () => {
    const { container } = render(<CountryStatsScene />);
    const spainBar = container.querySelector('[data-country-name="Spain"]');
    expect(spainBar).toBeInTheDocument();
    expect(spainBar).toHaveAttribute("data-flag-emoji", "🇪🇸");
    expect(spainBar).toHaveAttribute("data-user-count", "3");
  });

  it("renders sparkline charts for all countries", () => {
    const { container } = render(<CountryStatsScene />);
    const sparklineCharts = container.querySelectorAll('[data-testid="sparkline-chart"]');
    expect(sparklineCharts.length).toBe(6);
  });

  it("renders sparkline charts with correct styling", () => {
    const { container } = render(<CountryStatsScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    const firstSparkline = sparklines[0];
    expect(firstSparkline).toHaveAttribute("data-color", "#f7931a");
    expect(firstSparkline).toHaveAttribute("data-stroke-width", "2");
    expect(firstSparkline).toHaveAttribute("data-show-fill", "true");
    expect(firstSparkline).toHaveAttribute("data-show-glow", "true");
  });

  it('renders the "Nutzer weltweit" total badge', () => {
    const { container } = render(<CountryStatsScene />);
    expect(container.textContent).toContain("Nutzer weltweit");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<CountryStatsScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    // 6 country chimes + 1 slide-in = 7 sequences
    expect(sequences.length).toBe(7);
  });

  it("includes success-chime audio for country entrances", () => {
    const { container } = render(<CountryStatsScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const successChimes = Array.from(audioElements).filter((audio) =>
      audio.getAttribute("src")?.includes("success-chime.mp3")
    );
    expect(successChimes.length).toBe(6);
  });

  it("includes slide-in audio for section entrance", () => {
    const { container } = render(<CountryStatsScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<CountryStatsScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("applies 3D perspective transform styles", () => {
    const { container } = render(<CountryStatsScene />);
    const elements = container.querySelectorAll('[style*="perspective"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders the globe icon SVG", () => {
    const { container } = render(<CountryStatsScene />);
    const svgElements = container.querySelectorAll("svg");
    // Should have globe icon
    expect(svgElements.length).toBeGreaterThanOrEqual(1);
  });

  it("renders CountryBar with maxCount set to Germany's count (highest)", () => {
    const { container } = render(<CountryStatsScene />);
    const countryBars = container.querySelectorAll('[data-testid="country-bar"]');
    // All bars should have maxCount = 458 (Germany's count)
    countryBars.forEach((bar) => {
      expect(bar).toHaveAttribute("data-max-count", "458");
    });
  });

  it("renders CountryBar with Bitcoin orange accent color", () => {
    const { container } = render(<CountryStatsScene />);
    const countryBars = container.querySelectorAll('[data-testid="country-bar"]');
    countryBars.forEach((bar) => {
      expect(bar).toHaveAttribute("data-accent-color", "#f7931a");
    });
  });

  it("renders countries in a 2-column grid layout", () => {
    const { container } = render(<CountryStatsScene />);
    const grid = container.querySelector(".grid-cols-2");
    expect(grid).toBeInTheDocument();
  });
});
