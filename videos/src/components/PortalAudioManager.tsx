import { Audio } from "@remotion/media";
import {
  staticFile,
  useVideoConfig,
  useCurrentFrame,
  interpolate,
} from "remotion";

/**
 * PortalAudioManager Component
 *
 * Manages background music for the Portal Presentation video with:
 * - 1 second fade-in at the beginning
 * - 3 second fade-out at the end
 *
 * Scene-specific sound effects are handled within each scene component
 * for better maintainability and timing accuracy.
 *
 * Scene Structure (90 seconds total @ 30fps = 2700 frames):
 * 1. Logo Reveal (6s) - Frames 0-180
 * 2. Portal Title (4s) - Frames 180-300
 * 3. Dashboard Overview (12s) - Frames 300-660
 * 4. Meine Meetups (12s) - Frames 660-1020
 * 5. Top Länder (12s) - Frames 1020-1380
 * 6. Top Meetups (10s) - Frames 1380-1680
 * 7. Activity Feed (10s) - Frames 1680-1980
 * 8. Call to Action (12s) - Frames 1980-2340
 * 9. Outro (12s) - Frames 2340-2700
 */

/**
 * BackgroundMusic - Handles the background music track with fade in/out
 */
const BackgroundMusic: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, durationInFrames } = useVideoConfig();

  // Fade-in for 1 second
  const fadeInDuration = 1 * fps;
  // Fade-out for 3 seconds
  const fadeOutDuration = 3 * fps;
  const fadeOutStart = durationInFrames - fadeOutDuration;

  // Base volume for background music
  const baseVolume = 0.25;

  // Calculate volume with fade-in and fade-out
  let volume = baseVolume;

  // Fade-in at the beginning
  if (frame < fadeInDuration) {
    volume = interpolate(frame, [0, fadeInDuration], [0, baseVolume], {
      extrapolateLeft: "clamp",
      extrapolateRight: "clamp",
    });
  }
  // Fade-out at the end
  else if (frame >= fadeOutStart) {
    volume = interpolate(
      frame,
      [fadeOutStart, durationInFrames],
      [baseVolume, 0],
      {
        extrapolateLeft: "clamp",
        extrapolateRight: "clamp",
      }
    );
  }

  return (
    <Audio src={staticFile("music/background-music.mp3")} volume={volume} loop />
  );
};

/**
 * PortalAudioManager - Main audio manager component for the Portal Presentation
 *
 * This component handles the background music with proper fade in/out.
 * Scene-specific sound effects are embedded within each scene component
 * for better timing accuracy and maintainability.
 */
export const PortalAudioManager: React.FC = () => {
  return (
    <>
      {/* Background Music - plays throughout the entire video */}
      <BackgroundMusic />
    </>
  );
};
