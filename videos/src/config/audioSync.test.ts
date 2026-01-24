import { describe, it, expect } from "vitest";
import {
  STANDARD_FPS,
  TOTAL_DURATION_FRAMES,
  TOTAL_DURATION_SECONDS,
  BACKGROUND_MUSIC_CONFIG,
  SCENE_START_FRAMES,
  SCENE_AUDIO_CONFIGS,
  LOGO_REVEAL_AUDIO,
  PORTAL_TITLE_AUDIO,
  DASHBOARD_OVERVIEW_AUDIO,
  MEETUP_SHOWCASE_AUDIO,
  COUNTRY_STATS_AUDIO,
  TOP_MEETUPS_AUDIO,
  ACTIVITY_FEED_AUDIO,
  CALL_TO_ACTION_AUDIO,
  OUTRO_AUDIO,
  calculateSceneStartFrames,
  getSceneAudioEvents,
  getAbsoluteAudioFrame,
  getAllAudioEventsAbsolute,
  calculateBackgroundMusicVolume,
  isAudioEventInSync,
  getAudioTimingDeviation,
  frameDeviationToMs,
  type AudioEvent,
} from "./audioSync";
import { SCENE_DURATIONS, secondsToFrames } from "./timing";

describe("Audio Sync Configuration Constants", () => {
  it("defines correct standard FPS", () => {
    expect(STANDARD_FPS).toBe(30);
  });

  it("defines correct total duration in frames", () => {
    expect(TOTAL_DURATION_FRAMES).toBe(3240);
  });

  it("defines correct total duration in seconds", () => {
    expect(TOTAL_DURATION_SECONDS).toBe(108);
  });

  it("duration frames equals seconds times FPS", () => {
    expect(TOTAL_DURATION_FRAMES).toBe(TOTAL_DURATION_SECONDS * STANDARD_FPS);
  });
});

describe("Background Music Configuration", () => {
  it("has correct audio file path", () => {
    expect(BACKGROUND_MUSIC_CONFIG.audioFile).toBe("music/background-music.mp3");
  });

  it("has correct base volume", () => {
    expect(BACKGROUND_MUSIC_CONFIG.baseVolume).toBe(0.25);
  });

  it("has 1 second fade-in duration", () => {
    expect(BACKGROUND_MUSIC_CONFIG.fadeInDuration).toBe(1);
  });

  it("has 3 second fade-out duration", () => {
    expect(BACKGROUND_MUSIC_CONFIG.fadeOutDuration).toBe(3);
  });

  it("is configured to loop", () => {
    expect(BACKGROUND_MUSIC_CONFIG.loop).toBe(true);
  });

  it("fade-in frames equals duration times FPS", () => {
    const fadeInFrames = BACKGROUND_MUSIC_CONFIG.fadeInDuration * STANDARD_FPS;
    expect(fadeInFrames).toBe(30);
  });

  it("fade-out frames equals duration times FPS", () => {
    const fadeOutFrames = BACKGROUND_MUSIC_CONFIG.fadeOutDuration * STANDARD_FPS;
    expect(fadeOutFrames).toBe(90);
  });
});

