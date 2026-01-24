import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalOutroSceneMobile } from "./PortalOutroSceneMobile";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 750), // Frame 25 seconds in (after logo appears at 24s)
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
    inOut: vi.fn((fn) => fn),
  },
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

// Mock LogoMatrix3DMobile component
vi.mock("../../../components/LogoMatrix3DMobile", () => ({
  LogoMatrix3DMobile: vi.fn(() => (
    <div data-testid="logo-matrix-3d-mobile">LogoMatrix3DMobile</div>
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
    // Mobile uses 500px width vs desktop 700px
    expect(logo).toHaveStyle({ width: "500px" });
  });

  it("renders EINUNDZWANZIG text with mobile-optimized size", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveTextContent("EINUNDZWANZIG");
    // Mobile uses text-5xl vs desktop text-6xl
    expect(title).toHaveClass("text-5xl");
  });

  it("renders subtitle with mobile-optimized size", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const subtitle = container.querySelector("p.text-orange-400");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Die Bitcoin-Community");
    // Mobile uses text-2xl vs desktop text-3xl
    expect(subtitle).toHaveClass("text-2xl");
  });

  it("renders community count badge", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const badge = container.querySelector("span.text-orange-300");
    expect(badge).toBeInTheDocument();
    expect(badge).toHaveTextContent("230+ Meetups weltweit");
  });

  it("renders BitcoinEffect component", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const bitcoinEffect = container.querySelector('[data-testid="bitcoin-effect"]');
    expect(bitcoinEffect).toBeInTheDocument();
  });

  it("renders LogoMatrix3DMobile component", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const logoMatrix = container.querySelector('[data-testid="logo-matrix-3d-mobile"]');
    expect(logoMatrix).toBeInTheDocument();
  });

  it("renders final-chime audio", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const finalChimeAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("final-chime.mp3")
    );
    expect(finalChimeAudio).toBeInTheDocument();
  });

  it("renders logo-whoosh audio", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const whooshAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("logo-whoosh.mp3")
    );
    expect(whooshAudio).toBeInTheDocument();
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

  it("renders glow effect element with blur filter", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    // Look for the glow elements with blur filter
    const elements = container.querySelectorAll('[style*="blur"]');
    expect(elements.length).toBeGreaterThan(0);
  });
});

describe("PortalOutroSceneMobile audio configuration", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders two audio sequences (whoosh + chime)", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBe(2);
  });

  it("logo-whoosh sequence starts at 4 seconds (120 frames)", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const whooshSequence = Array.from(sequences).find((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("logo-whoosh.mp3");
    });
    expect(whooshSequence).toBeInTheDocument();
    expect(whooshSequence).toHaveAttribute("data-from", "120");
  });

  it("final-chime sequence starts at logo entrance (24 seconds / 720 frames)", () => {
    const { container } = render(<PortalOutroSceneMobile />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const chimeSequence = Array.from(sequences).find((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("final-chime.mp3");
    });
    expect(chimeSequence).toBeInTheDocument();
    expect(chimeSequence).toHaveAttribute("data-from", "720");
  });
});
