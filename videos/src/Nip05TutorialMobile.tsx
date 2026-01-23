import { AbsoluteFill, Sequence, useVideoConfig } from "remotion";
import { IntroSceneMobile } from "./scenes/mobile/IntroSceneMobile";
import { UIShowcaseSceneMobile } from "./scenes/mobile/UIShowcaseSceneMobile";
import { InputDemoSceneMobile } from "./scenes/mobile/InputDemoSceneMobile";
import { SaveButtonSceneMobile } from "./scenes/mobile/SaveButtonSceneMobile";
import { VerificationSceneMobile } from "./scenes/mobile/VerificationSceneMobile";
import { OutroSceneMobile } from "./scenes/mobile/OutroSceneMobile";
import { AudioManager } from "./components/AudioManager";
import { inconsolataFont } from "./fonts/inconsolata";

export const Nip05TutorialMobile: React.FC = () => {
  const { fps } = useVideoConfig();

  return (
    <AbsoluteFill className="bg-gradient-to-br from-zinc-900 to-zinc-800" style={{ fontFamily: inconsolataFont }}>
      {/* Audio for entire video */}
      <AudioManager />

      {/* Intro - 12 seconds (extended with registration and payment steps) */}
      <Sequence durationInFrames={12 * fps} premountFor={fps}>
        <IntroSceneMobile />
      </Sequence>

      {/* UI Showcase - 6 seconds */}
      <Sequence from={12 * fps} durationInFrames={6 * fps} premountFor={fps}>
        <UIShowcaseSceneMobile />
      </Sequence>

      {/* Input Demo - 8 seconds */}
      <Sequence from={18 * fps} durationInFrames={8 * fps} premountFor={fps}>
        <InputDemoSceneMobile />
      </Sequence>

      {/* Save Button - 5 seconds */}
      <Sequence from={26 * fps} durationInFrames={5 * fps} premountFor={fps}>
        <SaveButtonSceneMobile />
      </Sequence>

      {/* Verification - 5 seconds */}
      <Sequence from={31 * fps} durationInFrames={5 * fps} premountFor={fps}>
        <VerificationSceneMobile />
      </Sequence>

      {/* Outro - 20 seconds */}
      <Sequence from={36 * fps} durationInFrames={20 * fps} premountFor={fps}>
        <OutroSceneMobile />
      </Sequence>
    </AbsoluteFill>
  );
};
