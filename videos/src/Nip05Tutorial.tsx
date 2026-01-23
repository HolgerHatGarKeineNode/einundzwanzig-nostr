import { AbsoluteFill, Sequence, useVideoConfig } from "remotion";
import { IntroScene } from "./scenes/IntroScene";
import { UIShowcaseScene } from "./scenes/UIShowcaseScene";
import { InputDemoScene } from "./scenes/InputDemoScene";
import { SaveButtonScene } from "./scenes/SaveButtonScene";
import { VerificationScene } from "./scenes/VerificationScene";
import { OutroScene } from "./scenes/OutroScene";
import { AudioManager } from "./components/AudioManager";
import { inconsolataFont } from "./fonts/inconsolata";

export const Nip05Tutorial: React.FC = () => {
  const { fps } = useVideoConfig();

  return (
    <AbsoluteFill className="bg-gradient-to-br from-zinc-900 to-zinc-800" style={{ fontFamily: inconsolataFont }}>
      {/* Audio for entire video */}
      <AudioManager />

      {/* Intro - 12 seconds (extended with registration and payment steps) */}
      <Sequence durationInFrames={12 * fps} premountFor={fps}>
        <IntroScene />
      </Sequence>

      {/* UI Showcase - 6 seconds */}
      <Sequence from={12 * fps} durationInFrames={6 * fps} premountFor={fps}>
        <UIShowcaseScene />
      </Sequence>

      {/* Input Demo - 8 seconds */}
      <Sequence from={18 * fps} durationInFrames={8 * fps} premountFor={fps}>
        <InputDemoScene />
      </Sequence>

      {/* Save Button - 5 seconds */}
      <Sequence from={26 * fps} durationInFrames={5 * fps} premountFor={fps}>
        <SaveButtonScene />
      </Sequence>

      {/* Verification - 5 seconds */}
      <Sequence from={31 * fps} durationInFrames={5 * fps} premountFor={fps}>
        <VerificationScene />
      </Sequence>

      {/* Outro - 20 seconds */}
      <Sequence from={36 * fps} durationInFrames={20 * fps} premountFor={fps}>
        <OutroScene />
      </Sequence>
    </AbsoluteFill>
  );
};
