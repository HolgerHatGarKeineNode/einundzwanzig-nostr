import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { MeetupShowcaseSceneMobile } from "./MeetupShowcaseSceneMobile";

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

// Mock MeetupCard component
vi.mock("../../../components/MeetupCard", () => ({
  MeetupCard: vi.fn(({ name, width }) => (
    <div data-testid="meetup-card" data-name={name} data-width={width}>
      {name}
    </div>
  )),
}));

describe("MeetupShowcaseSceneMobile", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
  });

  it("renders the header with mobile-optimized size", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Meine nächsten Meetups");
    // Mobile uses text-3xl vs desktop text-5xl
    expect(header).toHaveClass("text-3xl");
  });

  it("renders the featured MeetupCard with mobile-optimized width", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const meetupCard = container.querySelector('[data-testid="meetup-card"]');
    expect(meetupCard).toBeInTheDocument();
    // Mobile uses 360px width vs desktop 450px
    expect(meetupCard).toHaveAttribute("data-width", "360");
  });

  it("renders three meetup items total (1 featured + 2 upcoming)", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const meetupLogos = container.querySelectorAll('[data-testid="remotion-img"]');
    // 3 meetup logos + 1 wallpaper = 4 images total
    // But MeetupCard is mocked, so we count the upcoming meetup logos
    expect(meetupLogos.length).toBeGreaterThanOrEqual(3);
  });

  it("renders slide-in audio for section entrance", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("renders card-slide audio for featured card", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const cardSlideAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("card-slide.mp3")
    );
    expect(cardSlideAudio).toBeInTheDocument();
  });

  it("renders badge-appear audio for list items", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const badgeAppearAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("badge-appear.mp3")
    );
    expect(badgeAppearAudio).toBeInTheDocument();
  });

  it("renders wallpaper background", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders 'Weitere Termine' section header", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const sectionHeader = container.querySelector("h3");
    expect(sectionHeader).toBeInTheDocument();
    expect(sectionHeader).toHaveTextContent("Weitere Termine");
  });

  it("renders 'Nächster Termin' badge", () => {
    const { container } = render(<MeetupShowcaseSceneMobile />);
    const badge = container.textContent;
    expect(badge).toContain("Nächster Termin");
  });
});
