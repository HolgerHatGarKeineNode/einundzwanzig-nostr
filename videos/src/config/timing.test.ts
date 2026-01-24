import { describe, it, expect } from "vitest";
import {
  SPRING_CONFIGS,
  STAGGER_DELAYS,
  TIMING,
  GLOW_CONFIG,
  SCENE_DURATIONS,
  secondsToFrames,
  getStaggeredDelay,
} from "./timing";

describe("Timing Configuration", () => {
  describe("SPRING_CONFIGS", () => {
    it("exports all required spring configurations", () => {
      expect(SPRING_CONFIGS.SMOOTH).toBeDefined();
      expect(SPRING_CONFIGS.SNAPPY).toBeDefined();
      expect(SPRING_CONFIGS.BOUNCY).toBeDefined();
      expect(SPRING_CONFIGS.PERSPECTIVE).toBeDefined();
      expect(SPRING_CONFIGS.FEATURED).toBeDefined();
      expect(SPRING_CONFIGS.LOGO).toBeDefined();
      expect(SPRING_CONFIGS.BADGE).toBeDefined();
      expect(SPRING_CONFIGS.BUTTON).toBeDefined();
      expect(SPRING_CONFIGS.COUNTER).toBeDefined();
      expect(SPRING_CONFIGS.ROW).toBeDefined();
    });

    it("SMOOTH has high damping for slow animations", () => {
      expect(SPRING_CONFIGS.SMOOTH.damping).toBe(200);
    });

    it("SNAPPY has moderate damping and stiffness for responsive UI", () => {
      expect(SPRING_CONFIGS.SNAPPY.damping).toBe(15);
      expect(SPRING_CONFIGS.SNAPPY.stiffness).toBe(80);
    });

    it("BOUNCY has low damping for playful animations", () => {
      expect(SPRING_CONFIGS.BOUNCY.damping).toBe(12);
    });

    it("PERSPECTIVE has appropriate values for 3D entrances", () => {
      expect(SPRING_CONFIGS.PERSPECTIVE.damping).toBe(20);
      expect(SPRING_CONFIGS.PERSPECTIVE.stiffness).toBe(60);
    });

    it("BADGE has high stiffness for quick bounces", () => {
      expect(SPRING_CONFIGS.BADGE.damping).toBe(8);
      expect(SPRING_CONFIGS.BADGE.stiffness).toBe(150);
    });
  });

  describe("STAGGER_DELAYS", () => {
    it("exports all required stagger delay values", () => {
      expect(STAGGER_DELAYS.CARD).toBeDefined();
      expect(STAGGER_DELAYS.LIST_ITEM).toBeDefined();
      expect(STAGGER_DELAYS.COUNTRY).toBeDefined();
      expect(STAGGER_DELAYS.MEETUP_RANK).toBeDefined();
      expect(STAGGER_DELAYS.ACTIVITY).toBeDefined();
      expect(STAGGER_DELAYS.SIDEBAR).toBeDefined();
      expect(STAGGER_DELAYS.QUICK_STAT).toBeDefined();
    });

    it("CARD has optimal stagger delay for card animations", () => {
      // 5 frames = ~167ms at 30fps, good for card cascade
      expect(STAGGER_DELAYS.CARD).toBe(5);
    });

    it("ACTIVITY has longer stagger for feed items", () => {
      // 20 frames = ~667ms at 30fps, appropriate for activity feed
      expect(STAGGER_DELAYS.ACTIVITY).toBe(20);
    });

    it("SIDEBAR has shortest stagger for quick menu items", () => {
      // 3 frames = ~100ms at 30fps, snappy sidebar reveal
      expect(STAGGER_DELAYS.SIDEBAR).toBe(3);
    });

    it("stagger delays are ordered by intended speed", () => {
      expect(STAGGER_DELAYS.SIDEBAR).toBeLessThan(STAGGER_DELAYS.CARD);
      expect(STAGGER_DELAYS.CARD).toBeLessThan(STAGGER_DELAYS.LIST_ITEM);
      expect(STAGGER_DELAYS.LIST_ITEM).toBeLessThan(STAGGER_DELAYS.ACTIVITY);
    });
  });

  describe("TIMING", () => {
    it("exports all required timing values", () => {
      expect(TIMING.PERSPECTIVE_ENTRANCE).toBeDefined();
      expect(TIMING.HEADER_DELAY).toBeDefined();
      expect(TIMING.CONTENT_BASE_DELAY).toBeDefined();
      expect(TIMING.LOGO_ENTRANCE_DELAY).toBeDefined();
      expect(TIMING.TITLE_DELAY).toBeDefined();
      expect(TIMING.SUBTITLE_DELAY).toBeDefined();
      expect(TIMING.CHAR_FRAMES).toBeDefined();
      expect(TIMING.CURSOR_BLINK_FRAMES).toBeDefined();
    });

    it("header appears after perspective entrance starts", () => {
      expect(TIMING.HEADER_DELAY).toBeGreaterThan(TIMING.PERSPECTIVE_ENTRANCE);
    });

    it("content appears after header", () => {
      expect(TIMING.CONTENT_BASE_DELAY).toBeGreaterThan(TIMING.HEADER_DELAY);
    });

    it("title appears after logo entrance", () => {
      expect(TIMING.TITLE_DELAY).toBeGreaterThan(TIMING.LOGO_ENTRANCE_DELAY);
    });

    it("subtitle appears after title", () => {
      expect(TIMING.SUBTITLE_DELAY).toBeGreaterThan(TIMING.TITLE_DELAY);
    });

    it("typing animation uses 2 frames per character", () => {
      expect(TIMING.CHAR_FRAMES).toBe(2);
    });

    it("cursor blinks at 16 frame intervals", () => {
      expect(TIMING.CURSOR_BLINK_FRAMES).toBe(16);
    });

    it("counter animation has appropriate duration", () => {
      // 60 frames = 2 seconds at 30fps
      expect(TIMING.COUNTER_DURATION).toBe(60);
    });

    it("CTA scene elements appear in correct sequence", () => {
      expect(TIMING.CTA_OVERLAY_DELAY).toBeLessThan(TIMING.CTA_TITLE_DELAY);
      expect(TIMING.CTA_TITLE_DELAY).toBeLessThan(TIMING.CTA_LOGO_DELAY);
      expect(TIMING.CTA_LOGO_DELAY).toBeLessThan(TIMING.CTA_URL_DELAY);
      expect(TIMING.CTA_URL_DELAY).toBeLessThan(TIMING.CTA_SUBTITLE_DELAY);
    });

    it("outro elements appear in correct sequence", () => {
      expect(TIMING.OUTRO_LOGO_DELAY).toBeLessThan(TIMING.OUTRO_TEXT_DELAY);
      expect(TIMING.OUTRO_TEXT_DELAY).toBeLessThan(TIMING.OUTRO_SUBTITLE_DELAY);
    });
  });

  describe("GLOW_CONFIG", () => {
    it("exports intensity ranges", () => {
      expect(GLOW_CONFIG.INTENSITY.SUBTLE).toEqual([0.3, 0.5]);
      expect(GLOW_CONFIG.INTENSITY.NORMAL).toEqual([0.4, 0.8]);
      expect(GLOW_CONFIG.INTENSITY.STRONG).toEqual([0.5, 1.0]);
    });

    it("exports frequency values", () => {
      expect(GLOW_CONFIG.FREQUENCY.SLOW).toBe(0.04);
      expect(GLOW_CONFIG.FREQUENCY.NORMAL).toBe(0.06);
      expect(GLOW_CONFIG.FREQUENCY.FAST).toBe(0.08);
      expect(GLOW_CONFIG.FREQUENCY.PULSE).toBe(0.1);
    });

    it("exports scale ranges", () => {
      expect(GLOW_CONFIG.SCALE.SUBTLE).toEqual([1.0, 1.1]);
      expect(GLOW_CONFIG.SCALE.NORMAL).toEqual([1.0, 1.15]);
      expect(GLOW_CONFIG.SCALE.STRONG).toEqual([1.0, 1.2]);
    });

    it("frequency values increase from slow to pulse", () => {
      expect(GLOW_CONFIG.FREQUENCY.SLOW).toBeLessThan(
        GLOW_CONFIG.FREQUENCY.NORMAL
      );
      expect(GLOW_CONFIG.FREQUENCY.NORMAL).toBeLessThan(
        GLOW_CONFIG.FREQUENCY.FAST
      );
      expect(GLOW_CONFIG.FREQUENCY.FAST).toBeLessThan(
        GLOW_CONFIG.FREQUENCY.PULSE
      );
    });
  });

  describe("SCENE_DURATIONS", () => {
    it("exports all scene durations", () => {
      expect(SCENE_DURATIONS.LOGO_REVEAL).toBe(6);
      expect(SCENE_DURATIONS.PORTAL_TITLE).toBe(4);
      expect(SCENE_DURATIONS.DASHBOARD_OVERVIEW).toBe(12);
      expect(SCENE_DURATIONS.MEINE_MEETUPS).toBe(12);
      expect(SCENE_DURATIONS.TOP_LAENDER).toBe(12);
      expect(SCENE_DURATIONS.TOP_MEETUPS).toBe(10);
      expect(SCENE_DURATIONS.ACTIVITY_FEED).toBe(10);
      expect(SCENE_DURATIONS.CALL_TO_ACTION).toBe(12);
      expect(SCENE_DURATIONS.OUTRO).toBe(30);
    });

    it("total duration equals 108 seconds", () => {
      const totalDuration =
        SCENE_DURATIONS.LOGO_REVEAL +
        SCENE_DURATIONS.PORTAL_TITLE +
        SCENE_DURATIONS.DASHBOARD_OVERVIEW +
        SCENE_DURATIONS.MEINE_MEETUPS +
        SCENE_DURATIONS.TOP_LAENDER +
        SCENE_DURATIONS.TOP_MEETUPS +
        SCENE_DURATIONS.ACTIVITY_FEED +
        SCENE_DURATIONS.CALL_TO_ACTION +
        SCENE_DURATIONS.OUTRO;
      expect(totalDuration).toBe(108);
    });
  });

  describe("secondsToFrames helper", () => {
    it("converts seconds to frames at default 30fps", () => {
      expect(secondsToFrames(1)).toBe(30);
      expect(secondsToFrames(2)).toBe(60);
      expect(secondsToFrames(0.5)).toBe(15);
    });

    it("converts seconds to frames at custom fps", () => {
      expect(secondsToFrames(1, 60)).toBe(60);
      expect(secondsToFrames(2, 24)).toBe(48);
    });

    it("returns integer values (floors decimal results)", () => {
      expect(secondsToFrames(0.3)).toBe(9); // 0.3 * 30 = 9
      expect(secondsToFrames(0.35, 30)).toBe(10); // 0.35 * 30 = 10.5 -> 10
    });

    it("handles zero correctly", () => {
      expect(secondsToFrames(0)).toBe(0);
    });
  });

  describe("getStaggeredDelay helper", () => {
    it("calculates staggered delay for first item", () => {
      expect(getStaggeredDelay(0, 30, 5)).toBe(30);
    });

    it("calculates staggered delay for subsequent items", () => {
      expect(getStaggeredDelay(1, 30, 5)).toBe(35);
      expect(getStaggeredDelay(2, 30, 5)).toBe(40);
      expect(getStaggeredDelay(3, 30, 5)).toBe(45);
    });

    it("works with different base delays", () => {
      expect(getStaggeredDelay(0, 0, 10)).toBe(0);
      expect(getStaggeredDelay(1, 0, 10)).toBe(10);
      expect(getStaggeredDelay(2, 60, 15)).toBe(90);
    });

    it("integrates with STAGGER_DELAYS constants", () => {
      const baseDelay = secondsToFrames(1); // 30 frames
      expect(getStaggeredDelay(0, baseDelay, STAGGER_DELAYS.CARD)).toBe(30);
      expect(getStaggeredDelay(1, baseDelay, STAGGER_DELAYS.CARD)).toBe(35);
      expect(getStaggeredDelay(2, baseDelay, STAGGER_DELAYS.CARD)).toBe(40);
    });
  });
});

