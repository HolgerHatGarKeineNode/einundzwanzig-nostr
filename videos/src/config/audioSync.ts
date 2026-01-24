/**
 * Audio Sync Configuration
 *
 * Frame-accurate audio timing definitions for the Portal Presentation.
 * All audio events are defined with their exact frame positions and expected visual sync points.
 *
 * This configuration serves as the source of truth for audio-visual synchronization
 * and enables automated verification of audio timing accuracy.
 */

import { SCENE_DURATIONS, secondsToFrames } from "./timing";

// ============================================================================
// TYPES
// ============================================================================

/**
 * Represents a single audio event with its timing and visual sync target.
 */
export interface AudioEvent {
  /** Unique identifier for the audio event */
  id: string;
  /** Audio file path (relative to public directory) */
  audioFile: string;
  /** Frame number when audio should start (absolute, from composition start) */
  startFrame: number;
  /** Duration in frames for the audio playback */
  durationInFrames: number;
  /** Volume level (0-1) */
  volume: number;
  /** Description of what visual element this syncs with */
  visualSyncTarget: string;
  /** Frame offset tolerance for sync verification (default 0 = exact) */
  toleranceFrames?: number;
}

/**
 * Background music configuration with fade parameters.
 */
export interface BackgroundMusicConfig {
  /** Audio file path */
  audioFile: string;
  /** Base volume level */
  baseVolume: number;
  /** Fade-in duration in seconds */
  fadeInDuration: number;
  /** Fade-out duration in seconds */
  fadeOutDuration: number;
  /** Whether to loop the audio */
  loop: boolean;
}

/**
 * Scene audio configuration containing all audio events for a scene.
 */
export interface SceneAudioConfig {
  /** Scene identifier */
  sceneId: string;
  /** Scene start frame (absolute) */
  sceneStartFrame: number;
  /** Scene duration in frames */
  sceneDurationInFrames: number;
  /** Audio events within this scene (frames relative to scene start) */
  audioEvents: AudioEvent[];
}

// ============================================================================
// CONSTANTS
// ============================================================================

/** Standard FPS for all calculations */
export const STANDARD_FPS = 30;

/** Total composition duration in frames */
export const TOTAL_DURATION_FRAMES = 2700;

/** Total composition duration in seconds */
export const TOTAL_DURATION_SECONDS = 90;

// ============================================================================
// BACKGROUND MUSIC CONFIGURATION
// ============================================================================

export const BACKGROUND_MUSIC_CONFIG: BackgroundMusicConfig = {
  audioFile: "music/background-music.mp3",
  baseVolume: 0.25,
  fadeInDuration: 1, // 1 second = 30 frames
  fadeOutDuration: 3, // 3 seconds = 90 frames
  loop: true,
};

// ============================================================================
// SCENE START FRAMES (calculated from SCENE_DURATIONS)
// ============================================================================

/**
 * Calculate scene start frames based on durations.
 * Returns an object with scene names and their absolute start frame positions.
 */
