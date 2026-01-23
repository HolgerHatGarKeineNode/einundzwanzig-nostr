import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, Img, staticFile } from "remotion";
import { BitcoinEffect } from "../components/BitcoinEffect";
import { AnimatedLogo } from "../components/AnimatedLogo";

export const SaveButtonScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  const titleSpring = spring({
    frame,
    fps,
    config: { damping: 200 },
  });
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);

  // Button entrance
  const buttonSpring = spring({
    frame: frame - 0.5 * fps,
    fps,
    config: { damping: 15, stiffness: 100 },
  });
  const buttonScale = interpolate(buttonSpring, [0, 1], [0.5, 1]);
  const buttonOpacity = interpolate(buttonSpring, [0, 1], [0, 1]);

  // Click animation
  const clickFrame = 2 * fps;
  const clickSpring = spring({
    frame: frame - clickFrame,
    fps,
    config: { damping: 10, stiffness: 200 },
    durationInFrames: 0.3 * fps,
  });
  const buttonPressScale = frame >= clickFrame ? interpolate(clickSpring, [0, 1], [0.95, 1]) : 1;

  // Success animation
  const successSpring = spring({
    frame: frame - (clickFrame + 0.5 * fps),
    fps,
    config: { damping: 200 },
  });
  const successOpacity = interpolate(successSpring, [0, 1], [0, 1]);
  const successScale = interpolate(successSpring, [0, 1], [0, 1]);

  // Cursor pointer
  const cursorSpring = spring({
    frame: frame - 1.5 * fps,
    fps,
    config: { damping: 20, stiffness: 150 },
  });
  const cursorX = interpolate(cursorSpring, [0, 1], [-200, 0]);
  const cursorY = interpolate(cursorSpring, [0, 1], [100, 0]);

  return (
    <AbsoluteFill>
      {/* Wallpaper Background */}
      <Img
        src={staticFile("einundzwanzig-wallpaper.png")}
        className="absolute inset-0 w-full h-full object-cover"
      />
      <div className="absolute inset-0 bg-neutral-950/70" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      <div className="absolute inset-0 flex flex-col items-center justify-center px-12">
        <h2
          className="text-5xl font-bold text-white mb-20 text-center"
          style={{ opacity: titleOpacity }}
        >
          Schritt 2: Handle speichern
        </h2>

        {/* Button Container */}
        <div className="relative">
          {/* Animated Cursor */}
          <div
            className="absolute text-5xl pointer-events-none z-10"
            style={{
              left: "50%",
              top: "50%",
              transform: `translate(calc(-50% + ${cursorX}px), calc(-50% + ${cursorY}px))`,
            }}
          >
            👆
          </div>

          {/* Save Button */}
          <button
            className="bg-neutral-800 text-white font-bold text-3xl px-16 py-8 rounded-xl shadow-2xl border-4 border-neutral-600"
            style={{
              opacity: buttonOpacity,
              transform: `scale(${buttonScale * buttonPressScale})`,
            }}
          >
            Speichern
          </button>

          {/* Success Message */}
          {frame >= clickFrame + 0.5 * fps && (
            <div
              className="absolute -bottom-24 left-1/2 -translate-x-1/2 whitespace-nowrap"
              style={{
                opacity: successOpacity,
                transform: `translateX(-50%) scale(${successScale})`,
              }}
            >
              <div className="bg-neutral-800 text-white px-8 py-4 rounded-lg shadow-xl flex items-center gap-4 border-2 border-neutral-600">
                <span className="text-2xl">✓</span>
                <span className="text-xl font-semibold">NIP-05 Handle gespeichert!</span>
              </div>
            </div>
          )}
        </div>

        {/* 3D Success Logo */}
        {frame >= clickFrame + 0.5 * fps && (
          <div className="absolute top-20 right-20">
            <AnimatedLogo size={280} delay={clickFrame + 0.5 * fps} />
          </div>
        )}

        <p className="text-neutral-300 text-center mt-32 text-xl max-w-2xl" style={{ opacity: titleOpacity }}>
          Nach dem Speichern wird dein Handle in Kürze automatisch aktiviert.
        </p>
      </div>
    </AbsoluteFill>
  );
};