describe("Timing Consistency", () => {
  it("timing values are reasonable for 30fps video", () => {
    const fps = 30;

    // Entrance delays should be under 2 seconds
    expect(secondsToFrames(TIMING.HEADER_DELAY, fps)).toBeLessThan(60);
    expect(secondsToFrames(TIMING.CONTENT_BASE_DELAY, fps)).toBeLessThan(60);

    // Intro scene delays should allow content to appear within scene duration
    const introSceneFrames = SCENE_DURATIONS.LOGO_REVEAL * fps;
    expect(secondsToFrames(TIMING.LOGO_ENTRANCE_DELAY, fps)).toBeLessThan(
      introSceneFrames
    );
    expect(secondsToFrames(TIMING.TITLE_DELAY, fps)).toBeLessThan(
      introSceneFrames
    );
    expect(secondsToFrames(TIMING.SUBTITLE_DELAY, fps)).toBeLessThan(
      introSceneFrames
    );
  });

  it("CTA timing fits within CTA scene duration", () => {
    const fps = 30;
    const ctaSceneFrames = SCENE_DURATIONS.CALL_TO_ACTION * fps;

    expect(secondsToFrames(TIMING.CTA_SUBTITLE_DELAY, fps)).toBeLessThan(
      ctaSceneFrames
    );
  });

  it("outro timing fits within outro scene duration", () => {
    const fps = 30;
    const outroSceneFrames = SCENE_DURATIONS.OUTRO * fps;

    expect(secondsToFrames(TIMING.OUTRO_SUBTITLE_DELAY, fps)).toBeLessThan(
      outroSceneFrames - secondsToFrames(TIMING.OUTRO_FADE_DURATION, fps)
    );
  });
});
