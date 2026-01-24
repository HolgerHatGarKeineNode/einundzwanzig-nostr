import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { TopMeetupsScene } from "./TopMeetupsScene";

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

describe("TopMeetupsScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<TopMeetupsScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<TopMeetupsScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the section header with correct text", () => {
    const { container } = render(<TopMeetupsScene />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Top Meetups");
    expect(header).toHaveClass("text-5xl");
    expect(header).toHaveClass("font-bold");
    expect(header).toHaveClass("text-white");
  });

  it("renders the subtitle text", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container.textContent).toContain("Die aktivsten lokalen Bitcoin-Communities");
  });

  it("renders all five meetups", () => {
    const { container } = render(<TopMeetupsScene />);
    // Each meetup has a logo image
    const meetupLogos = container.querySelectorAll('[src*="/static/logos/"]');
    expect(meetupLogos.length).toBe(5);
  });

  it("renders EINUNDZWANZIG Saarland as the leading meetup", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Saarland");
    // Verify it has location
    expect(container.textContent).toContain("Saarbrücken");
  });

  it("renders EINUNDZWANZIG Frankfurt am Main", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Frankfurt am Main");
    expect(container.textContent).toContain("Frankfurt");
  });

  it("renders EINUNDZWANZIG Kempten", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Kempten");
    expect(container.textContent).toContain("Kempten");
  });

  it("renders EINUNDZWANZIG Pfalz", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Pfalz");
    expect(container.textContent).toContain("Pfalz");
  });

  it("renders EINUNDZWANZIG Trier", () => {
    const { container } = render(<TopMeetupsScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Trier");
    expect(container.textContent).toContain("Trier");
  });

  it("renders rank badges for all meetups (1-5)", () => {
    const { container } = render(<TopMeetupsScene />);
    for (let i = 1; i <= 5; i++) {
      expect(container.textContent).toContain(String(i));
    }
  });

  it("renders sparkline charts for all meetups", () => {
    const { container } = render(<TopMeetupsScene />);
    const sparklineCharts = container.querySelectorAll('[data-testid="sparkline-chart"]');
    expect(sparklineCharts.length).toBe(5);
  });

  it("renders sparkline charts with correct styling", () => {
    const { container } = render(<TopMeetupsScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    const firstSparkline = sparklines[0];
    // Leading meetup should have Bitcoin orange color
    expect(firstSparkline).toHaveAttribute("data-color", "#f7931a");
    expect(firstSparkline).toHaveAttribute("data-stroke-width", "2");
    expect(firstSparkline).toHaveAttribute("data-show-fill", "true");
    expect(firstSparkline).toHaveAttribute("data-show-glow", "true");
  });

  it("renders non-leading sparklines with gray color", () => {
    const { container } = render(<TopMeetupsScene />);
    const sparklines = container.querySelectorAll('[data-testid="sparkline-chart"]');
    // Second sparkline (index 1) should be gray
    const secondSparkline = sparklines[1];
    expect(secondSparkline).toHaveAttribute("data-color", "#71717a");
    expect(secondSparkline).toHaveAttribute("data-show-glow", "false");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<TopMeetupsScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    // 5 checkmark pops + 1 slide-in = 6 sequences
    expect(sequences.length).toBe(6);
  });

  it("includes checkmark-pop audio for meetup entrances", () => {
    const { container } = render(<TopMeetupsScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const checkmarkPops = Array.from(audioElements).filter((audio) =>
      audio.getAttribute("src")?.includes("checkmark-pop.mp3")
    );
    expect(checkmarkPops.length).toBe(5);
  });

  it("includes slide-in audio for section entrance", () => {
    const { container } = render(<TopMeetupsScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<TopMeetupsScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("applies 3D perspective transform styles", () => {
    const { container } = render(<TopMeetupsScene />);
    const elements = container.querySelectorAll('[style*="perspective"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders meetup logos with correct paths", () => {
    const { container } = render(<TopMeetupsScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');

    // Check for Saarbrücken logo (used for Saarland meetup)
    const saarlandLogo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("EinundzwanzigSaarbrucken.png")
    );
    expect(saarlandLogo).toBeInTheDocument();

    // Check for Frankfurt logo
    const frankfurtLogo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("EinundzwanzigFrankfurtAmMain.png")
    );
    expect(frankfurtLogo).toBeInTheDocument();
  });

  it("renders progress bars for each meetup", () => {
    const { container } = render(<TopMeetupsScene />);
    // Progress bars have rounded-full class and bg-zinc-700/50 (container)
    const progressBarContainers = container.querySelectorAll(".bg-zinc-700\\/50.rounded-full");
    expect(progressBarContainers.length).toBe(5);
  });

  it("renders meetups in a vertical flex layout", () => {
    const { container } = render(<TopMeetupsScene />);
    const flexCol = container.querySelector(".flex-col.gap-4");
    expect(flexCol).toBeInTheDocument();
  });
});
