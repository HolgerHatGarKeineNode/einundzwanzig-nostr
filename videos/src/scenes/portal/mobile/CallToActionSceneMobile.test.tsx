import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { CallToActionSceneMobile } from "./CallToActionSceneMobile";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 120),
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

describe("CallToActionSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<CallToActionSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the CTA title with mobile-optimized size", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveTextContent("Werde Teil der Community");
    // Mobile uses text-3xl vs desktop text-5xl
    expect(title).toHaveClass("text-3xl");
  });

  it("renders the Einundzwanzig logo with mobile-optimized dimensions", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const logo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-square-inverted.svg")
    );
    expect(logo).toBeInTheDocument();
    // Mobile logo is 100px vs desktop 120px
    expect(logo).toHaveStyle({ width: "100px", height: "100px" });
  });

  it("renders the portal URL", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const urlText = container.textContent;
    expect(urlText).toContain("portal.einundzwanzig.space");
  });

  it("renders URL container with mobile-optimized text size", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const urlSpan = container.querySelector('[class*="font-mono"]');
    expect(urlSpan).toBeInTheDocument();
    // Mobile uses text-xl vs desktop text-3xl
    expect(urlSpan).toHaveClass("text-xl");
  });

  it("renders glassmorphism overlay with mobile-optimized width", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const overlay = container.querySelector('[class*="max-w-md"]');
    expect(overlay).toBeInTheDocument();
  });

  it("renders typing audio for URL", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const typingAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("typing.mp3")
    );
    expect(typingAudio).toBeInTheDocument();
  });

  it("renders subtitle with correct styling", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Die Bitcoin-Community wartet auf dich");
    // Mobile uses text-base vs desktop text-xl
    expect(subtitle).toHaveClass("text-base");
  });

  it("renders wallpaper background", () => {
    const { container } = render(<CallToActionSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });
});
