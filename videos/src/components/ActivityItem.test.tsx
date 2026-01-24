import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { ActivityItem } from "./ActivityItem";

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
}));

describe("ActivityItem", () => {
  const defaultProps = {
    eventName: "EINUNDZWANZIG Kempten",
    timestamp: "vor 13 Stunden",
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    expect(container).toBeInTheDocument();
  });

  it("displays the event name", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const nameElement = container.querySelector(".font-bold.text-white");
    expect(nameElement).toHaveTextContent("EINUNDZWANZIG Kempten");
  });

  it("displays a custom event name", () => {
    const { container } = render(
      <ActivityItem
        {...defaultProps}
        eventName="BitcoinWalk Würzburg"
      />
    );
    const nameElement = container.querySelector(".font-bold.text-white");
    expect(nameElement).toHaveTextContent("BitcoinWalk Würzburg");
  });

  it("displays the timestamp", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const timestampElement = container.querySelector(".timestamp-text");
    expect(timestampElement).toHaveTextContent("vor 13 Stunden");
  });

  it("displays a custom timestamp", () => {
    const { container } = render(
      <ActivityItem
        {...defaultProps}
        timestamp="vor 2 Tagen"
      />
    );
    const timestampElement = container.querySelector(".timestamp-text");
    expect(timestampElement).toHaveTextContent("vor 2 Tagen");
  });

  it("displays the badge by default", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const badge = container.querySelector(".rounded-full.font-semibold");
    expect(badge).toBeInTheDocument();
    expect(badge).toHaveTextContent("Neuer Termin");
  });

  it("displays custom badge text", () => {
    const { container } = render(
      <ActivityItem
        {...defaultProps}
        badgeText="Neues Meetup"
      />
    );
    const badge = container.querySelector(".rounded-full.font-semibold");
    expect(badge).toHaveTextContent("Neues Meetup");
  });

  it("hides badge when showBadge is false", () => {
    const { container } = render(
      <ActivityItem {...defaultProps} showBadge={false} />
    );
    const badge = container.querySelector(".rounded-full.font-semibold");
    expect(badge).not.toBeInTheDocument();
  });

  it("applies custom width style", () => {
    const { container } = render(
      <ActivityItem {...defaultProps} width={500} />
    );
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveStyle({ width: "500px" });
  });

  it("applies default width style", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveStyle({ width: "400px" });
  });

  it("renders clock SVG icon for timestamp", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const svg = container.querySelector(".text-zinc-400 svg");
    expect(svg).toBeInTheDocument();
  });

  it("has rounded corners styling", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("rounded-xl");
  });

  it("has proper card background styling", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("bg-zinc-900/90");
  });

  it("has backdrop blur styling", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("backdrop-blur-sm");
  });

  it("has border styling", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("border");
    expect(card).toHaveClass("border-zinc-700/50");
  });

  it("event name has truncate class for overflow handling", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const nameElement = container.querySelector(".font-bold.text-white");
    expect(nameElement).toHaveClass("truncate");
  });

  it("applies default accent color to badge background", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const badge = container.querySelector(".rounded-full.font-semibold") as HTMLElement;
    expect(badge).toHaveStyle({ backgroundColor: "#f7931a" });
  });

  it("applies custom accent color to badge background", () => {
    const { container } = render(
      <ActivityItem {...defaultProps} accentColor="#00ff00" />
    );
    const badge = container.querySelector(".rounded-full.font-semibold") as HTMLElement;
    expect(badge).toHaveStyle({ backgroundColor: "#00ff00" });
  });

  it("timestamp uses monospace font family", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const timestampContainer = container.querySelector(".text-zinc-400") as HTMLElement;
    expect(timestampContainer).toHaveStyle({ fontFamily: "Inconsolata, monospace" });
  });

  it("uses flex column layout", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("flex");
    expect(card).toHaveClass("flex-col");
  });

  it("badge has self-start alignment", () => {
    const { container } = render(<ActivityItem {...defaultProps} />);
    const badge = container.querySelector(".rounded-full.font-semibold");
    expect(badge).toHaveClass("self-start");
  });
});