export function calculateSceneStartFrames(fps: number = STANDARD_FPS): Record<string, number> {
  let currentFrame = 0;
  const sceneOrder = [
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

  const startFrames: Record<string, number> = {};

  for (const scene of sceneOrder) {
    startFrames[scene] = currentFrame;
    const duration = SCENE_DURATIONS[scene as keyof typeof SCENE_DURATIONS];
    currentFrame += duration * fps;
  }

  return startFrames;
}

/**
 * Pre-calculated scene start frames at 30fps for quick reference.
 */
export const SCENE_START_FRAMES = calculateSceneStartFrames(STANDARD_FPS);

// ============================================================================
// AUDIO EVENT DEFINITIONS BY SCENE
// ============================================================================

/**
 * Audio events for the Logo Reveal scene (Scene 1).
 * Duration: 6 seconds (180 frames)
 */
export const LOGO_REVEAL_AUDIO: AudioEvent[] = [
  {
    id: "logo-whoosh",
    audioFile: "sfx/logo-whoosh.mp3",
    startFrame: 0, // Starts immediately
    durationInFrames: 60, // 2 seconds
    volume: 0.7,
    visualSyncTarget: "Background zoom animation start",
    toleranceFrames: 0,
  },
  {
    id: "logo-reveal",
    audioFile: "sfx/logo-reveal.mp3",
    startFrame: 15, // 0.5 seconds (TIMING.LOGO_ENTRANCE_DELAY)
    durationInFrames: 60, // 2 seconds
    volume: 0.6,
    visualSyncTarget: "Logo entrance animation start",
    toleranceFrames: 2,
  },
];

/**
 * Audio events for the Portal Title scene (Scene 2).
 * Duration: 4 seconds (120 frames)
 */
export const PORTAL_TITLE_AUDIO: AudioEvent[] = [
  {
    id: "title-ui-appear",
    audioFile: "sfx/ui-appear.mp3",
    startFrame: 15, // 0.5 seconds into scene
    durationInFrames: 30, // 1 second
    volume: 0.5,
    visualSyncTarget: "Title text entrance",
    toleranceFrames: 2,
  },
];

/**
 * Audio events for the Dashboard Overview scene (Scene 3).
 * Duration: 12 seconds (360 frames)
 */
export const DASHBOARD_OVERVIEW_AUDIO: AudioEvent[] = [
  {
    id: "dashboard-card-slide-1",
    audioFile: "sfx/card-slide.mp3",
    startFrame: 30, // 1 second into scene
    durationInFrames: 30,
    volume: 0.4,
    visualSyncTarget: "First dashboard card entrance",
    toleranceFrames: 3,
  },
  {
    id: "dashboard-card-slide-2",
    audioFile: "sfx/card-slide.mp3",
    startFrame: 75, // 2.5 seconds (after first card-slide finishes at frame 60)
    durationInFrames: 30,
    volume: 0.4,
    visualSyncTarget: "Second dashboard card entrance",
    toleranceFrames: 3,
  },
];

/**
 * Audio events for the Meetup Showcase scene (Scene 4).
 * Duration: 12 seconds (360 frames)
 */
export const MEETUP_SHOWCASE_AUDIO: AudioEvent[] = [
  {
    id: "meetup-ui-appear",
    audioFile: "sfx/ui-appear.mp3",
    startFrame: 15,
    durationInFrames: 30,
    volume: 0.5,
    visualSyncTarget: "Meetup header entrance",
    toleranceFrames: 2,
  },
  {
    id: "meetup-badge-appear",
    audioFile: "sfx/badge-appear.mp3",
    startFrame: 60, // 2 seconds
    durationInFrames: 30,
    volume: 0.5,
    visualSyncTarget: "Meetup count badge pop-in",
    toleranceFrames: 2,
  },
];

/**
 * Audio events for the Country Stats scene (Scene 5).
 * Duration: 12 seconds (360 frames)
 */
export const COUNTRY_STATS_AUDIO: AudioEvent[] = [
  {
    id: "country-ui-appear",
    audioFile: "sfx/ui-appear.mp3",
    startFrame: 15,
    durationInFrames: 30,
    volume: 0.5,
    visualSyncTarget: "Country stats header entrance",
    toleranceFrames: 2,
  },
];

/**
 * Audio events for the Top Meetups scene (Scene 6).
 * Duration: 10 seconds (300 frames)
 */
export const TOP_MEETUPS_AUDIO: AudioEvent[] = [
  {
    id: "top-meetups-ui-appear",
    audioFile: "sfx/ui-appear.mp3",
    startFrame: 15,
    durationInFrames: 30,
    volume: 0.5,
    visualSyncTarget: "Top meetups header entrance",
    toleranceFrames: 2,
  },
  {
    id: "top-meetups-slide-in",
    audioFile: "sfx/slide-in.mp3",
    startFrame: 45, // 1.5 seconds
    durationInFrames: 30,
    volume: 0.4,
    visualSyncTarget: "Ranking list slide-in",
    toleranceFrames: 3,
  },
];

/**
 * Audio events for the Activity Feed scene (Scene 7).
 * Duration: 10 seconds (300 frames)
 */
export const ACTIVITY_FEED_AUDIO: AudioEvent[] = [
  {
    id: "activity-ui-appear",
    audioFile: "sfx/ui-appear.mp3",
    startFrame: 15,
    durationInFrames: 30,
    volume: 0.5,
    visualSyncTarget: "Activity feed header entrance",
    toleranceFrames: 2,
  },
  {
    id: "activity-item-1",
    audioFile: "sfx/button-click.mp3",
    startFrame: 45,
    durationInFrames: 15,
    volume: 0.3,
    visualSyncTarget: "First activity item entrance",
    toleranceFrames: 3,
  },
  {
    id: "activity-item-2",
    audioFile: "sfx/button-click.mp3",
    startFrame: 65,
    durationInFrames: 15,
    volume: 0.3,
    visualSyncTarget: "Second activity item entrance",
    toleranceFrames: 3,
  },
];

/**
 * Audio events for the Call to Action scene (Scene 8).
 * Duration: 12 seconds (360 frames)
 */
export const CALL_TO_ACTION_AUDIO: AudioEvent[] = [
  {
    id: "cta-success-fanfare",
    audioFile: "sfx/success-fanfare.mp3",
    startFrame: 30, // 1 second
    durationInFrames: 90, // 3 seconds
    volume: 0.6,
    visualSyncTarget: "CTA glassmorphism overlay entrance",
    toleranceFrames: 3,
  },
  {
    id: "cta-url-emphasis",
    audioFile: "sfx/url-emphasis.mp3",
    startFrame: 75, // 2.5 seconds (TIMING.CTA_URL_DELAY)
    durationInFrames: 45,
    volume: 0.5,
    visualSyncTarget: "URL typing animation start",
    toleranceFrames: 2,
  },
  {
    id: "cta-final-chime",
    audioFile: "sfx/final-chime.mp3",
    startFrame: 180, // 6 seconds
    durationInFrames: 60,
    volume: 0.6,
    visualSyncTarget: "Final CTA button pulse",
    toleranceFrames: 3,
  },
];

/**
 * Audio events for the Outro scene (Scene 9).
 * Duration: 12 seconds (360 frames)
 */
export const OUTRO_AUDIO: AudioEvent[] = [
  {
    id: "outro-entrance",
    audioFile: "sfx/outro-entrance.mp3",
    startFrame: 0,
    durationInFrames: 60,
    volume: 0.5,
    visualSyncTarget: "Outro fade-in start",
    toleranceFrames: 0,
  },
  {
    id: "outro-logo-reveal",
    audioFile: "sfx/logo-reveal.mp3",
    startFrame: 30, // 1 second (TIMING.OUTRO_LOGO_DELAY)
    durationInFrames: 60,
    volume: 0.5,
    visualSyncTarget: "Outro logo entrance",
    toleranceFrames: 2,
  },
];

// ============================================================================
// SCENE AUDIO CONFIGURATIONS
// ============================================================================

/**
 * Complete audio configuration for all scenes.
 */
export const SCENE_AUDIO_CONFIGS: SceneAudioConfig[] = [
  {
    sceneId: "LOGO_REVEAL",
    sceneStartFrame: SCENE_START_FRAMES.LOGO_REVEAL,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.LOGO_REVEAL),
    audioEvents: LOGO_REVEAL_AUDIO,
  },
  {
    sceneId: "PORTAL_TITLE",
    sceneStartFrame: SCENE_START_FRAMES.PORTAL_TITLE,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.PORTAL_TITLE),
    audioEvents: PORTAL_TITLE_AUDIO,
  },
  {
    sceneId: "DASHBOARD_OVERVIEW",
    sceneStartFrame: SCENE_START_FRAMES.DASHBOARD_OVERVIEW,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.DASHBOARD_OVERVIEW),
    audioEvents: DASHBOARD_OVERVIEW_AUDIO,
  },
  {
    sceneId: "MEINE_MEETUPS",
    sceneStartFrame: SCENE_START_FRAMES.MEINE_MEETUPS,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.MEINE_MEETUPS),
    audioEvents: MEETUP_SHOWCASE_AUDIO,
  },
  {
    sceneId: "TOP_LAENDER",
    sceneStartFrame: SCENE_START_FRAMES.TOP_LAENDER,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.TOP_LAENDER),
    audioEvents: COUNTRY_STATS_AUDIO,
  },
  {
    sceneId: "TOP_MEETUPS",
    sceneStartFrame: SCENE_START_FRAMES.TOP_MEETUPS,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.TOP_MEETUPS),
    audioEvents: TOP_MEETUPS_AUDIO,
  },
  {
    sceneId: "ACTIVITY_FEED",
    sceneStartFrame: SCENE_START_FRAMES.ACTIVITY_FEED,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.ACTIVITY_FEED),
    audioEvents: ACTIVITY_FEED_AUDIO,
  },
  {
    sceneId: "CALL_TO_ACTION",
    sceneStartFrame: SCENE_START_FRAMES.CALL_TO_ACTION,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.CALL_TO_ACTION),
    audioEvents: CALL_TO_ACTION_AUDIO,
  },
  {
    sceneId: "OUTRO",
    sceneStartFrame: SCENE_START_FRAMES.OUTRO,
    sceneDurationInFrames: secondsToFrames(SCENE_DURATIONS.OUTRO),
    audioEvents: OUTRO_AUDIO,
  },
];

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

