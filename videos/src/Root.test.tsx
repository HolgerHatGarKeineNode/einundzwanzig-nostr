import { describe, it, expect, vi } from "vitest";
import { render } from "@testing-library/react";
import { RemotionRoot } from "./Root";

// Mock Remotion components
vi.mock("remotion", () => ({
  Composition: vi.fn(({ id, durationInFrames, fps, width, height }) => (
    <div
      data-testid={`composition-${id}`}
      data-duration={durationInFrames}
      data-fps={fps}
      data-width={width}
      data-height={height}
    >
      {id}
    </div>
  )),
  Folder: vi.fn(({ name, children }) => (
    <div data-testid={`folder-${name}`}>{children}</div>
  )),
}));

// Mock composition components
vi.mock("./Composition", () => ({
  MyComposition: vi.fn(() => <div>MyComposition</div>),
}));

vi.mock("./Nip05Tutorial", () => ({
  Nip05Tutorial: vi.fn(() => <div>Nip05Tutorial</div>),
}));

vi.mock("./Nip05TutorialMobile", () => ({
  Nip05TutorialMobile: vi.fn(() => <div>Nip05TutorialMobile</div>),
}));

vi.mock("./PortalPresentation", () => ({
  PortalPresentation: vi.fn(() => <div>PortalPresentation</div>),
}));

vi.mock("./PortalPresentationMobile", () => ({
  PortalPresentationMobile: vi.fn(() => <div>PortalPresentationMobile</div>),
}));

describe("RemotionRoot", () => {
  it("renders without errors", () => {
    const { container } = render(<RemotionRoot />);
    expect(container).toBeInTheDocument();
  });

  it("renders Portal folder", () => {
    const { container } = render(<RemotionRoot />);
    const portalFolder = container.querySelector('[data-testid="folder-Portal"]');
    expect(portalFolder).toBeInTheDocument();
  });

  it("renders PortalPresentation composition in Portal folder", () => {
    const { container } = render(<RemotionRoot />);
    const portalFolder = container.querySelector('[data-testid="folder-Portal"]');
    const composition = portalFolder?.querySelector(
      '[data-testid="composition-PortalPresentation"]'
    );
    expect(composition).toBeInTheDocument();
    expect(composition?.getAttribute("data-width")).toBe("1920");
    expect(composition?.getAttribute("data-height")).toBe("1080");
    expect(composition?.getAttribute("data-fps")).toBe("30");
    expect(composition?.getAttribute("data-duration")).toBe("2700"); // 90 * 30
  });

  it("renders PortalPresentationMobile composition in Portal folder", () => {
    const { container } = render(<RemotionRoot />);
    const portalFolder = container.querySelector('[data-testid="folder-Portal"]');
    const composition = portalFolder?.querySelector(
      '[data-testid="composition-PortalPresentationMobile"]'
    );
    expect(composition).toBeInTheDocument();
    expect(composition?.getAttribute("data-width")).toBe("1080");
    expect(composition?.getAttribute("data-height")).toBe("1920");
    expect(composition?.getAttribute("data-fps")).toBe("30");
    expect(composition?.getAttribute("data-duration")).toBe("2700"); // 90 * 30
  });

  it("renders NIP-05-Tutorial folder", () => {
    const { container } = render(<RemotionRoot />);
    const folder = container.querySelector('[data-testid="folder-NIP-05-Tutorial"]');
    expect(folder).toBeInTheDocument();
  });

  it("renders Nip05Tutorial desktop composition", () => {
    const { container } = render(<RemotionRoot />);
    const composition = container.querySelector('[data-testid="composition-Nip05Tutorial"]');
    expect(composition).toBeInTheDocument();
    expect(composition?.getAttribute("data-width")).toBe("1920");
    expect(composition?.getAttribute("data-height")).toBe("1080");
  });

  it("renders Nip05TutorialMobile composition", () => {
    const { container } = render(<RemotionRoot />);
    const composition = container.querySelector('[data-testid="composition-Nip05TutorialMobile"]');
    expect(composition).toBeInTheDocument();
    expect(composition?.getAttribute("data-width")).toBe("1080");
    expect(composition?.getAttribute("data-height")).toBe("1920");
  });

  it("renders MyComp composition", () => {
    const { container } = render(<RemotionRoot />);
    const composition = container.querySelector('[data-testid="composition-MyComp"]');
    expect(composition).toBeInTheDocument();
  });
});
