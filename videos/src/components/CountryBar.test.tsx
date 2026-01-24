import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { CountryBar } from "./CountryBar";

// Mock Remotion hooks
vi.mock("remotion", () => ({
  useCurrentFrame: vi.fn(() => 60),
  useVideoConfig: vi.fn(() => ({ fps: 30, width: 1920, height: 1080 })),
  interpolate: vi.fn((value, inputRange, outputRange) => {
    const [inMin, inMax] = inputRange;
    const [outMin, outMax] = outputRange;
    const progress = Math.max(0, Math.min(1, (value - inMin) / (inMax - inMin)));
    return outMin + progress * (outMax - outMin);
  }),
  spring: vi.fn(() => 1),
}));

describe("CountryBar", () => {
  const defaultProps = {
    countryName: "Germany",
    flagEmoji: "🇩🇪",
    userCount: 458,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    expect(container).toBeInTheDocument();
  });

  it("displays the country name", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const nameElement = container.querySelector(".font-semibold.text-white");
    expect(nameElement).toHaveTextContent("Germany");
  });

  it("displays the flag emoji", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    expect(container.textContent).toContain("🇩🇪");
  });

  it("displays the user count by default", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toBeInTheDocument();
    expect(countElement).toHaveTextContent("458");
  });

  it("hides user count when showCount is false", () => {
    const { container } = render(
      <CountryBar {...defaultProps} showCount={false} />
    );
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).not.toBeInTheDocument();
  });

  it("displays a custom country name", () => {
    const { container } = render(
      <CountryBar {...defaultProps} countryName="Austria" />
    );
    const nameElement = container.querySelector(".font-semibold.text-white");
    expect(nameElement).toHaveTextContent("Austria");
  });

  it("displays a custom flag emoji", () => {
    const { container } = render(
      <CountryBar {...defaultProps} flagEmoji="🇦🇹" />
    );
    expect(container.textContent).toContain("🇦🇹");
  });

  it("displays a custom user count", () => {
    const { container } = render(
      <CountryBar {...defaultProps} userCount={59} />
    );
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toHaveTextContent("59");
  });

  it("applies custom width style", () => {
    const { container } = render(
      <CountryBar {...defaultProps} width={600} />
    );
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveStyle({ width: "600px" });
  });

  it("applies default width of 500px", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveStyle({ width: "500px" });
  });

  it("applies default accent color to user count", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toHaveStyle({ color: "#f7931a" });
  });

  it("applies custom accent color to user count", () => {
    const { container } = render(
      <CountryBar {...defaultProps} accentColor="#00ff00" />
    );
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toHaveStyle({ color: "#00ff00" });
  });

  it("has rounded corners styling", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("rounded-xl");
  });

  it("has proper card background styling", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("bg-zinc-900/90");
  });

  it("has backdrop blur styling", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("backdrop-blur-sm");
  });

  it("has border styling", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const card = container.firstChild as HTMLElement;
    expect(card).toHaveClass("border");
    expect(card).toHaveClass("border-zinc-700/50");
  });

  it("renders the progress bar container", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const barContainer = container.querySelector(".bg-zinc-800\\/80");
    expect(barContainer).toBeInTheDocument();
  });

  it("renders the fill bar inside the container", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const fillBar = container.querySelector(".absolute.inset-y-0.left-0");
    expect(fillBar).toBeInTheDocument();
  });

  it("country name has truncate class for overflow handling", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const nameElement = container.querySelector(".font-semibold.text-white");
    expect(nameElement).toHaveClass("truncate");
  });

  it("uses tabular-nums class for consistent number width", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toBeInTheDocument();
  });

  it("uses monospace font family for user count", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toHaveStyle({ fontFamily: "Inconsolata, monospace" });
  });

  it("renders correctly with zero user count", () => {
    const { container } = render(
      <CountryBar {...defaultProps} userCount={0} />
    );
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toHaveTextContent("0");
  });

  it("renders correctly with large user count", () => {
    const { container } = render(
      <CountryBar {...defaultProps} userCount={10000} />
    );
    const countElement = container.querySelector(".tabular-nums");
    expect(countElement).toHaveTextContent("10000");
  });

  it("renders bar container with rounded-full class", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const barContainer = container.querySelector(".bg-zinc-800\\/80");
    expect(barContainer).toHaveClass("rounded-full");
  });

  it("renders fill bar with rounded-full class", () => {
    const { container } = render(<CountryBar {...defaultProps} />);
    const fillBar = container.querySelector(".absolute.inset-y-0.left-0");
    expect(fillBar).toHaveClass("rounded-full");
  });

  it("accepts maxCount prop for calculating bar width", () => {
    const { container } = render(
      <CountryBar {...defaultProps} userCount={229} maxCount={458} />
    );
    // Component should render without errors with maxCount
    expect(container).toBeInTheDocument();
  });

  it("renders correctly with Switzerland data", () => {
    const { container } = render(
      <CountryBar
        countryName="Switzerland"
        flagEmoji="🇨🇭"
        userCount={34}
      />
    );
    expect(container.textContent).toContain("Switzerland");
    expect(container.textContent).toContain("🇨🇭");
    expect(container.textContent).toContain("34");
  });

  it("renders correctly with Luxembourg data", () => {
    const { container } = render(
      <CountryBar
        countryName="Luxembourg"
        flagEmoji="🇱🇺"
        userCount={8}
      />
    );
    expect(container.textContent).toContain("Luxembourg");
    expect(container.textContent).toContain("🇱🇺");
    expect(container.textContent).toContain("8");
  });

  it("renders correctly with Bulgaria data", () => {
    const { container } = render(
      <CountryBar
        countryName="Bulgaria"
        flagEmoji="🇧🇬"
        userCount={7}
      />
    );
    expect(container.textContent).toContain("Bulgaria");
    expect(container.textContent).toContain("🇧🇬");
    expect(container.textContent).toContain("7");
  });

  it("renders correctly with Spain data", () => {
    const { container } = render(
      <CountryBar
        countryName="Spain"
        flagEmoji="🇪🇸"
        userCount={3}
      />
    );
    expect(container.textContent).toContain("Spain");
    expect(container.textContent).toContain("🇪🇸");
    expect(container.textContent).toContain("3");
  });
});
