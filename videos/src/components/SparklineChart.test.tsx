import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render } from "@testing-library/react";
import { SparklineChart } from "./SparklineChart";

// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 60), // Midway through animation
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1920, height: 1080 })),
  interpolate: vi.fn((value, inputRange, outputRange, options) => {
    // Simple linear interpolation mock
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
  spring: vi.fn(() => 1), // Return 1 for fully animated state
  Easing: {
    out: vi.fn((fn) => fn),
    cubic: vi.fn((t: number) => t),
  },
}));

describe("SparklineChart", () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    expect(container).toBeInTheDocument();
  });

  it("renders an SVG element", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const svg = container.querySelector("svg");
    expect(svg).toBeInTheDocument();
  });

  it("renders with default dimensions", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const svg = container.querySelector("svg");
    expect(svg).toHaveAttribute("width", "100");
    expect(svg).toHaveAttribute("height", "30");
  });

  it("renders with custom dimensions", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} width={200} height={50} />
    );
    const svg = container.querySelector("svg");
    expect(svg).toHaveAttribute("width", "200");
    expect(svg).toHaveAttribute("height", "50");
  });

  it("renders a path element for the line", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const paths = container.querySelectorAll("path");
    // At least one path for the main line
    expect(paths.length).toBeGreaterThanOrEqual(1);
  });

  it("applies custom stroke color", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} color="#00ff00" />
    );
    const path = container.querySelector('path[stroke="#00ff00"]');
    expect(path).toBeInTheDocument();
  });

  it("applies custom stroke width", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} strokeWidth={4} />
    );
    const path = container.querySelector('path[stroke-width="4"]');
    expect(path).toBeInTheDocument();
  });

  it("returns null for empty data", () => {
    const { container } = render(<SparklineChart data={[]} />);
    const svg = container.querySelector("svg");
    expect(svg).toBeNull();
  });

  it("handles single data point", () => {
    const { container } = render(<SparklineChart data={[42]} />);
    const svg = container.querySelector("svg");
    expect(svg).toBeInTheDocument();
    const path = container.querySelector("path");
    expect(path).toBeInTheDocument();
  });

  it("renders fill gradient when showFill is true", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} showFill />
    );
    const linearGradient = container.querySelector("linearGradient");
    expect(linearGradient).toBeInTheDocument();
  });

  it("renders glow filter when showGlow is true (default)", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const filter = container.querySelector("filter");
    expect(filter).toBeInTheDocument();
  });

  it("does not render glow filter when showGlow is false", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} showGlow={false} />
    );
    const filter = container.querySelector("filter");
    expect(filter).toBeNull();
  });

  it("uses stroke-dasharray for animation", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const path = container.querySelector('path[fill="none"]');
    expect(path).toHaveAttribute("stroke-dasharray");
    expect(path).toHaveAttribute("stroke-dashoffset");
  });

  it("uses default Bitcoin orange color", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const path = container.querySelector('path[stroke="#f7931a"]');
    expect(path).toBeInTheDocument();
  });

  it("renders with correct viewBox", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} width={150} height={40} />
    );
    const svg = container.querySelector("svg");
    expect(svg).toHaveAttribute("viewBox", "0 0 150 40");
  });

  it("handles constant data (all same values)", () => {
    const { container } = render(<SparklineChart data={[5, 5, 5, 5, 5]} />);
    const svg = container.querySelector("svg");
    expect(svg).toBeInTheDocument();
    const path = container.querySelector("path");
    expect(path).toBeInTheDocument();
  });

  it("handles negative values", () => {
    const { container } = render(<SparklineChart data={[-5, -2, 0, 3, 5]} />);
    const svg = container.querySelector("svg");
    expect(svg).toBeInTheDocument();
  });

  it("renders two paths when showFill is enabled", () => {
    const { container } = render(
      <SparklineChart data={[1, 2, 3, 4, 5]} showFill />
    );
    const paths = container.querySelectorAll("path");
    expect(paths.length).toBe(2); // One for fill, one for line
  });

  it("sets stroke-linecap to round", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const path = container.querySelector('path[stroke-linecap="round"]');
    expect(path).toBeInTheDocument();
  });

  it("sets stroke-linejoin to round", () => {
    const { container } = render(<SparklineChart data={[1, 2, 3, 4, 5]} />);
    const path = container.querySelector('path[stroke-linejoin="round"]');
    expect(path).toBeInTheDocument();
  });
});
