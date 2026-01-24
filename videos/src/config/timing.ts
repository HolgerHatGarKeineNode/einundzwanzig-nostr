/**
 * Centralized timing configuration for all Portal Presentation animations.
 *
 * This file provides a single source of truth for:
 * - Spring configurations (animation feel)
 * - Stagger delays (sequencing)
 * - Transition durations (timing)
 *
 * Usage:
 * import { SPRING_CONFIGS, STAGGER_DELAYS, TIMING } from '../config/timing';
 */

// ============================================================================
// SPRING CONFIGURATIONS
// ============================================================================

/**
 * Spring animation configurations for different animation feels.
 * These are optimized for 30fps and provide consistent motion across scenes.
 */
export const SPRING_CONFIGS = {
  /**
   * SMOOTH - Very slow, gentle animations
   * Use for: fades, overlays, background transitions, outro elements
   * Character: Elegant, cinematic, no bounce
   */
  SMOOTH: { damping: 200 },

  /**
   * SNAPPY - Fast, responsive UI element entrances
   * Use for: cards, headers, buttons, UI elements
   * Character: Professional, quick, minimal overshoot
   */
  SNAPPY: { damping: 15, stiffness: 80 },

  /**
   * BOUNCY - Energetic, playful animations
   * Use for: titles, logos, badges, call-to-action elements
   * Character: Lively, attention-grabbing, noticeable bounce
   */
  BOUNCY: { damping: 12 },

  /**
   * PERSPECTIVE - 3D entrance animations
   * Use for: scene entrances with rotateX perspective effect
   * Character: Cinematic, immersive, theatrical
   */
  PERSPECTIVE: { damping: 20, stiffness: 60 },

  /**
   * FEATURED - Featured card 3D shadows and highlights
   * Use for: hero cards, featured elements, highlighted content
   * Character: Premium, elevated, attention-worthy
   */
  FEATURED: { damping: 18, stiffness: 70 },

  /**
   * LOGO - Logo entrance animations
   * Use for: logo reveals, brand elements
   * Character: Impactful, memorable, brand-aligned
   */
  LOGO: { damping: 15, stiffness: 80 },

  /**
   * BADGE - Badge bounce animations
   * Use for: notification badges, count indicators, tags
   * Character: Playful, noticeable, draws attention
   */
  BADGE: { damping: 8, stiffness: 150 },

  /**
   * BUTTON - Button scale animations
   * Use for: CTA buttons, interactive elements
   * Character: Responsive, tactile, inviting
   */
  BUTTON: { damping: 12, stiffness: 100 },

  /**
   * COUNTER - Number counting animations
   * Use for: stats counters, animated numbers
   * Character: Smooth counting, professional
   */
  COUNTER: { damping: 20, stiffness: 80, mass: 1 },

  /**
   * ROW - List row entrance animations
   * Use for: list items, table rows
   * Character: Quick, efficient, organized
   */
  ROW: { damping: 15, stiffness: 90 },
} as const;

// ============================================================================
// STAGGER DELAYS (in frames @ 30fps)
// ============================================================================

/**
 * Stagger delays for sequential animations.
 * All values are in frames at 30fps (multiply by 1000/30 for ms).
 */
export const STAGGER_DELAYS = {
  /** Cards in a grid/row (5 frames = ~167ms) */
  CARD: 5,

  /** List items in meetup lists (8 frames = ~267ms) */
  LIST_ITEM: 8,

  /** Country statistics bars (12 frames = ~400ms) */
  COUNTRY: 12,

  /** Top meetups ranking items (15 frames = ~500ms) */
  MEETUP_RANK: 15,

  /** Activity feed items (20 frames = ~667ms) */
  ACTIVITY: 20,

  /** Sidebar navigation items (3 frames = ~100ms) */
  SIDEBAR: 3,

  /** Quick stat rows (8 frames = ~267ms) */
  QUICK_STAT: 8,
} as const;

// ============================================================================
// TIMING CONSTANTS (in seconds, convert to frames with * fps)
// ============================================================================

/**
 * Timing constants for delays and durations.
 * All values are in seconds - multiply by fps to get frames.
 */
