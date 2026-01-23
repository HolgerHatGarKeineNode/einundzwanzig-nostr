import { Audio } from "@remotion/media";
import { Sequence, staticFile, useVideoConfig, useCurrentFrame, interpolate } from "remotion";

/**
 * AudioManager Component
 *
 * Manages all audio for the video including:
 * - Background music with fade-in/fade-out
 * - Sound effects for each scene
 *
 * Audio files should be placed in public/ folder
 */

const BackgroundMusic: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, durationInFrames } = useVideoConfig();

  // Fade-in for 1 second (30 frames at 30fps)
  const fadeInDuration = 1 * fps;
  // Fade-out for 3 seconds (90 frames at 30fps)
  const fadeOutDuration = 3 * fps;
  const fadeOutStart = durationInFrames - fadeOutDuration;

  // Base volume
  const baseVolume = 0.3;

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

export const AudioManager: React.FC = () => {
  const { fps } = useVideoConfig();

  return (
    <>
      {/* Background Music - plays throughout the entire video with fade-in (1s) and fade-out (3s) */}
      <BackgroundMusic />

      {/* ===== INTRO SCENE (0-12s) ===== */}

      {/* Logo reveal chime */}
      <Sequence from={0.5 * fps}>
        <Audio
          src={staticFile("sfx/logo-reveal.mp3")}
          volume={0.5}
        />
      </Sequence>

      {/* Step 2 Lightning appears */}
      <Sequence from={5 * fps}>
        <Audio
          src={staticFile("sfx/ui-appear.mp3")}
          volume={0.4}
        />
      </Sequence>

      {/* Step 3 NIP-05 appears */}
      <Sequence from={7.5 * fps}>
        <Audio
          src={staticFile("sfx/success-chime.mp3")}
          volume={0.3}
        />
      </Sequence>

      {/* Call to action */}
      <Sequence from={9.5 * fps}>
        <Audio
          src={staticFile("sfx/ui-appear.mp3")}
          volume={0.3}
        />
      </Sequence>

      {/* ===== UI SHOWCASE SCENE (12-18s) ===== */}

      {/* UI appear chime */}
      <Sequence from={12.5 * fps}>
        <Audio
          src={staticFile("sfx/ui-appear.mp3")}
          volume={0.3}
        />
      </Sequence>

      {/* ===== INPUT DEMO SCENE (18-26s) ===== */}

      {/* Typing sound effect - plays during typing animation */}
      <Sequence from={18 * fps}>
        <Audio
          src={staticFile("sfx/typing.mp3")}
          volume={0.4}
          trimAfter={2 * fps} // Only play for 2 seconds
        />
      </Sequence>


      {/* ===== SAVE BUTTON SCENE (26-31s) ===== */}

      {/* Button hover/focus */}
      <Sequence from={27.5 * fps}>
        <Audio
          src={staticFile("sfx/button-hover.mp3")}
          volume={0.3}
        />
      </Sequence>

      {/* Button click */}
      <Sequence from={28 * fps}>
        <Audio
          src={staticFile("sfx/button-click.mp3")}
          volume={0.6}
        />
      </Sequence>

      {/* Success notification */}
      <Sequence from={28.5 * fps}>
        <Audio
          src={staticFile("sfx/success-chime.mp3")}
          volume={0.5}
        />
      </Sequence>

      {/* ===== VERIFICATION SCENE (31-36s) ===== */}

      {/* Badge appear */}
      <Sequence from={31.5 * fps}>
        <Audio
          src={staticFile("sfx/badge-appear.mp3")}
          volume={0.4}
        />
      </Sequence>

      {/* Checkmark pop */}
      <Sequence from={33 * fps}>
        <Audio
          src={staticFile("sfx/checkmark-pop.mp3")}
          volume={0.3}
        />
      </Sequence>

      {/* ===== OUTRO SCENE (36-56s) ===== */}

      {/* URL emphasis - pulsing sound */}
      <Sequence from={38 * fps}>
        <Audio
          src={staticFile("sfx/url-emphasis.mp3")}
          volume={0.5}
        />
      </Sequence>

      {/* Final chime */}
      <Sequence from={44 * fps}>
        <Audio
          src={staticFile("sfx/final-chime.mp3")}
          volume={0.4}
        />
      </Sequence>
    </>
  );
};
