/* eslint-disable @remotion/warn-native-media-tag */
import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { MeetupCard } from "./MeetupCard";

// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 30),
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1920, height: 1080 })),
  interpolate: vi.fn((value, inputRange, outputRange) => {
    const [inMin, inMax] = inputRange;
    const [outMin, outMax] = outputRange;
    const progress = Math.max(0, Math.min(1, (value - inMin) / (inMax - inMin)));
    return outMin + progress * (outMax - outMin);
  }),
  spring: vi.fn(() => 1),
  Img: vi.fn(({ src, style }) => (
    <img src={src} style={style} data-testid="meetup-logo" alt="meetup logo" />
  )),
}));

describe("MeetupCard", () => {
  const defaultProps = {
    logoSrc: "/test-logo.png",
    name: "Bitcoin Stammtisch Berlin",
    location: "Berlin, Germany",
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    expect(container).toBeInTheDocument();
  });

  it("displays the meetup name", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const nameElement = container.querySelector(".font-bold.text-white");
    expect(nameElement).toHaveTextContent("Bitcoin Stammtisch Berlin");
  });

  it("displays the location", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const locationElement = container.querySelector(".text-zinc-400");
    expect(locationElement).toHaveTextContent("Berlin, Germany");
  });

  it("renders the logo image with correct src", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const logo = container.querySelector('[data-testid="meetup-logo"]');
    expect(logo).toHaveAttribute("src", "/test-logo.png");
  });

  it("displays a custom meetup name", () => {
    const { container } = render(
      <MeetupCard
        {...defaultProps}
        name="Einundzwanzig München"
      />
    );
    const nameElement = container.querySelector(".font-bold.text-white");
    expect(nameElement).toHaveTextContent("Einundzwanzig München");
  });

  it("displays a custom location", () => {
    const { container } = render(
      <MeetupCard
        {...defaultProps}
        location="München, Bavaria"
      />
    );
    const locationElement = container.querySelector(".text-zinc-400");
    expect(locationElement).toHaveTextContent("München, Bavaria");
  });

  it("applies custom width style", () => {
    const { container } = render(
      <MeetupCard {...defaultProps} width={500} />
    );
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveStyle({ width: "500px" });
  });

  it("renders location pin SVG icon", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const svg = container.querySelector("svg");
    expect(svg).toBeInTheDocument();
  });

  it("applies default accent color to location icon", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const svg = container.querySelector("svg");
    expect(svg).toHaveAttribute("stroke", "#f7931a");
  });

  it("applies custom accent color to location icon", () => {
    const { container } = render(
      <MeetupCard {...defaultProps} accentColor="#00ff00" />
    );
    const svg = container.querySelector("svg");
    expect(svg).toHaveAttribute("stroke", "#00ff00");
  });

  it("has rounded corners styling", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("rounded-2xl");
  });

  it("has proper card background styling", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("bg-zinc-900/90");
  });

  it("name has truncate class for overflow handling", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const nameElement = container.querySelector(".font-bold.text-white");
    expect(nameElement).toHaveClass("truncate");
  });

  it("location text has truncate class for overflow handling", () => {
    const { container } = render(<MeetupCard {...defaultProps} />);
    const locationElement = container.querySelector(".text-zinc-400");
    expect(locationElement).toHaveClass("truncate");
  });
});
