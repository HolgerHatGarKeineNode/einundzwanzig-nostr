import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalAudioManager } from "./PortalAudioManager";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks with dynamic frame value
let mockCurrentFrame = 45;

vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => mockCurrentFrame),
  useVideoConfig: vi.fn(() => ({
    fps: 30,
    width: 1920,
    height: 1080,
    durationInFrames: 3240, // 108 seconds at 30fps
  })),
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
  staticFile: vi.fn((path: string) => `/static/${path}`),
}));

// Mock @remotion/media
vi.mock("@remotion/media", () => ({
  Audio: vi.fn(({ src, volume, loop }) => (
    <audio
      data-testid="audio"
      src={src}
      data-volume={volume}
      data-loop={loop ? "true" : "false"}
    />
  )),
}));
/* eslint-enable @remotion/warn-native-media-tag */

describe("PortalAudioManager", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockCurrentFrame = 45; // Reset to mid-video frame
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalAudioManager />);
    expect(container).toBeInTheDocument();
  });

  it("renders the background music audio element", () => {
    const { container } = render(<PortalAudioManager />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    expect(audioElements.length).toBe(1);
  });

  it("uses the correct background music file", () => {
    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    expect(audioElement?.getAttribute("src")).toBe(
      "/static/music/background-music.mp3"
    );
  });

  it("sets the background music to loop", () => {
    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    expect(audioElement?.getAttribute("data-loop")).toBe("true");
  });
});

describe("PortalAudioManager volume behavior", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockCurrentFrame = 45;
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders audio with volume attribute", () => {
    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = audioElement?.getAttribute("data-volume");
    expect(volume).toBeDefined();
    expect(parseFloat(volume as string)).toBeGreaterThanOrEqual(0);
    expect(parseFloat(volume as string)).toBeLessThanOrEqual(1);
  });
});

describe("PortalAudioManager fade-in behavior", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("starts with zero volume at frame 0", () => {
    mockCurrentFrame = 0;

    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = parseFloat(
      audioElement?.getAttribute("data-volume") as string
    );
    // At frame 0, volume should be 0 (start of fade-in)
    expect(volume).toBe(0);
  });

  it("has base volume after fade-in completes", () => {
    // Frame 45 is after fade-in (1 second = 30 frames)
    mockCurrentFrame = 45;

    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = parseFloat(
      audioElement?.getAttribute("data-volume") as string
    );
    // After fade-in, volume should be at base level (0.25)
    expect(volume).toBeCloseTo(0.25, 2);
  });

  it("has partial volume during fade-in", () => {
    // Frame 15 is midway through fade-in (0.5 seconds into 1 second fade)
    mockCurrentFrame = 15;

    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = parseFloat(
      audioElement?.getAttribute("data-volume") as string
    );
    // Midway through fade-in, volume should be around 0.125 (half of 0.25)
    expect(volume).toBeCloseTo(0.125, 2);
  });
});

describe("PortalAudioManager fade-out behavior", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("maintains base volume before fade-out starts", () => {
    // Frame 2600 is before fade-out (starts at 3240 - 90 = 3150)
    mockCurrentFrame = 2600;

    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = parseFloat(
      audioElement?.getAttribute("data-volume") as string
    );
    // Before fade-out, volume should be at base level (0.25)
    expect(volume).toBeCloseTo(0.25, 2);
  });

  it("has reduced volume during fade-out", () => {
    // Frame 3195 is midway through fade-out (3150 + 45)
    mockCurrentFrame = 3195;

    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = parseFloat(
      audioElement?.getAttribute("data-volume") as string
    );
    // During fade-out, volume should be less than base
    expect(volume).toBeLessThan(0.25);
    expect(volume).toBeGreaterThan(0);
  });

  it("reaches zero volume at the final frame", () => {
    mockCurrentFrame = 3240;

    const { container } = render(<PortalAudioManager />);
    const audioElement = container.querySelector('[data-testid="audio"]');
    expect(audioElement).toBeInTheDocument();
    const volume = parseFloat(
      audioElement?.getAttribute("data-volume") as string
    );
    // At the last frame, volume should be 0
    expect(volume).toBe(0);
  });
});

describe("PortalAudioManager integration", () => {
  beforeEach(() => {
    vi.clearAllMocks();
    mockCurrentFrame = 45;
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("only renders one audio element for background music", () => {
    const { container } = render(<PortalAudioManager />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    expect(audioElements.length).toBe(1);
  });

  it("does not render any sequence elements (SFX handled by individual scenes)", () => {
    const { container } = render(<PortalAudioManager />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBe(0);
  });
});
