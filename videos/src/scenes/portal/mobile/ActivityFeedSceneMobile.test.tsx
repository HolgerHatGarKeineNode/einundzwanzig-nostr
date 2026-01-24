import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { ActivityFeedSceneMobile } from "./ActivityFeedSceneMobile";

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

// Mock ActivityItem component
vi.mock("../../../components/ActivityItem", () => ({
  ActivityItem: vi.fn(({ eventName, width }) => (
    <div data-testid="activity-item" data-event={eventName} data-width={width}>
      {eventName}
    </div>
  )),
}));

describe("ActivityFeedSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the header with mobile-optimized size", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Aktivitäten");
    // Mobile uses text-4xl vs desktop text-5xl
    expect(header).toHaveClass("text-4xl");
  });

  it("renders LIVE indicator badge", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const liveIndicator = container.querySelector('[class*="bg-green-500"]');
    expect(liveIndicator).toBeInTheDocument();
  });

  it("renders four activity items", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const activityItems = container.querySelectorAll('[data-testid="activity-item"]');
    expect(activityItems.length).toBe(4);
  });

  it("renders activity items with mobile-optimized width (400px)", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const activityItems = container.querySelectorAll('[data-testid="activity-item"]');
    activityItems.forEach((item) => {
      // Mobile uses 400px vs desktop 480px
      expect(item).toHaveAttribute("data-width", "400");
    });
  });

  it("renders button-click audio for each activity", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const audioSequences = Array.from(sequences).filter((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("button-click.mp3");
    });
    expect(audioSequences.length).toBe(4);
  });

  it("renders slide-in audio for section entrance", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("renders wallpaper background", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders subtitle with correct styling", () => {
    const { container } = render(<ActivityFeedSceneMobile />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Der Puls der Bitcoin-Community");
    expect(subtitle).toHaveClass("text-lg");
  });
});
