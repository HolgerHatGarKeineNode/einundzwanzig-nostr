import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { PortalPresentation } from "./PortalPresentation";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 60),
  useVideoConfig: vi.fn(() => ({
    fps: 30,
    width: 1920,
    height: 1080,
    durationInFrames: 2700,
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
  Sequence: vi.fn(({ children, from, durationInFrames, premountFor }) => (
    <div
      data-testid="sequence"
      data-from={from}
      data-duration={durationInFrames}
      data-premount={premountFor}
    >
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

// Mock all scene components
vi.mock("./scenes/portal/PortalIntroScene", () => ({
  PortalIntroScene: vi.fn(() => (
    <div data-testid="portal-intro-scene">PortalIntroScene</div>
  )),
}));

vi.mock("./scenes/portal/PortalTitleScene", () => ({
  PortalTitleScene: vi.fn(() => (
    <div data-testid="portal-title-scene">PortalTitleScene</div>
  )),
}));

vi.mock("./scenes/portal/DashboardOverviewScene", () => ({
  DashboardOverviewScene: vi.fn(() => (
    <div data-testid="dashboard-overview-scene">DashboardOverviewScene</div>
  )),
}));

vi.mock("./scenes/portal/MeetupShowcaseScene", () => ({
  MeetupShowcaseScene: vi.fn(() => (
    <div data-testid="meetup-showcase-scene">MeetupShowcaseScene</div>
  )),
}));

vi.mock("./scenes/portal/CountryStatsScene", () => ({
  CountryStatsScene: vi.fn(() => (
    <div data-testid="country-stats-scene">CountryStatsScene</div>
  )),
}));

vi.mock("./scenes/portal/TopMeetupsScene", () => ({
  TopMeetupsScene: vi.fn(() => (
    <div data-testid="top-meetups-scene">TopMeetupsScene</div>
  )),
}));

vi.mock("./scenes/portal/ActivityFeedScene", () => ({
  ActivityFeedScene: vi.fn(() => (
    <div data-testid="activity-feed-scene">ActivityFeedScene</div>
  )),
}));

vi.mock("./scenes/portal/CallToActionScene", () => ({
  CallToActionScene: vi.fn(() => (
    <div data-testid="call-to-action-scene">CallToActionScene</div>
  )),
}));

vi.mock("./scenes/portal/PortalOutroScene", () => ({
  PortalOutroScene: vi.fn(() => (
    <div data-testid="portal-outro-scene">PortalOutroScene</div>
  )),
}));

/* eslint-disable @remotion/warn-native-media-tag, @remotion/no-string-assets */
// Mock PortalAudioManager to verify it's rendered
vi.mock("./components/PortalAudioManager", () => ({
  PortalAudioManager: vi.fn(() => (
    <div data-testid="portal-audio-manager">
      <audio
        data-testid="background-music"
        src="/static/music/background-music.mp3"
        data-volume="0.25"
        data-loop="true"
      />
    </div>
  )),
}));
/* eslint-enable @remotion/warn-native-media-tag, @remotion/no-string-assets */

// Mock fonts
vi.mock("./fonts/inconsolata", () => ({
  inconsolataFont: "Inconsolata, monospace",
}));

describe("PortalPresentation", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<PortalPresentation />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<PortalPresentation />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-gradient-to-br");
    expect(absoluteFill).toHaveClass("from-zinc-900");
    expect(absoluteFill).toHaveClass("to-zinc-800");
  });

  it("renders the PortalAudioManager for background music", () => {
    const { container } = render(<PortalPresentation />);
    const audioManager = container.querySelector('[data-testid="portal-audio-manager"]');
    expect(audioManager).toBeInTheDocument();
  });

  it("renders background music audio element within PortalAudioManager", () => {
    const { container } = render(<PortalPresentation />);
    const backgroundMusic = container.querySelector('[data-testid="background-music"]');
    expect(backgroundMusic).toBeInTheDocument();
    expect(backgroundMusic?.getAttribute("src")).toContain("background-music.mp3");
    expect(backgroundMusic?.getAttribute("data-loop")).toBe("true");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<PortalPresentation />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders all 9 scene sequences", () => {
    const { container } = render(<PortalPresentation />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBe(9);
  });

  it("renders Scene 1: PortalIntroScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="portal-intro-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 2: PortalTitleScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="portal-title-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 3: DashboardOverviewScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="dashboard-overview-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 4: MeetupShowcaseScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="meetup-showcase-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 5: CountryStatsScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="country-stats-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 6: TopMeetupsScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="top-meetups-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 7: ActivityFeedScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="activity-feed-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 8: CallToActionScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="call-to-action-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders Scene 9: PortalOutroScene", () => {
    const { container } = render(<PortalPresentation />);
    const scene = container.querySelector('[data-testid="portal-outro-scene"]');
    expect(scene).toBeInTheDocument();
  });

  it("renders sequences with correct durations totaling 90 seconds", () => {
    const { container } = render(<PortalPresentation />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const durations = Array.from(sequences).map((seq) =>
      parseInt(seq.getAttribute("data-duration") || "0", 10)
    );

    // 90 seconds * 30fps = 2700 frames total
    const totalDuration = durations.reduce((sum, d) => sum + d, 0);
    expect(totalDuration).toBe(2700);
  });

  it("renders Scene 1 (Logo Reveal) with 6 second duration (180 frames)", () => {
    const { container } = render(<PortalPresentation />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const firstSequence = sequences[0];
    expect(firstSequence?.getAttribute("data-duration")).toBe("180");
    expect(firstSequence?.getAttribute("data-from")).toBe("0");
  });

  it("renders Scene 9 (Outro) with 12 second duration (360 frames)", () => {
    const { container } = render(<PortalPresentation />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    const lastSequence = sequences[sequences.length - 1];
    expect(lastSequence?.getAttribute("data-duration")).toBe("360");
  });

  it("applies Inconsolata font family to the composition", () => {
    const { container } = render(<PortalPresentation />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill?.getAttribute("style")).toContain("Inconsolata");
  });
});

describe("PortalPresentation audio integration", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("integrates PortalAudioManager at the composition level", () => {
    const { container } = render(<PortalPresentation />);
    const audioManager = container.querySelector('[data-testid="portal-audio-manager"]');

    // PortalAudioManager should be present as a direct child of AbsoluteFill
    expect(audioManager).toBeInTheDocument();
  });

  it("ensures background music is set to loop", () => {
    const { container } = render(<PortalPresentation />);
    const backgroundMusic = container.querySelector('[data-testid="background-music"]');
    expect(backgroundMusic?.getAttribute("data-loop")).toBe("true");
  });

  it("ensures background music uses correct file path", () => {
    const { container } = render(<PortalPresentation />);
    const backgroundMusic = container.querySelector('[data-testid="background-music"]');
    expect(backgroundMusic?.getAttribute("src")).toBe("/static/music/background-music.mp3");
  });

  it("renders audio manager before scene sequences in DOM order", () => {
    const { container } = render(<PortalPresentation />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    const children = absoluteFill?.children;

    if (children) {
      // Audio manager should be one of the first elements (before scenes)
      const childArray = Array.from(children);
      const audioManagerIndex = childArray.findIndex(
        (el) => el.getAttribute("data-testid") === "portal-audio-manager"
      );
      expect(audioManagerIndex).toBeGreaterThanOrEqual(0);
      expect(audioManagerIndex).toBeLessThan(3); // Should be in first few elements
    }
  });
});

describe("PortalPresentation scene timing", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("sequences scenes in correct order with proper timing", () => {
    const { container } = render(<PortalPresentation />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');

    // Expected scene timings (at 30fps):
    // Scene 1: 0-180 (6s)
    // Scene 2: 180-300 (4s)
    // Scene 3: 300-660 (12s)
    // Scene 4: 660-1020 (12s)
    // Scene 5: 1020-1380 (12s)
    // Scene 6: 1380-1680 (10s)
    // Scene 7: 1680-1980 (10s)
    // Scene 8: 1980-2340 (12s)
    // Scene 9: 2340-2700 (12s)

    const expectedFromValues = [0, 180, 300, 660, 1020, 1380, 1680, 1980, 2340];

    sequences.forEach((seq, index) => {
      const fromValue = parseInt(seq.getAttribute("data-from") || "0", 10);
      expect(fromValue).toBe(expectedFromValues[index]);
    });
  });

  it("all sequences have premountFor set to 1 second (30 frames)", () => {
    const { container } = render(<PortalPresentation />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');

    sequences.forEach((seq) => {
      const premount = seq.getAttribute("data-premount");
      expect(premount).toBe("30");
    });
  });
});
