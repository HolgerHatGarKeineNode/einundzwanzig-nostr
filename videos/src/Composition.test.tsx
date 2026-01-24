import { describe, it, expect } from "vitest";
import { render } from "@testing-library/react";
import { MyComposition } from "./Composition";

describe("MyComposition", () => {
  it("renders without errors", () => {
    const { container } = render(<MyComposition />);
    expect(container).toBeInTheDocument();
  });

  it("returns null (empty composition)", () => {
    const { container } = render(<MyComposition />);
    expect(container.firstChild).toBeNull();
  });
});
