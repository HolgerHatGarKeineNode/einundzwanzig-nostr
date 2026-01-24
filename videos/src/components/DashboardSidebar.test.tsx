import { describe, it, expect, vi, beforeEach, afterEach } from "vitest";
import { render, cleanup } from "@testing-library/react";
import { DashboardSidebar, SidebarNavItem } from "./DashboardSidebar";

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
  Img: vi.fn(({ src, style }: { src: string; style?: React.CSSProperties }) => (
    // eslint-disable-next-line @remotion/warn-native-media-tag
    <img src={src} style={style} alt="logo" data-testid="sidebar-logo" />
  )),
}));

describe("DashboardSidebar", () => {
  const defaultNavItems: SidebarNavItem[] = [
    { label: "Dashboard", icon: "dashboard", isActive: true },
    { label: "Meetups", icon: "meetups", badgeCount: 204 },
    { label: "Users", icon: "users", badgeCount: 587 },
    { label: "Events", icon: "events" },
  ];

  const defaultProps = {
    logoSrc: "/einundzwanzig-horizontal-inverted.svg",
    navItems: defaultNavItems,
  };

  beforeEach(() => {
    vi.clearAllMocks();
  });

  afterEach(() => {
    cleanup();
    vi.resetAllMocks();
  });

  it("renders without errors", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    expect(container).toBeInTheDocument();
  });

  it("renders the logo image", () => {
    const { getByTestId } = render(<DashboardSidebar {...defaultProps} />);
    const logo = getByTestId("sidebar-logo");
    expect(logo).toBeInTheDocument();
    expect(logo).toHaveAttribute("src", "/einundzwanzig-horizontal-inverted.svg");
  });

  it("renders all navigation items", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);

    expect(container.textContent).toContain("Dashboard");
    expect(container.textContent).toContain("Meetups");
    expect(container.textContent).toContain("Users");
    expect(container.textContent).toContain("Events");
  });

  it("displays badge counts for items with badges", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);

    expect(container.textContent).toContain("204");
    expect(container.textContent).toContain("587");
  });

  it("applies active styling to active items", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const activeItem = container.querySelector(".bg-zinc-800\\/80");
    expect(activeItem).toBeInTheDocument();
  });

  it("renders icons for nav items with icons", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const svgIcons = container.querySelectorAll("svg");
    // Should have at least 4 icons for the 4 nav items
    expect(svgIcons.length).toBeGreaterThanOrEqual(4);
  });

  it("applies custom width", () => {
    const { container } = render(
      <DashboardSidebar {...defaultProps} width={320} />
    );
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveStyle({ width: "320px" });
  });

  it("applies default width of 280px", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveStyle({ width: "280px" });
  });

  it("applies custom height", () => {
    const { container } = render(
      <DashboardSidebar {...defaultProps} height={900} />
    );
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveStyle({ height: "900px" });
  });

  it("applies default height of 1080px", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveStyle({ height: "1080px" });
  });

  it("renders section headers correctly", () => {
    const navItemsWithSection: SidebarNavItem[] = [
      { label: "Dashboard", icon: "dashboard" },
      { label: "Einstellungen", isSection: true },
      { label: "Settings", icon: "settings" },
    ];

    const { container } = render(
      <DashboardSidebar
        logoSrc="/logo.svg"
        navItems={navItemsWithSection}
      />
    );

    const sectionHeader = container.querySelector(".uppercase.tracking-wider");
    expect(sectionHeader).toBeInTheDocument();
    expect(sectionHeader).toHaveTextContent("Einstellungen");
  });

  it("renders nested items with indentation", () => {
    const navItemsWithIndent: SidebarNavItem[] = [
      { label: "Settings", icon: "settings" },
      { label: "Language", icon: "language", indentLevel: 1 },
    ];

    const { container } = render(
      <DashboardSidebar
        logoSrc="/logo.svg"
        navItems={navItemsWithIndent}
      />
    );

    expect(container.textContent).toContain("Settings");
    expect(container.textContent).toContain("Language");
  });

  it("has flex column layout", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveClass("flex");
    expect(sidebar).toHaveClass("flex-col");
  });

  it("has proper background styling", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveClass("bg-zinc-900/95");
  });

  it("has backdrop blur styling", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveClass("backdrop-blur-md");
  });

  it("has border-right styling", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const sidebar = container.firstChild as HTMLElement;
    expect(sidebar).toHaveClass("border-r");
    expect(sidebar).toHaveClass("border-zinc-700/50");
  });

  it("applies custom accent color to badges", () => {
    const { container } = render(
      <DashboardSidebar {...defaultProps} accentColor="#00ff00" />
    );
    const badge = container.querySelector(".rounded-full.font-bold");
    expect(badge).toHaveStyle({ backgroundColor: "#00ff00" });
  });

  it("applies default Bitcoin orange accent color to badges", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const badge = container.querySelector(".rounded-full.font-bold");
    expect(badge).toHaveStyle({ backgroundColor: "#f7931a" });
  });

  it("renders empty sidebar with no nav items", () => {
    const { container } = render(
      <DashboardSidebar logoSrc="/logo.svg" navItems={[]} />
    );
    expect(container).toBeInTheDocument();
    // Should still have logo
    const logo = container.querySelector('[data-testid="sidebar-logo"]');
    expect(logo).toBeInTheDocument();
  });

  it("truncates long navigation labels", () => {
    const navItemsWithLongLabel: SidebarNavItem[] = [
      { label: "This is a very long navigation item label that should be truncated", icon: "dashboard" },
    ];

    const { container } = render(
      <DashboardSidebar
        logoSrc="/logo.svg"
        navItems={navItemsWithLongLabel}
      />
    );

    const label = container.querySelector(".truncate");
    expect(label).toBeInTheDocument();
  });

  it("renders badges with monospace font", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const badge = container.querySelector(".rounded-full.font-bold.tabular-nums");
    expect(badge).toHaveStyle({ fontFamily: "Inconsolata, monospace" });
  });

  it("renders items without icons correctly", () => {
    const navItemsNoIcons: SidebarNavItem[] = [
      { label: "Item without icon" },
      { label: "Another item without icon", badgeCount: 5 },
    ];

    const { container } = render(
      <DashboardSidebar
        logoSrc="/logo.svg"
        navItems={navItemsNoIcons}
      />
    );

    expect(container.textContent).toContain("Item without icon");
    expect(container.textContent).toContain("Another item without icon");
    expect(container.textContent).toContain("5");
  });

  it("applies active border color correctly", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const activeItem = container.querySelector(".bg-zinc-800\\/80") as HTMLElement;
    expect(activeItem).toHaveStyle({ borderLeft: "3px solid #f7931a" });
  });

  it("renders logo section with border bottom", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const logoSection = container.querySelector(".border-b.border-zinc-700\\/50");
    expect(logoSection).toBeInTheDocument();
  });

  it("has overflow hidden on navigation container", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const navContainer = container.querySelector(".flex-1.overflow-hidden");
    expect(navContainer).toBeInTheDocument();
  });

  it("renders footer gradient overlay", () => {
    const { container } = render(<DashboardSidebar {...defaultProps} />);
    const gradient = container.querySelector(".pointer-events-none");
    expect(gradient).toBeInTheDocument();
  });

  it("renders multiple icons with correct names", () => {
    const navItemsAllIcons: SidebarNavItem[] = [
      { label: "Dashboard", icon: "dashboard" },
      { label: "Nostr", icon: "nostr" },
      { label: "Meetups", icon: "meetups" },
      { label: "Users", icon: "users" },
      { label: "Events", icon: "events" },
      { label: "Settings", icon: "settings" },
      { label: "Language", icon: "language" },
    ];

    const { container } = render(
      <DashboardSidebar
        logoSrc="/logo.svg"
        navItems={navItemsAllIcons}
      />
    );

    // Should render all items
    navItemsAllIcons.forEach(item => {
      expect(container.textContent).toContain(item.label);
    });

    // Should have SVG icons
    const svgIcons = container.querySelectorAll("svg");
    expect(svgIcons.length).toBeGreaterThanOrEqual(9);
  });

  it("renders fallback icon for unknown icon names", () => {
    const navItemsUnknownIcon: SidebarNavItem[] = [
      { label: "Unknown", icon: "unknown-icon-name" },
    ];

    const { container } = render(
      <DashboardSidebar
        logoSrc="/logo.svg"
        navItems={navItemsUnknownIcon}
      />
    );

    // Should still render without crashing
    expect(container.textContent).toContain("Unknown");
    const svgIcon = container.querySelector("svg");
    expect(svgIcon).toBeInTheDocument();
  });
});