/**
 * Get all audio events for a specific scene.
 */
export function getSceneAudioEvents(sceneId: string): AudioEvent[] {
  const config = SCENE_AUDIO_CONFIGS.find((c) => c.sceneId === sceneId);
  return config?.audioEvents ?? [];
}

/**
 * Get absolute frame position for an audio event.
 * Converts scene-relative frame to absolute composition frame.
 */
export function getAbsoluteAudioFrame(sceneId: string, audioEventId: string): number | null {
  const config = SCENE_AUDIO_CONFIGS.find((c) => c.sceneId === sceneId);
  if (!config) return null;

  const event = config.audioEvents.find((e) => e.id === audioEventId);
  if (!event) return null;

  return config.sceneStartFrame + event.startFrame;
}

/**
 * Get all audio events flattened with absolute frame positions.
 */
export function getAllAudioEventsAbsolute(): Array<AudioEvent & { absoluteStartFrame: number }> {
  const events: Array<AudioEvent & { absoluteStartFrame: number }> = [];

  for (const config of SCENE_AUDIO_CONFIGS) {
    for (const event of config.audioEvents) {
      events.push({
        ...event,
        absoluteStartFrame: config.sceneStartFrame + event.startFrame,
      });
    }
  }

  return events.sort((a, b) => a.absoluteStartFrame - b.absoluteStartFrame);
}

