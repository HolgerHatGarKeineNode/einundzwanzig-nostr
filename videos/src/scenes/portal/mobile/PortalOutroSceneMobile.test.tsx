import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalOutroSceneMobile } from "./PortalOutroSceneMobile";

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

// Mock BitcoinEffect component
vi.mock("../../../components/BitcoinEffect", () => ({
  BitcoinEffect: vi.fn(() => (
    <div data-testid="bitcoin-effect">BitcoinEffect</div>
  )),
}));

describe("PortalOutroSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the horizontal logo with mobile-optimized width", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const logo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-horizontal-inverted.svg")
    );
    expect(logo).toBeInTheDocument();
    // Mobile uses 450px width vs desktop 600px
    expect(logo).toHaveStyle({ width: "450px" });
  });

  it("renders EINUNDZWANZIG text with mobile-optimized size", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveTextContent("EINUNDZWANZIG");
    // Mobile uses text-4xl vs desktop text-5xl
    expect(title).toHaveClass("text-4xl");
  });

  it("renders subtitle with mobile-optimized size", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Die deutschsprachige Bitcoin-Community");
    // Mobile uses text-xl vs desktop text-2xl
    expect(subtitle).toHaveClass("text-xl");
  });

  it("renders BitcoinEffect component", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const bitcoinEffect = container.querySelector('[data-testid="bitcoin-effect"]');
    expect(bitcoinEffect).toBeInTheDocument();
  });

  it("renders final-chime audio", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const finalChimeAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("final-chime.mp3")
    );
    expect(finalChimeAudio).toBeInTheDocument();
  });

  it("renders wallpaper background", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const vignette = container.querySelector('[class*="pointer-events-none"]');
    expect(vignette).toBeInTheDocument();
  });

  it("renders glow effect element with mobile-optimized dimensions", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    // Look for the glow element with blur filter
    const elements = container.querySelectorAll('[style*="blur(50px)"]');
    expect(elements.length).toBeGreaterThan(0);
  });
});