export const TIMING = {
  // Scene entrance delays
  PERSPECTIVE_ENTRANCE: 0, // Start immediately with perspective
  HEADER_DELAY: 0.5, // Headers appear after perspective settles
  CONTENT_BASE_DELAY: 1.0, // Content starts after 1 second
  FEATURED_DELAY: 0.8, // Featured items delay

  // Logo/Brand timing
  LOGO_ENTRANCE_DELAY: 0.5, // Logo reveal delay in intro
  TITLE_DELAY: 2.0, // Title text appears after logo
  SUBTITLE_DELAY: 2.8, // Subtitle follows title

  // Typing animation
  CHAR_FRAMES: 2, // Frames per character in typing
  CURSOR_BLINK_FRAMES: 16, // Cursor blink cycle

  // Counter animation
  COUNTER_DURATION: 60, // Frames for counter to complete
  COUNTER_PRE_DELAY: 15, // Frames before counter starts

  // Sparkline drawing
  SPARKLINE_DURATION: 45, // Frames to draw sparkline
  SPARKLINE_PRE_DELAY: 30, // Frames before sparkline starts

  // Call to Action scene
  CTA_OVERLAY_DELAY: 0.5, // Glassmorphism overlay
  CTA_TITLE_DELAY: 1.0, // Title entrance
  CTA_LOGO_DELAY: 1.5, // Logo entrance
  CTA_URL_DELAY: 2.5, // URL typing start
  CTA_URL_DURATION: 1.5, // URL typing duration
  CTA_SUBTITLE_DELAY: 3.5, // Final subtitle

  // Outro scene
  OUTRO_LOGO_DELAY: 1.0, // Logo entrance
  OUTRO_TEXT_DELAY: 2.0, // Text entrance
  OUTRO_SUBTITLE_DELAY: 2.5, // Subtitle entrance
  OUTRO_FADE_DURATION: 2.0, // Final fade out duration

  // Audio sync offsets
  AUDIO_PRE_ROLL: 0.5, // Audio starts slightly before visual
} as const;

// ============================================================================
// GLOW EFFECT PARAMETERS
// ============================================================================

/**
 * Glow effect parameters for pulsing animations.
 */
export const GLOW_CONFIG = {
  /** Glow intensity range [min, max] */
  INTENSITY: {
    SUBTLE: [0.3, 0.5] as const,
    NORMAL: [0.4, 0.8] as const,
    STRONG: [0.5, 1.0] as const,
  },

  /** Glow pulse frequency (lower = slower) */
  FREQUENCY: {
    SLOW: 0.04,
    NORMAL: 0.06,
    FAST: 0.08,
    PULSE: 0.1,
  },

  /** Glow scale range [min, max] */
  SCALE: {
    SUBTLE: [1.0, 1.1] as const,
    NORMAL: [1.0, 1.15] as const,
    STRONG: [1.0, 1.2] as const,
  },
} as const;

// ============================================================================
// SCENE DURATIONS (in seconds)
// ============================================================================

/**
 * Scene duration constants matching PortalPresentation.tsx.
 * Total: 90 seconds = 2700 frames @ 30fps
 */
export const SCENE_DURATIONS = {
  LOGO_REVEAL: 6,
  PORTAL_TITLE: 4,
  DASHBOARD_OVERVIEW: 12,
  MEINE_MEETUPS: 12,
  TOP_LAENDER: 12,
  TOP_MEETUPS: 10,
  ACTIVITY_FEED: 10,
  CALL_TO_ACTION: 12,
  OUTRO: 12,
} as const;

// ============================================================================
// TRANSITION EASING HELPERS
// ============================================================================

/**
 * Helper to calculate frame-based delay from seconds.
 * @param seconds - Time in seconds
 * @param fps - Frames per second (default 30)
 * @returns Number of frames
 */
export function secondsToFrames(seconds: number, fps: number = 30): number {
  return Math.floor(seconds * fps);
}

/**
 * Helper to calculate staggered delay for an item in a sequence.
 * @param index - Item index in the sequence
 * @param baseDelay - Base delay in frames
 * @param staggerDelay - Delay between items in frames
 * @returns Total delay in frames for this item
 */
export function getStaggeredDelay(
  index: number,
  baseDelay: number,
  staggerDelay: number
): number {
  return baseDelay + index * staggerDelay;
}
