import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { TopMeetupsSceneMobile } from "./TopMeetupsSceneMobile";

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

// Mock SparklineChart component
vi.mock("../../../components/SparklineChart", () => ({
  SparklineChart: vi.fn(({ width, height }) => (
    <div data-testid="sparkline-chart" data-width={width} data-height={height}>
      Sparkline
    </div>
  )),
}));

describe("TopMeetupsSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the header with mobile-optimized size", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Top Meetups");
    // Mobile uses text-4xl vs desktop text-5xl
    expect(header).toHaveClass("text-4xl");
  });

  it("renders five meetup rows", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const meetupLogos = container.querySelectorAll('[data-testid="remotion-img"]');
    // 5 meetup logos + 1 wallpaper = 6 images
    expect(meetupLogos.length).toBe(6);
  });

  it("renders sparkline charts with mobile-optimized dimensions", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    expect(sparklines.length).toBe(5);
    sparklines.forEach((sparkline) => {
      // Mobile uses 70px width vs desktop 100px
      expect(sparkline).toHaveAttribute("data-width", "70");
      expect(sparkline).toHaveAttribute("data-height", "28");
    });
  });

  it("renders checkmark-pop audio for each meetup", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const audioSequences = Array.from(sequences).filter((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("checkmark-pop.mp3");
    });
    expect(audioSequences.length).toBe(5);
  });

  it("renders slide-in audio for section entrance", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("renders wallpaper background", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders rank badges for each meetup", () => {
    const { container } = render(<TopMeetupsSceneMobile />);
    // Rank badges are styled as rounded-full circles
    const rankBadges = container.querySelectorAll('[class*="rounded-full"]');
    expect(rankBadges.length).toBeGreaterThanOrEqual(5);
  });
});