describe("Scene Start Frames Calculation", () => {
  it("Logo Reveal starts at frame 0", () => {
    expect(SCENE_START_FRAMES.LOGO_REVEAL).toBe(0);
  });

  it("Portal Title starts after Logo Reveal", () => {
    const expectedFrame = SCENE_DURATIONS.LOGO_REVEAL * STANDARD_FPS;
    expect(SCENE_START_FRAMES.PORTAL_TITLE).toBe(expectedFrame);
    expect(SCENE_START_FRAMES.PORTAL_TITLE).toBe(180);
  });

  it("Dashboard Overview starts after Portal Title", () => {
    const expectedFrame =
      (SCENE_DURATIONS.LOGO_REVEAL + SCENE_DURATIONS.PORTAL_TITLE) * STANDARD_FPS;
    expect(SCENE_START_FRAMES.DASHBOARD_OVERVIEW).toBe(expectedFrame);
    expect(SCENE_START_FRAMES.DASHBOARD_OVERVIEW).toBe(300);
  });

  it("Meine Meetups starts at correct frame", () => {
    const expectedFrame =
      (SCENE_DURATIONS.LOGO_REVEAL +
        SCENE_DURATIONS.PORTAL_TITLE +
        SCENE_DURATIONS.DASHBOARD_OVERVIEW) *
      STANDARD_FPS;
    expect(SCENE_START_FRAMES.MEINE_MEETUPS).toBe(expectedFrame);
    expect(SCENE_START_FRAMES.MEINE_MEETUPS).toBe(660);
  });

  it("Top Laender starts at correct frame", () => {
    expect(SCENE_START_FRAMES.TOP_LAENDER).toBe(1020);
  });

  it("Top Meetups starts at correct frame", () => {
    expect(SCENE_START_FRAMES.TOP_MEETUPS).toBe(1380);
  });

  it("Activity Feed starts at correct frame", () => {
    expect(SCENE_START_FRAMES.ACTIVITY_FEED).toBe(1680);
  });

  it("Call to Action starts at correct frame", () => {
    expect(SCENE_START_FRAMES.CALL_TO_ACTION).toBe(1980);
  });

  it("Outro starts at correct frame", () => {
    expect(SCENE_START_FRAMES.OUTRO).toBe(2340);
  });

  it("all scenes sum to total duration", () => {
    const lastSceneStart = SCENE_START_FRAMES.OUTRO;
    const lastSceneDuration = SCENE_DURATIONS.OUTRO * STANDARD_FPS;
    expect(lastSceneStart + lastSceneDuration).toBe(TOTAL_DURATION_FRAMES);
  });

  it("calculateSceneStartFrames returns same values at 30fps", () => {
    const calculated = calculateSceneStartFrames(30);
    expect(calculated).toEqual(SCENE_START_FRAMES);
  });

  it("calculateSceneStartFrames scales with different FPS", () => {
    const calculated60fps = calculateSceneStartFrames(60);
    expect(calculated60fps.LOGO_REVEAL).toBe(0);
    expect(calculated60fps.PORTAL_TITLE).toBe(360); // 6 seconds * 60fps
    expect(calculated60fps.DASHBOARD_OVERVIEW).toBe(600); // (6 + 4) * 60fps
  });
});

