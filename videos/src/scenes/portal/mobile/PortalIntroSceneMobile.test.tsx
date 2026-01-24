import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalIntroSceneMobile } from "./PortalIntroSceneMobile";

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

// Mock AnimatedLogo component
vi.mock("../../../components/AnimatedLogo", () => ({
  AnimatedLogo: vi.fn(({ size, delay }) => (
    <div data-testid="animated-logo" data-size={size} data-delay={delay}>
      AnimatedLogo
    </div>
  )),
}));

// Mock BitcoinEffect component
vi.mock("../../../components/BitcoinEffect", () => ({
  BitcoinEffect: vi.fn(() => (
    <div data-testid="bitcoin-effect">BitcoinEffect</div>
  )),
}));

describe("PortalIntroSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the AnimatedLogo with mobile-optimized size (280px)", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const logo = container.querySelector('[data-testid="animated-logo"]');
    expect(logo).toBeInTheDocument();
    // Mobile uses 280px vs desktop 350px
    expect(logo).toHaveAttribute("data-size", "280");
    // delay is 0.5 * fps = 0.5 * 30 = 15 frames
    expect(logo).toHaveAttribute("data-delay", "15");
  });

  it("renders the BitcoinEffect component", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const bitcoinEffect = container.querySelector('[data-testid="bitcoin-effect"]');
    expect(bitcoinEffect).toBeInTheDocument();
  });

  it("renders the EINUNDZWANZIG title with mobile-appropriate sizing", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveTextContent("EINUNDZWANZIG");
    // Mobile uses text-5xl vs desktop text-6xl
    expect(title).toHaveClass("text-5xl");
    expect(title).toHaveClass("font-bold");
    expect(title).toHaveClass("text-white");
  });

  it("renders the subtitle with orange color", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Das Portal");
    expect(subtitle).toHaveClass("text-orange-500");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBeGreaterThanOrEqual(2);
  });

  it("includes logo-reveal audio", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const revealAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("logo-reveal.mp3")
    );
    expect(revealAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<PortalIntroSceneMobile />);
    const vignette = container.querySelector('[class*="pointer-events-none"]');
    expect(vignette).toBeInTheDocument();
  });
});
