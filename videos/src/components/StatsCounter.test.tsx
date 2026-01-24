import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, screen } from "@testing-library/react";
import { StatsCounter } from "./StatsCounter";

// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 0),
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1920, height: 1080 })),
  interpolate: vi.fn((value, inputRange, outputRange) => {
    // Simple linear interpolation mock
    const [inMin, inMax] = inputRange;
    const [outMin, outMax] = outputRange;
    const progress = Math.max(0, Math.min(1, (value - inMin) / (inMax - inMin)));
    return outMin + progress * (outMax - outMin);
  }),
  spring: vi.fn(() => 1), // Return 1 for fully animated state
  Easing: {
    out: vi.fn((fn) => fn),
    cubic: vi.fn((t: number) => t),
  },
}));

describe("StatsCounter", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<StatsCounter />);
    expect(container).toBeInTheDocument();
  });

  it("displays the default target number (204)", () => {
    const { container } = render(<StatsCounter />);
    const numberElement = container.querySelector(".tabular-nums");
    expect(numberElement).toHaveTextContent("204");
  });

  it("displays a custom target number", () => {
    render(<StatsCounter targetNumber={500} />);
    expect(screen.getByText("500")).toBeInTheDocument();
  });

  it("displays a label when provided", () => {
    render(<StatsCounter label="Active Members" />);
    expect(screen.getByText("Active Members")).toBeInTheDocument();
  });

  it("displays prefix when provided", () => {
    render(<StatsCounter targetNumber={100} prefix="$" />);
    expect(screen.getByText("$100")).toBeInTheDocument();
  });

  it("displays suffix when provided", () => {
    render(<StatsCounter targetNumber={100} suffix="%" />);
    expect(screen.getByText("100%")).toBeInTheDocument();
  });

  it("displays prefix and suffix together", () => {
    render(<StatsCounter targetNumber={50} prefix="~" suffix="+" />);
    expect(screen.getByText("~50+")).toBeInTheDocument();
  });

  it("displays decimal places when specified", () => {
    render(<StatsCounter targetNumber={3.14159} decimals={2} />);
    expect(screen.getByText("3.14")).toBeInTheDocument();
  });

  it("applies custom color style", () => {
    const { container } = render(<StatsCounter color="#00ff00" />);
    const numberElement = container.querySelector(".tabular-nums");
    expect(numberElement).toHaveStyle({ color: "#00ff00" });
  });

  it("applies custom font size", () => {
    const { container } = render(<StatsCounter fontSize={200} />);
    const numberElement = container.querySelector(".tabular-nums");
    expect(numberElement).toHaveStyle({ fontSize: "200px" });
  });

  it("does not render label when not provided", () => {
    const { container } = render(<StatsCounter />);
    const labels = container.querySelectorAll(".text-zinc-300");
    expect(labels.length).toBe(0);
  });

  it("uses tabular-nums class for consistent number width", () => {
    const { container } = render(<StatsCounter />);
    const numberElement = container.querySelector(".tabular-nums");
    expect(numberElement).toBeInTheDocument();
  });
});
