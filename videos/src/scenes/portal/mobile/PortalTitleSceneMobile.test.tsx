import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalTitleSceneMobile } from "./PortalTitleSceneMobile";

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

describe("PortalTitleSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the title split across two lines for mobile", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const titles = container.querySelectorAll("h1");
    // Mobile version splits title into two h1 elements
    expect(titles.length).toBe(2);
  });

  it("renders titles with mobile-optimized text size (text-5xl)", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const titles = container.querySelectorAll("h1");
    titles.forEach((title) => {
      expect(title).toHaveClass("text-5xl");
      expect(title).toHaveClass("font-bold");
      expect(title).toHaveClass("text-white");
    });
  });

  it("renders the subtitle with correct styling", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Das Herzstück der deutschsprachigen Bitcoin-Community");
    expect(subtitle).toHaveClass("text-xl");
    expect(subtitle).toHaveClass("text-zinc-300");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBeGreaterThanOrEqual(2);
  });

  it("includes typing audio", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const typingAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("typing.mp3")
    );
    expect(typingAudio).toBeInTheDocument();
  });

  it("includes ui-appear audio", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const uiAppearAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("ui-appear.mp3")
    );
    expect(uiAppearAudio).toBeInTheDocument();
  });

  it("renders wallpaper background", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders decorative line element", () => {
    const { container } = render(<PortalTitleSceneMobile />);
    const decorativeLine = container.querySelector('[class*="bg-gradient-to-r"]');
    expect(decorativeLine).toBeInTheDocument();
  });
});
