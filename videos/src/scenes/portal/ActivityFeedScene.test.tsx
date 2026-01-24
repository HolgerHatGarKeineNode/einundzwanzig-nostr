import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { ActivityFeedScene } from "./ActivityFeedScene";

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

// Mock ActivityItem component
vi.mock("../../components/ActivityItem", () => ({
  ActivityItem: vi.fn(({ eventName, timestamp, badgeText, showBadge, delay, width, accentColor }) => (
    <div
      data-testid="activity-item"
      data-event-name={eventName}
      data-timestamp={timestamp}
      data-badge-text={badgeText}
      data-show-badge={showBadge}
      data-delay={delay}
      data-width={width}
      data-accent-color={accentColor}
    >
      <span className="event-name">{eventName}</span>
      <span className="timestamp">{timestamp}</span>
      {showBadge && <span className="badge">{badgeText}</span>}
    </div>
  )),
}));

describe("ActivityFeedScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<ActivityFeedScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<ActivityFeedScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<ActivityFeedScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the section header with 'Aktivitäten'", () => {
    const { container } = render(<ActivityFeedScene />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Aktivitäten");
    expect(header).toHaveClass("text-5xl");
    expect(header).toHaveClass("font-bold");
    expect(header).toHaveClass("text-white");
  });

  it("renders the subtitle text", () => {
    const { container } = render(<ActivityFeedScene />);
    expect(container.textContent).toContain("Der Puls der Bitcoin-Community");
  });

  it("renders the LIVE indicator", () => {
    const { container } = render(<ActivityFeedScene />);
    expect(container.textContent).toContain("LIVE");
  });

  it("renders the green live indicator dot", () => {
    const { container } = render(<ActivityFeedScene />);
    const greenDot = container.querySelector(".bg-green-500.rounded-full");
    expect(greenDot).toBeInTheDocument();
  });

  it("renders all four activity items", () => {
    const { container } = render(<ActivityFeedScene />);
    const activityItems = container.querySelectorAll('[data-testid="activity-item"]');
    expect(activityItems.length).toBe(4);
  });

  it("renders EINUNDZWANZIG Kempten activity", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    const kemptenItem = Array.from(items).find(
      (item) => item.getAttribute("data-event-name") === "EINUNDZWANZIG Kempten"
    );
    expect(kemptenItem).toBeInTheDocument();
    expect(kemptenItem).toHaveAttribute("data-timestamp", "vor 13 Stunden");
  });

  it("renders EINUNDZWANZIG Darmstadt activity", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    const darmstadtItem = Array.from(items).find(
      (item) => item.getAttribute("data-event-name") === "EINUNDZWANZIG Darmstadt"
    );
    expect(darmstadtItem).toBeInTheDocument();
    expect(darmstadtItem).toHaveAttribute("data-timestamp", "vor 21 Stunden");
  });

  it("renders EINUNDZWANZIG Vulkaneifel activity", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    const vulkaneifelItem = Array.from(items).find(
      (item) => item.getAttribute("data-event-name") === "EINUNDZWANZIG Vulkaneifel"
    );
    expect(vulkaneifelItem).toBeInTheDocument();
    expect(vulkaneifelItem).toHaveAttribute("data-timestamp", "vor 2 Tagen");
  });

  it("renders BitcoinWalk Würzburg activity", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    const wurzburgItem = Array.from(items).find(
      (item) => item.getAttribute("data-event-name") === "BitcoinWalk Würzburg"
    );
    expect(wurzburgItem).toBeInTheDocument();
    expect(wurzburgItem).toHaveAttribute("data-timestamp", "vor 2 Tagen");
  });

  it("renders activity items with 'Neuer Termin' badge text", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    items.forEach((item) => {
      expect(item).toHaveAttribute("data-badge-text", "Neuer Termin");
    });
  });

  it("renders activity items with badges visible", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    items.forEach((item) => {
      expect(item).toHaveAttribute("data-show-badge", "true");
    });
  });

  it("renders activity items with Bitcoin orange accent color", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    items.forEach((item) => {
      expect(item).toHaveAttribute("data-accent-color", "#f7931a");
    });
  });

  it("renders activity items with width of 480px", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    items.forEach((item) => {
      expect(item).toHaveAttribute("data-width", "480");
    });
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<ActivityFeedScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    // 4 button-click sounds + 1 slide-in = 5 sequences
    expect(sequences.length).toBe(5);
  });

  it("includes button-click audio for activity item entrances", () => {
    const { container } = render(<ActivityFeedScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const buttonClicks = Array.from(audioElements).filter((audio) =>
      audio.getAttribute("src")?.includes("button-click.mp3")
    );
    expect(buttonClicks.length).toBe(4);
  });

  it("includes slide-in audio for section entrance", () => {
    const { container } = render(<ActivityFeedScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<ActivityFeedScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("applies 3D perspective transform styles", () => {
    const { container } = render(<ActivityFeedScene />);
    const elements = container.querySelectorAll('[style*="perspective"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders activity items in a vertical flex layout with gap", () => {
    const { container } = render(<ActivityFeedScene />);
    const flexCol = container.querySelector(".flex-col.gap-4");
    expect(flexCol).toBeInTheDocument();
  });

  it("renders activity items with staggered delays", () => {
    const { container } = render(<ActivityFeedScene />);
    const items = container.querySelectorAll('[data-testid="activity-item"]');
    const delays = Array.from(items).map((item) =>
      parseInt(item.getAttribute("data-delay") || "0", 10)
    );

    // Each item should have an increasing delay
    for (let i = 1; i < delays.length; i++) {
      expect(delays[i]).toBeGreaterThan(delays[i - 1]);
    }
  });

  it("renders gradient overlay for background effect", () => {
    const { container } = render(<ActivityFeedScene />);
    const gradientElements = container.querySelectorAll('[style*="radial-gradient"]');
    expect(gradientElements.length).toBeGreaterThan(0);
  });
});
