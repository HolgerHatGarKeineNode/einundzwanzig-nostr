import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { CallToActionScene } from "./CallToActionScene";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 120),
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1920, height: 1080 })),
  interpolate: vi.fn((value, inputRange, outputRange) => {
    const [inMin, inMax] = inputRange;
    const [outMin, outMax] = outputRange;
    let progress = (value - inMin) / (inMax - inMin);
    progress = Math.max(0, Math.min(1, progress));
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

describe("CallToActionScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<CallToActionScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<CallToActionScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<CallToActionScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the main title 'Werde Teil der Community'", () => {
    const { container } = render(<CallToActionScene />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveTextContent("Werde Teil der Community");
    expect(title).toHaveClass("text-5xl");
    expect(title).toHaveClass("font-bold");
    expect(title).toHaveClass("text-white");
  });

  it("renders the EINUNDZWANZIG logo", () => {
    const { container } = render(<CallToActionScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const logo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-square-inverted.svg")
    );
    expect(logo).toBeInTheDocument();
  });

  it("renders the portal URL", () => {
    const { container } = render(<CallToActionScene />);
    // At frame 120, URL should be partially or fully typed
    const urlContainer = container.querySelector(".font-mono");
    expect(urlContainer).toBeInTheDocument();
  });

  it("renders the subtitle text", () => {
    const { container } = render(<CallToActionScene />);
    expect(container.textContent).toContain("Die Bitcoin-Community wartet auf dich");
  });

  it("includes typing audio for URL animation", () => {
    const { container } = render(<CallToActionScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const typingAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("typing.mp3")
    );
    expect(typingAudio).toBeInTheDocument();
  });

  it("includes url-emphasis audio", () => {
    const { container } = render(<CallToActionScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const urlEmphasisAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("url-emphasis.mp3")
    );
    expect(urlEmphasisAudio).toBeInTheDocument();
  });

  it("includes logo-reveal audio", () => {
    const { container } = render(<CallToActionScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const logoRevealAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("logo-reveal.mp3")
    );
    expect(logoRevealAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<CallToActionScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("renders glassmorphism overlay container", () => {
    const { container } = render(<CallToActionScene />);
    // Check for the rounded card with glassmorphism styling
    const glassCard = container.querySelector(".rounded-3xl");
    expect(glassCard).toBeInTheDocument();
  });

  it("renders gradient overlay for background effect", () => {
    const { container } = render(<CallToActionScene />);
    const gradientElements = container.querySelectorAll('[style*="radial-gradient"]');
    expect(gradientElements.length).toBeGreaterThan(0);
  });

  it("renders the URL container with correct styling classes", () => {
    const { container } = render(<CallToActionScene />);
    const urlContainer = container.querySelector(".rounded-xl.inline-block");
    expect(urlContainer).toBeInTheDocument();
  });

  it("applies blur filter to background", () => {
    const { container } = render(<CallToActionScene />);
    const blurredElements = container.querySelectorAll('[style*="blur"]');
    expect(blurredElements.length).toBeGreaterThan(0);
  });
});

describe("CallToActionScene URL display", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders URL container with font-mono class", () => {
    const { container } = render(<CallToActionScene />);
    const urlContainer = container.querySelector(".font-mono");
    expect(urlContainer).toBeInTheDocument();
  });

  it("URL container is positioned within the glassmorphism card", () => {
    const { container } = render(<CallToActionScene />);
    const glassCard = container.querySelector(".rounded-3xl");
    expect(glassCard).toBeInTheDocument();
    const urlContainer = glassCard?.querySelector(".font-mono");
    expect(urlContainer).toBeInTheDocument();
  });

  it("renders the correct portal URL text", () => {
    const { container } = render(<CallToActionScene />);
    // At frame 120 (default mock), URL should contain portal.einundzwanzig.space
    expect(container.textContent).toContain("portal.einundzwanzig.space");
  });
});

describe("CallToActionScene logo glow animation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders logo with drop-shadow glow effect", () => {
    const { container } = render(<CallToActionScene />);
    const glowElements = container.querySelectorAll('[style*="drop-shadow"]');
    expect(glowElements.length).toBeGreaterThan(0);
  });

  it("renders glow ring around the logo", () => {
    const { container } = render(<CallToActionScene />);
    const glowRings = container.querySelectorAll(".rounded-full");
    expect(glowRings.length).toBeGreaterThan(0);
  });
});
