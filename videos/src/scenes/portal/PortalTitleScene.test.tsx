import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalTitleScene } from "./PortalTitleScene";

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
    <img
      data-testid="remotion-img"
      src={src}
      className={className}
      style={style}
    />
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

describe("PortalTitleScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalTitleScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<PortalTitleScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<PortalTitleScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the title element", () => {
    const { container } = render(<PortalTitleScene />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveClass("text-7xl");
    expect(title).toHaveClass("font-bold");
    expect(title).toHaveClass("text-white");
  });

  it("displays typed text based on current frame", () => {
    const { container } = render(<PortalTitleScene />);
    const title = container.querySelector("h1");
    // At frame 60 with 2 frames per char, we should have 30 characters typed
    // But title is only 20 chars, so full title should be displayed
    expect(title?.textContent).toContain("EINUNDZWANZIG PORTAL");
  });

  it("renders the cursor element with orange color", () => {
    const { container } = render(<PortalTitleScene />);
    const cursor = container.querySelector(".text-orange-500");
    expect(cursor).toBeInTheDocument();
    expect(cursor?.textContent).toBe("|");
  });

  it("renders the subtitle text", () => {
    const { container } = render(<PortalTitleScene />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent(
      "Das Herzstück der Bitcoin-Community"
    );
    expect(subtitle).toHaveClass("text-zinc-300");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<PortalTitleScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBeGreaterThanOrEqual(2);
  });

  it("includes typing audio", () => {
    const { container } = render(<PortalTitleScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const typingAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("typing.mp3")
    );
    expect(typingAudio).toBeInTheDocument();
  });

  it("includes ui-appear audio for subtitle", () => {
    const { container } = render(<PortalTitleScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const uiAppearAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("ui-appear.mp3")
    );
    expect(uiAppearAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<PortalTitleScene />);
    const vignette = container.querySelector('[class*="pointer-events-none"]');
    expect(vignette).toBeInTheDocument();
  });

  it("renders center glow effect", () => {
    const { container } = render(<PortalTitleScene />);
    // Look for the glow element with blur filter
    const elements = container.querySelectorAll('[style*="blur(60px)"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders decorative line under subtitle", () => {
    const { container } = render(<PortalTitleScene />);
    const line = container.querySelector(".h-0\\.5");
    expect(line).toBeInTheDocument();
    expect(line).toHaveClass("bg-gradient-to-r");
  });
});

describe("PortalTitleScene typing animation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("shows partial text at early frames", async () => {
    const remotion = await import("remotion");
    vi.mocked(remotion.useCurrentFrame).mockReturnValue(10);

    const { container } = render(<PortalTitleScene />);
    const title = container.querySelector("h1");
    // At frame 10 with 2 frames per char = 5 characters
    const titleSpan = title?.querySelector("span");
    expect(titleSpan?.textContent?.length).toBeLessThanOrEqual(5);
  });

  it("shows full title after typing completes", async () => {
    const remotion = await import("remotion");
    // Title is 20 chars, 2 frames per char = 40 frames to complete
    vi.mocked(remotion.useCurrentFrame).mockReturnValue(50);

    const { container } = render(<PortalTitleScene />);
    const title = container.querySelector("h1");
    expect(title?.textContent).toContain("EINUNDZWANZIG PORTAL");
  });
});