/**
 * Calculate expected background music volume at a specific frame.
 */
export function calculateBackgroundMusicVolume(
  frame: number,
  fps: number = STANDARD_FPS,
  durationInFrames: number = TOTAL_DURATION_FRAMES
): number {
  const { baseVolume, fadeInDuration, fadeOutDuration } = BACKGROUND_MUSIC_CONFIG;
  const fadeInFrames = fadeInDuration * fps;
  const fadeOutFrames = fadeOutDuration * fps;
  const fadeOutStart = durationInFrames - fadeOutFrames;

  // Fade-in phase
  if (frame < fadeInFrames) {
    return (frame / fadeInFrames) * baseVolume;
  }

  // Fade-out phase
  if (frame >= fadeOutStart) {
    const fadeOutProgress = (frame - fadeOutStart) / fadeOutFrames;
    return baseVolume * (1 - fadeOutProgress);
  }

  // Normal volume phase
  return baseVolume;
}

/**
 * Verify if an audio event would be within tolerance of its expected frame.
 */
export function isAudioEventInSync(
  actualFrame: number,
  expectedFrame: number,
  tolerance: number = 0
): boolean {
  return Math.abs(actualFrame - expectedFrame) <= tolerance;
}

/**
 * Get timing deviation for an audio event.
 * Returns negative if too early, positive if too late.
 */
export function getAudioTimingDeviation(actualFrame: number, expectedFrame: number): number {
  return actualFrame - expectedFrame;
}

/**
 * Convert frame deviation to milliseconds.
 */
export function frameDeviationToMs(frames: number, fps: number = STANDARD_FPS): number {
  return (frames / fps) * 1000;
}
