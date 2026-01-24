import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { MeetupShowcaseScene } from "./MeetupShowcaseScene";

/* eslint-disable @remotion/warn-native-media-tag */
// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 60),
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

// Mock MeetupCard component
vi.mock("../../components/MeetupCard", () => ({
  MeetupCard: vi.fn(({ logoSrc, name, location, delay, width, accentColor }) => (
    <div
      data-testid="meetup-card"
      data-logo-src={logoSrc}
      data-name={name}
      data-location={location}
      data-delay={delay}
      data-width={width}
      data-accent-color={accentColor}
    >
      {name}
    </div>
  )),
}));

describe("MeetupShowcaseScene", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container).toBeInTheDocument();
  });

  it("renders the AbsoluteFill container with correct classes", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const absoluteFill = container.querySelector('[data-testid="absolute-fill"]');
    expect(absoluteFill).toBeInTheDocument();
    expect(absoluteFill).toHaveClass("bg-zinc-900");
    expect(absoluteFill).toHaveClass("overflow-hidden");
  });

  it("renders the wallpaper background image", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const wallpaper = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("einundzwanzig-wallpaper.png")
    );
    expect(wallpaper).toBeInTheDocument();
  });

  it("renders the section header with correct text", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const header = container.querySelector("h1");
    expect(header).toBeInTheDocument();
    expect(header).toHaveTextContent("Meine nächsten Meetup Termine");
    expect(header).toHaveClass("text-5xl");
    expect(header).toHaveClass("font-bold");
    expect(header).toHaveClass("text-white");
  });

  it("renders the subtitle text", () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("Deine kommenden Bitcoin-Treffen in der Region");
  });

  it("renders the featured MeetupCard for Kempten", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const meetupCard = container.querySelector('[data-testid="meetup-card"]');
    expect(meetupCard).toBeInTheDocument();
    expect(meetupCard).toHaveAttribute("data-name", "EINUNDZWANZIG Kempten");
    expect(meetupCard).toHaveAttribute("data-location", "Kempten im Allgäu");
    expect(meetupCard?.getAttribute("data-logo-src")).toContain("EinundzwanzigKempten.jpg");
  });

  it("renders the MeetupCard with correct width for featured display", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const meetupCard = container.querySelector('[data-testid="meetup-card"]');
    expect(meetupCard).toHaveAttribute("data-width", "450");
  });

  it("renders the date and time for the featured meetup", () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("Di, 28. Jan 2025");
    expect(container.textContent).toContain("19:00 Uhr");
  });

  it('renders the "Nächster Termin" badge', () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("Nächster Termin");
  });

  it('renders the "Weitere Termine" section header', () => {
    const { container } = render(<MeetupShowcaseScene />);
    const sectionHeaders = container.querySelectorAll("h3");
    const furtherHeader = Array.from(sectionHeaders).find(
      (h3) => h3.textContent === "Weitere Termine"
    );
    expect(furtherHeader).toBeInTheDocument();
    expect(furtherHeader).toHaveClass("uppercase");
    expect(furtherHeader).toHaveClass("tracking-wider");
  });

  it("renders upcoming meetups for Memmingen", () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Memmingen");
    expect(container.textContent).toContain("Mi, 29. Jan 2025");
    expect(container.textContent).toContain("19:30 Uhr");
  });

  it("renders upcoming meetups for Friedrichshafen", () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("EINUNDZWANZIG Friedrichshafen");
    expect(container.textContent).toContain("Do, 30. Jan 2025");
    expect(container.textContent).toContain("20:00 Uhr");
  });

  it("renders logos for upcoming meetups", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const images = container.querySelectorAll('[data-testid="remotion-img"]');
    const memmingenLogo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("EinundzwanzigMemmingen.jpg")
    );
    const friedrichshafenLogo = Array.from(images).find((img) =>
      img.getAttribute("src")?.includes("EinundzwanzigFriedrichshafen.png")
    );
    expect(memmingenLogo).toBeInTheDocument();
    expect(friedrichshafenLogo).toBeInTheDocument();
  });

  it('renders the "Zum Kalender hinzufügen" action button', () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("Zum Kalender hinzufügen");
  });

  it('renders the "Alle Meetups anzeigen" action button', () => {
    const { container } = render(<MeetupShowcaseScene />);
    expect(container.textContent).toContain("Alle Meetups anzeigen");
  });

  it("renders audio sequences for sound effects", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const sequences = container.querySelectorAll('[data-testid="sequence"]');
    expect(sequences.length).toBeGreaterThanOrEqual(3);
  });

  it("includes slide-in audio", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const slideInAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("slide-in.mp3")
    );
    expect(slideInAudio).toBeInTheDocument();
  });

  it("includes card-slide audio", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const cardSlideAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("card-slide.mp3")
    );
    expect(cardSlideAudio).toBeInTheDocument();
  });

  it("includes badge-appear audio", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const audioElements = container.querySelectorAll('[data-testid="audio"]');
    const badgeAudio = Array.from(audioElements).find((audio) =>
      audio.getAttribute("src")?.includes("badge-appear.mp3")
    );
    expect(badgeAudio).toBeInTheDocument();
  });

  it("renders vignette overlay with pointer-events-none", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const vignettes = container.querySelectorAll(".pointer-events-none");
    expect(vignettes.length).toBeGreaterThan(0);
  });

  it("applies 3D perspective transform styles", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const elements = container.querySelectorAll('[style*="perspective"]');
    expect(elements.length).toBeGreaterThan(0);
  });

  it("renders SVG icons for calendar and clock", () => {
    const { container } = render(<MeetupShowcaseScene />);
    const svgElements = container.querySelectorAll("svg");
    // Should have calendar icon, clock icon, calendar-plus icon, and list icon (at least 4)
    expect(svgElements.length).toBeGreaterThanOrEqual(4);
  });

  it("renders the primary action button with Bitcoin orange styling", () => {
    const { container } = render(<MeetupShowcaseScene />);
    // Look for element containing "Zum Kalender hinzufügen" which should be the primary button
    const buttons = Array.from(container.querySelectorAll("div")).filter(
      (el) => el.textContent?.includes("Zum Kalender hinzufügen")
    );
    expect(buttons.length).toBeGreaterThan(0);
    // Verify at least one element has the orange background color
    const hasOrangeButton = buttons.some((el) =>
      el.getAttribute("style")?.includes("rgb(247, 147, 26)")
    );
    expect(hasOrangeButton).toBe(true);
  });
});
