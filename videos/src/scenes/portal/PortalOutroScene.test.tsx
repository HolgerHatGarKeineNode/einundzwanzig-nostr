import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalOutroScene } from "./PortalOutroScene";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 90),
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

// Mock BitcoinEffect component
vi.mock("../../components/BitcoinEffect", () => ({
  BitcoinEffect: vi.fn(() => (
    <div data-testid="bitcoin-effect">BitcoinEffect</div>
  )),
}));

describe("PortalOutroScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalOutroScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<PortalOutroScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<PortalOutroScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the horizontal EINUNDZWANZIG logo", () => {
    const { container } = render(<PortalOutroScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const logo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-horizontal-inverted.svg")
    );
    expect(logo).toBeInTheDocument();
  });

  it("renders the BitcoinEffect component", () => {
    const { container } = render(<PortalOutroScene />);
    const bitcoinEffect = container.querySelector('[data-testid="bitcoin-effect"]');
    expect(bitcoinEffect).toBeInTheDocument();
  });

  it("renders the EINUNDZWANZIG title text", () => {
    const { container } = render(<PortalOutroScene />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    expect(title).toHaveTextContent("EINUNDZWANZIG");
    expect(title).toHaveClass("text-5xl");
    expect(title).toHaveClass("font-bold");
    expect(title).toHaveClass("text-white");
    expect(title).toHaveClass("tracking-widest");
  });

  it("renders the subtitle with orange color", () => {
    const { container } = render(<PortalOutroScene />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveTextContent("Die deutschsprachige Bitcoin-Community");
    expect(subtitle).toHaveClass("text-orange-500");
  });

  it("renders audio sequence for final-chime sound effect", () => {
    const { container } = render(<PortalOutroScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    // final-chime = 1 sequence
    expect(sequences.length).toBe(1);
  });

  it("includes final-chime audio", () => {
    const { container } = render(<PortalOutroScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const chimeAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("final-chime.mp3")
    );
    expect(chimeAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<PortalOutroScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("renders glow effect element with blur filter", () => {
    const { container } = render(<PortalOutroScene />);
    const elements = container.querySelectorAll('[style*="blur(60px)"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders gradient overlay for background effect", () => {
    const { container } = render(<PortalOutroScene />);
    const gradientElements = container.querySelectorAll('[style*="radial-gradient"]');
    expect(gradientElements.length).toBeGreaterThan(0);
  });

  it("renders logo with drop-shadow glow effect", () => {
    const { container } = render(<PortalOutroScene />);
    const glowElements = container.querySelectorAll('[style*="drop-shadow"]');
    expect(glowElements.length).toBeGreaterThan(0);
  });

  it("renders ambient glow at bottom", () => {
    const { container } = render(<PortalOutroScene />);
    const ambientGlow = container.querySelector(".h-64.pointer-events-none");
    expect(ambientGlow).toBeInTheDocument();
  });
});

describe("PortalOutroScene logo display", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders horizontal logo with correct width", () => {
    const { container } = render(<PortalOutroScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const logo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-horizontal-inverted.svg")
    );
    expect(logo).toBeInTheDocument();
    expect(logo).toHaveStyle({ width: "600px" });
  });

  it("logo is centered in the container", () => {
    const { container } = render(<PortalOutroScene />);
    const centerContainer = container.querySelector(".flex.flex-col.items-center.justify-center");
    expect(centerContainer).toBeInTheDocument();
  });
});

describe("PortalOutroScene text styling", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("title has text shadow for glow effect", () => {
    const { container } = render(<PortalOutroScene />);
    const title = container.querySelector("h1");
    expect(title).toBeInTheDocument();
    const style = title?.getAttribute("style");
    expect(style).toContain("text-shadow");
  });

  it("subtitle has tracking-wide class", () => {
    const { container } = render(<PortalOutroScene />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveClass("tracking-wide");
  });

  it("subtitle has font-medium class", () => {
    const { container } = render(<PortalOutroScene />);
    const subtitle = container.querySelector("p");
    expect(subtitle).toBeInTheDocument();
    expect(subtitle).toHaveClass("font-medium");
  });
});

describe("PortalOutroScene audio configuration", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("final-chime sequence starts at logo delay (1 second / 30 frames)", () => {
    const { container } = render(<PortalOutroScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const chimeSequence = Array.from(sequences).find((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("final-chime.mp3");
    });
    expect(chimeSequence).toBeInTheDocument();
    expect(chimeSequence).toHaveAttribute("data-from", "30");
  });

  it("final-chime has correct duration (3 seconds / 90 frames)", () => {
    const { container } = render(<PortalOutroScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const chimeSequence = Array.from(sequences).find((seq) => {
      const audio = seq.querySelector('[data-testid="audio"]');
      return audio?.getAttribute("src")?.includes("final-chime.mp3");
    });
    expect(chimeSequence).toBeInTheDocument();
    expect(chimeSequence).toHaveAttribute("data-duration", "90");
  });
});