describe("Logo Reveal Audio Events", () => {
  it("has correct number of audio events", () => {
    expect(LOGO_REVEAL_AUDIO.length).toBe(2);
  });

  it("logo-whoosh starts at frame 0", () => {
    const event = LOGO_REVEAL_AUDIO.find((e) => e.id === "logo-whoosh");
    expect(event).toBeDefined();
    expect(event!.startFrame).toBe(0);
    expect(event!.volume).toBe(0.7);
    expect(event!.toleranceFrames).toBe(0);
  });

  it("logo-reveal starts at correct delay", () => {
    const event = LOGO_REVEAL_AUDIO.find((e) => e.id === "logo-reveal");
    expect(event).toBeDefined();
    expect(event!.startFrame).toBe(15); // 0.5 seconds
    expect(event!.volume).toBe(0.6);
  });

  it("all events have required properties", () => {
    for (const event of LOGO_REVEAL_AUDIO) {
      expect(event.id).toBeDefined();
      expect(event.audioFile).toMatch(/^sfx\//);
      expect(event.startFrame).toBeGreaterThanOrEqual(0);
      expect(event.durationInFrames).toBeGreaterThan(0);
      expect(event.volume).toBeGreaterThan(0);
      expect(event.volume).toBeLessThanOrEqual(1);
      expect(event.visualSyncTarget).toBeDefined();
    }
  });
});

describe("Portal Title Audio Events", () => {
  it("has ui-appear sound", () => {
    const event = PORTAL_TITLE_AUDIO.find((e) => e.id === "title-ui-appear");
    expect(event).toBeDefined();
    expect(event!.audioFile).toBe("sfx/ui-appear.mp3");
  });
});

describe("Dashboard Overview Audio Events", () => {
  it("has card slide sounds for staggered cards", () => {
    const cardSlideEvents = DASHBOARD_OVERVIEW_AUDIO.filter((e) =>
      e.id.includes("card-slide")
    );
    expect(cardSlideEvents.length).toBe(2);
  });

  it("card slides are staggered to avoid overlap", () => {
    const slide1 = DASHBOARD_OVERVIEW_AUDIO.find((e) => e.id === "dashboard-card-slide-1");
    const slide2 = DASHBOARD_OVERVIEW_AUDIO.find((e) => e.id === "dashboard-card-slide-2");
    expect(slide1).toBeDefined();
    expect(slide2).toBeDefined();
    // Second card starts after first card audio finishes
    expect(slide2!.startFrame).toBeGreaterThanOrEqual(
      slide1!.startFrame + slide1!.durationInFrames
    );
  });
});

describe("Call to Action Audio Events", () => {
  it("has success fanfare for celebration", () => {
    const event = CALL_TO_ACTION_AUDIO.find((e) => e.id === "cta-success-fanfare");
    expect(event).toBeDefined();
    expect(event!.volume).toBe(0.6);
    expect(event!.durationInFrames).toBe(90); // 3 seconds
  });

  it("has URL emphasis audio synced with typing", () => {
    const event = CALL_TO_ACTION_AUDIO.find((e) => e.id === "cta-url-emphasis");
    expect(event).toBeDefined();
    expect(event!.startFrame).toBe(75); // 2.5 seconds
  });

  it("has final chime for button emphasis", () => {
    const event = CALL_TO_ACTION_AUDIO.find((e) => e.id === "cta-final-chime");
    expect(event).toBeDefined();
    expect(event!.startFrame).toBe(180); // 6 seconds into scene
  });
});

describe("Outro Audio Events", () => {
  it("outro-entrance starts immediately", () => {
    const event = OUTRO_AUDIO.find((e) => e.id === "outro-entrance");
    expect(event).toBeDefined();
    expect(event!.startFrame).toBe(0);
    expect(event!.toleranceFrames).toBe(0);
  });

  it("outro logo reveal syncs with logo animation", () => {
    const event = OUTRO_AUDIO.find((e) => e.id === "outro-logo-reveal");
    expect(event).toBeDefined();
    expect(event!.startFrame).toBe(30); // 1 second
  });
});

describe("Scene Audio Configurations", () => {
  it("has configurations for all 9 scenes", () => {
    expect(SCENE_AUDIO_CONFIGS.length).toBe(9);
  });

  it("all scenes have correct start frames", () => {
    for (const config of SCENE_AUDIO_CONFIGS) {
      expect(config.sceneStartFrame).toBe(SCENE_START_FRAMES[config.sceneId]);
    }
  });

  it("all scenes have matching duration frames", () => {
    for (const config of SCENE_AUDIO_CONFIGS) {
      const expectedDuration =
        SCENE_DURATIONS[config.sceneId as keyof typeof SCENE_DURATIONS] * STANDARD_FPS;
      expect(config.sceneDurationInFrames).toBe(expectedDuration);
    }
  });

  it("no audio events exceed scene duration", () => {
    for (const config of SCENE_AUDIO_CONFIGS) {
      for (const event of config.audioEvents) {
        const eventEnd = event.startFrame + event.durationInFrames;
        expect(eventEnd).toBeLessThanOrEqual(config.sceneDurationInFrames);
      }
    }
  });

  it("all audio event IDs are unique across scenes", () => {
    const allIds = SCENE_AUDIO_CONFIGS.flatMap((c) => c.audioEvents.map((e) => e.id));
    const uniqueIds = new Set(allIds);
    expect(uniqueIds.size).toBe(allIds.length);
  });
});

describe("getSceneAudioEvents", () => {
  it("returns audio events for valid scene", () => {
    const events = getSceneAudioEvents("LOGO_REVEAL");
    expect(events).toEqual(LOGO_REVEAL_AUDIO);
  });

  it("returns empty array for invalid scene", () => {
    const events = getSceneAudioEvents("INVALID_SCENE");
    expect(events).toEqual([]);
  });

  it("returns events for all scenes", () => {
    const sceneIds = [
      "LOGO_REVEAL",
      "PORTAL_TITLE",
      "DASHBOARD_OVERVIEW",
      "MEINE_MEETUPS",
      "TOP_LAENDER",
      "TOP_MEETUPS",
      "ACTIVITY_FEED",
      "CALL_TO_ACTION",
      "OUTRO",
    ];
    for (const sceneId of sceneIds) {
      const events = getSceneAudioEvents(sceneId);
      expect(events.length).toBeGreaterThan(0);
    }
  });
});

describe("getAbsoluteAudioFrame", () => {
  it("returns correct absolute frame for logo-whoosh", () => {
    const frame = getAbsoluteAudioFrame("LOGO_REVEAL", "logo-whoosh");
    expect(frame).toBe(0);
  });

  it("returns correct absolute frame for logo-reveal", () => {
    const frame = getAbsoluteAudioFrame("LOGO_REVEAL", "logo-reveal");
    expect(frame).toBe(15); // Scene starts at 0, event at 15
  });

  it("returns correct absolute frame for title-ui-appear", () => {
    const frame = getAbsoluteAudioFrame("PORTAL_TITLE", "title-ui-appear");
    expect(frame).toBe(180 + 15); // Scene starts at 180, event at 15
  });

  it("returns correct absolute frame for CTA events", () => {
    const fanfareFrame = getAbsoluteAudioFrame("CALL_TO_ACTION", "cta-success-fanfare");
    expect(fanfareFrame).toBe(1980 + 30); // Scene at 1980, event at 30

    const urlFrame = getAbsoluteAudioFrame("CALL_TO_ACTION", "cta-url-emphasis");
    expect(urlFrame).toBe(1980 + 75); // Scene at 1980, event at 75
  });

  it("returns null for invalid scene", () => {
    const frame = getAbsoluteAudioFrame("INVALID", "logo-whoosh");
    expect(frame).toBeNull();
  });

  it("returns null for invalid event", () => {
    const frame = getAbsoluteAudioFrame("LOGO_REVEAL", "invalid-event");
    expect(frame).toBeNull();
  });
});

describe("getAllAudioEventsAbsolute", () => {
  it("returns all events with absolute frames", () => {
    const events = getAllAudioEventsAbsolute();
    expect(events.length).toBeGreaterThan(0);
    for (const event of events) {
      expect(event.absoluteStartFrame).toBeDefined();
      expect(event.absoluteStartFrame).toBeGreaterThanOrEqual(0);
      expect(event.absoluteStartFrame).toBeLessThan(TOTAL_DURATION_FRAMES);
    }
  });

  it("events are sorted by absolute start frame", () => {
    const events = getAllAudioEventsAbsolute();
    for (let i = 1; i < events.length; i++) {
      expect(events[i].absoluteStartFrame).toBeGreaterThanOrEqual(
        events[i - 1].absoluteStartFrame
      );
    }
  });

  it("first event is at frame 0", () => {
    const events = getAllAudioEventsAbsolute();
    expect(events[0].absoluteStartFrame).toBe(0);
  });

  it("total events equals sum from all scenes", () => {
    const events = getAllAudioEventsAbsolute();
    const expectedCount = SCENE_AUDIO_CONFIGS.reduce(
      (sum, config) => sum + config.audioEvents.length,
      0
    );
    expect(events.length).toBe(expectedCount);
  });
});

describe("calculateBackgroundMusicVolume", () => {
  describe("fade-in phase", () => {
    it("returns 0 at frame 0", () => {
      const volume = calculateBackgroundMusicVolume(0);
      expect(volume).toBe(0);
    });

    it("returns half base volume midway through fade-in", () => {
      const volume = calculateBackgroundMusicVolume(15); // Half of 30 frames
      expect(volume).toBeCloseTo(0.125, 4); // Half of 0.25
    });

    it("returns base volume at end of fade-in", () => {
      const volume = calculateBackgroundMusicVolume(30);
      expect(volume).toBeCloseTo(0.25, 4);
    });
  });

  describe("normal volume phase", () => {
    it("returns base volume in middle of video", () => {
      const volume = calculateBackgroundMusicVolume(1350); // Midpoint
      expect(volume).toBe(0.25);
    });

    it("returns base volume just before fade-out starts", () => {
      const volume = calculateBackgroundMusicVolume(2609); // Frame before fade-out
      expect(volume).toBe(0.25);
    });
  });

  describe("fade-out phase", () => {
    it("starts fade-out at correct frame", () => {
      const fadeOutStart = TOTAL_DURATION_FRAMES - 90; // 3150
      const volumeJustBefore = calculateBackgroundMusicVolume(fadeOutStart - 1);
      const volumeAtStart = calculateBackgroundMusicVolume(fadeOutStart);
      const volumeAfterStart = calculateBackgroundMusicVolume(fadeOutStart + 1);
      expect(volumeJustBefore).toBe(0.25);
      // At exact fade-out start, volume begins decreasing (may still be 0.25 or just below)
      expect(volumeAtStart).toBeLessThanOrEqual(0.25);
      expect(volumeAfterStart).toBeLessThan(0.25);
    });

    it("returns half volume midway through fade-out", () => {
      const fadeOutStart = 3150; // 3240 - 90
      const midPoint = fadeOutStart + 45; // 45 frames into 90-frame fade
      const volume = calculateBackgroundMusicVolume(midPoint);
      expect(volume).toBeCloseTo(0.125, 4);
    });

    it("returns 0 at final frame", () => {
      const volume = calculateBackgroundMusicVolume(3240);
      expect(volume).toBe(0);
    });
  });

  describe("with custom FPS", () => {
    it("calculates correctly at 60fps", () => {
      // At 60fps, fade-in is 60 frames (1 second)
      const volumeAtStart = calculateBackgroundMusicVolume(0, 60, 5400);
      const volumeMid = calculateBackgroundMusicVolume(30, 60, 5400);
      const volumeEnd = calculateBackgroundMusicVolume(60, 60, 5400);
      expect(volumeAtStart).toBe(0);
      expect(volumeMid).toBeCloseTo(0.125, 4);
      expect(volumeEnd).toBeCloseTo(0.25, 4);
    });
  });
});

describe("isAudioEventInSync", () => {
  it("returns true for exact match", () => {
    expect(isAudioEventInSync(100, 100)).toBe(true);
  });

  it("returns true within tolerance", () => {
    expect(isAudioEventInSync(102, 100, 3)).toBe(true);
    expect(isAudioEventInSync(98, 100, 3)).toBe(true);
  });

  it("returns false outside tolerance", () => {
    expect(isAudioEventInSync(104, 100, 3)).toBe(false);
    expect(isAudioEventInSync(96, 100, 3)).toBe(false);
  });

  it("returns true at exactly tolerance boundary", () => {
    expect(isAudioEventInSync(103, 100, 3)).toBe(true);
    expect(isAudioEventInSync(97, 100, 3)).toBe(true);
  });

  it("defaults to zero tolerance", () => {
    expect(isAudioEventInSync(100, 100)).toBe(true);
    expect(isAudioEventInSync(101, 100)).toBe(false);
  });
});

describe("getAudioTimingDeviation", () => {
  it("returns 0 for exact match", () => {
    expect(getAudioTimingDeviation(100, 100)).toBe(0);
  });

  it("returns negative for early audio", () => {
    expect(getAudioTimingDeviation(95, 100)).toBe(-5);
  });

  it("returns positive for late audio", () => {
    expect(getAudioTimingDeviation(105, 100)).toBe(5);
  });
});

describe("frameDeviationToMs", () => {
  it("converts frames to milliseconds at 30fps", () => {
    expect(frameDeviationToMs(1)).toBeCloseTo(33.33, 1);
    expect(frameDeviationToMs(30)).toBeCloseTo(1000, 0);
    expect(frameDeviationToMs(15)).toBeCloseTo(500, 0);
  });

  it("converts negative frames", () => {
    expect(frameDeviationToMs(-3)).toBeCloseTo(-100, 0);
  });

  it("handles custom FPS", () => {
    expect(frameDeviationToMs(60, 60)).toBeCloseTo(1000, 0);
    expect(frameDeviationToMs(24, 24)).toBeCloseTo(1000, 0);
  });
});

describe("Audio Event Validation", () => {
  const validateAudioEvent = (event: AudioEvent) => {
    // Volume bounds
    expect(event.volume).toBeGreaterThan(0);
    expect(event.volume).toBeLessThanOrEqual(1);

    // Frame bounds
    expect(event.startFrame).toBeGreaterThanOrEqual(0);
    expect(event.durationInFrames).toBeGreaterThan(0);

    // File path format
    expect(event.audioFile).toMatch(/\.(mp3|wav|ogg)$/);

    // Tolerance bounds
    if (event.toleranceFrames !== undefined) {
      expect(event.toleranceFrames).toBeGreaterThanOrEqual(0);
      expect(event.toleranceFrames).toBeLessThanOrEqual(5);
    }
  };

  it("all Logo Reveal events are valid", () => {
    LOGO_REVEAL_AUDIO.forEach(validateAudioEvent);
  });

  it("all Portal Title events are valid", () => {
    PORTAL_TITLE_AUDIO.forEach(validateAudioEvent);
  });

  it("all Dashboard Overview events are valid", () => {
    DASHBOARD_OVERVIEW_AUDIO.forEach(validateAudioEvent);
  });

  it("all Meetup Showcase events are valid", () => {
    MEETUP_SHOWCASE_AUDIO.forEach(validateAudioEvent);
  });

  it("all Country Stats events are valid", () => {
    COUNTRY_STATS_AUDIO.forEach(validateAudioEvent);
  });

  it("all Top Meetups events are valid", () => {
    TOP_MEETUPS_AUDIO.forEach(validateAudioEvent);
  });

  it("all Activity Feed events are valid", () => {
    ACTIVITY_FEED_AUDIO.forEach(validateAudioEvent);
  });

  it("all Call to Action events are valid", () => {
    CALL_TO_ACTION_AUDIO.forEach(validateAudioEvent);
  });

  it("all Outro events are valid", () => {
    OUTRO_AUDIO.forEach(validateAudioEvent);
  });
});

describe("Frame-Accurate Timing Verification", () => {
  it("logo-whoosh syncs exactly with video start", () => {
    const frame = getAbsoluteAudioFrame("LOGO_REVEAL", "logo-whoosh");
    expect(frame).toBe(0);
    const tolerance = LOGO_REVEAL_AUDIO.find((e) => e.id === "logo-whoosh")?.toleranceFrames;
    expect(tolerance).toBe(0); // Must be exact
  });

  it("logo-reveal syncs with logo entrance delay", () => {
    const frame = getAbsoluteAudioFrame("LOGO_REVEAL", "logo-reveal");
    // TIMING.LOGO_ENTRANCE_DELAY = 0.5 seconds = 15 frames
    expect(frame).toBe(15);
  });

  it("outro-entrance starts exactly when outro scene begins", () => {
    const frame = getAbsoluteAudioFrame("OUTRO", "outro-entrance");
    expect(frame).toBe(SCENE_START_FRAMES.OUTRO);
    expect(frame).toBe(2340);
  });

  it("CTA fanfare plays after overlay animation settles", () => {
    const frame = getAbsoluteAudioFrame("CALL_TO_ACTION", "cta-success-fanfare");
    const sceneStart = SCENE_START_FRAMES.CALL_TO_ACTION;
    // Event starts 30 frames (1 second) into scene
    expect(frame).toBe(sceneStart + 30);
  });

  it("all critical sync points have zero or low tolerance", () => {
    const criticalEvents = ["logo-whoosh", "outro-entrance"];
    const allEvents = getAllAudioEventsAbsolute();

    for (const eventId of criticalEvents) {
      const event = allEvents.find((e) => e.id === eventId);
      expect(event).toBeDefined();
      expect(event!.toleranceFrames ?? 0).toBeLessThanOrEqual(2);
    }
  });
});

describe("Audio Overlap Detection", () => {
  it("no overlapping audio events within same scene", () => {
    for (const config of SCENE_AUDIO_CONFIGS) {
      const events = config.audioEvents;
      for (let i = 0; i < events.length; i++) {
        for (let j = i + 1; j < events.length; j++) {
          const e1Start = events[i].startFrame;
          const e1End = e1Start + events[i].durationInFrames;
          const e2Start = events[j].startFrame;
          const e2End = e2Start + events[j].durationInFrames;

          // Check if same audio file overlaps with itself
          if (events[i].audioFile === events[j].audioFile) {
            const overlaps = e1Start < e2End && e2Start < e1End;
            if (overlaps) {
              // Same audio file should not overlap
              expect(overlaps).toBe(false);
            }
          }
        }
      }
    }
  });
});

describe("Volume Consistency", () => {
  it("background music never exceeds base volume", () => {
    for (let frame = 0; frame <= TOTAL_DURATION_FRAMES; frame += 10) {
      const volume = calculateBackgroundMusicVolume(frame);
      expect(volume).toBeLessThanOrEqual(BACKGROUND_MUSIC_CONFIG.baseVolume);
    }
  });

  it("SFX volumes are reasonable", () => {
    const allEvents = getAllAudioEventsAbsolute();
    for (const event of allEvents) {
      // SFX should be between 0.3 and 0.7 typically
      expect(event.volume).toBeGreaterThanOrEqual(0.3);
      expect(event.volume).toBeLessThanOrEqual(0.7);
    }
  });

  it("total potential volume at any frame is reasonable", () => {
    // At any given frame, the sum of volumes should not cause clipping
    // Background is 0.25, individual SFX up to 0.7
    // Max potential is around 0.95 which is safe
    const allEvents = getAllAudioEventsAbsolute();
    for (const event of allEvents) {
      const bgVolume = BACKGROUND_MUSIC_CONFIG.baseVolume;
      const totalPotentialVolume = bgVolume + event.volume;
      expect(totalPotentialVolume).toBeLessThanOrEqual(1.0);
    }
  });
});

describe("Timing Integration with TIMING config", () => {
  it("logo audio syncs with TIMING.LOGO_ENTRANCE_DELAY", () => {
    const logoReveal = LOGO_REVEAL_AUDIO.find((e) => e.id === "logo-reveal");
    const expectedFrame = secondsToFrames(0.5); // TIMING.LOGO_ENTRANCE_DELAY = 0.5
    expect(logoReveal?.startFrame).toBe(expectedFrame);
  });

  it("outro logo audio syncs with TIMING.OUTRO_LOGO_DELAY", () => {
    const outroLogo = OUTRO_AUDIO.find((e) => e.id === "outro-logo-reveal");
    const expectedFrame = secondsToFrames(1.0); // TIMING.OUTRO_LOGO_DELAY = 1.0
    expect(outroLogo?.startFrame).toBe(expectedFrame);
  });

  it("CTA URL audio syncs with TIMING.CTA_URL_DELAY", () => {
    const ctaUrl = CALL_TO_ACTION_AUDIO.find((e) => e.id === "cta-url-emphasis");
    const expectedFrame = secondsToFrames(2.5); // TIMING.CTA_URL_DELAY = 2.5
    expect(ctaUrl?.startFrame).toBe(expectedFrame);
  });
});
