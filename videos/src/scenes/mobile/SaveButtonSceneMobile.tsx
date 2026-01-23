import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, staticFile } from "remotion";
import { BitcoinEffect } from "../../components/BitcoinEffect";
import { AnimatedLogo } from "../../components/AnimatedLogo";

export const SaveButtonSceneMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width } = useVideoConfig();

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
  const buttonPressScale = frame >= clickFrame ? interpolate(clickSpring, [0, 1], [0.92, 1]) : 1;

  // Success animation
  const successSpring = spring({
    frame: frame - (clickFrame + 0.5 * fps),
    fps,
    config: { damping: 200 },
  });
  const successOpacity = interpolate(successSpring, [0, 1], [0, 1]);
  const successScale = interpolate(successSpring, [0, 1], [0, 1]);

  // Cursor pointer - from left
  const cursorSpring = spring({
    frame: frame - 1.5 * fps,
    fps,
    config: { damping: 20, stiffness: 150 },
  });
  const cursorX = interpolate(cursorSpring, [0, 1], [-200, 0]);
  const cursorOpacity = interpolate(cursorSpring, [0, 1], [0, 1]);

  return (
    <AbsoluteFill>
      {/* Tiled Wallpaper Background */}
      <div
        className="absolute inset-0"
        style={{
          backgroundImage: `url(${staticFile("einundzwanzig-wallpaper.png")})`,
          backgroundSize: `${width}px auto`,
          backgroundRepeat: "repeat-y",
          backgroundPosition: "center top",
        }}
      />
      <div className="absolute inset-0 bg-neutral-950/75" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      <div className="absolute inset-0 flex flex-col items-center justify-center px-8">
        <h2
          className="text-5xl font-bold text-white mb-24 text-center"
          style={{ opacity: titleOpacity }}
        >
          Schritt 2: Handle speichern
        </h2>

        {/* Button Container */}
        <div className="relative">
          {/* Animated Cursor - from left */}
          <div
            className="absolute text-8xl pointer-events-none z-10"
            style={{
              left: "-120px",
              top: "50%",
              transform: `translateY(-50%) translateX(${cursorX}px)`,
              opacity: cursorOpacity,
            }}
          >
            👉
          </div>

          {/* Save Button - Much Larger */}
          <button
            className="bg-neutral-800 text-white font-bold text-5xl px-24 py-10 rounded-2xl shadow-2xl border-4 border-neutral-600"
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
              className="absolute -bottom-32 left-1/2 -translate-x-1/2 whitespace-nowrap"
              style={{
                opacity: successOpacity,
                transform: `translateX(-50%) scale(${successScale})`,
              }}
            >
              <div className="bg-neutral-800 text-white px-10 py-6 rounded-2xl shadow-xl flex items-center gap-5 border-2 border-neutral-600">
                <span className="text-4xl">✓</span>
                <span className="text-3xl font-semibold">NIP-05 Handle gespeichert!</span>
              </div>
            </div>
          )}
        </div>

        {/* 3D Success Logo */}
        {frame >= clickFrame + 0.5 * fps && (
          <div className="absolute top-32 right-8">
            <AnimatedLogo size={200} delay={clickFrame + 0.5 * fps} />
          </div>
        )}

        <p className="text-neutral-200 text-center mt-48 text-3xl px-4 font-medium" style={{ opacity: titleOpacity }}>
          Nach dem Speichern wird dein Handle in Kürze automatisch aktiviert.
        </p>
      </div>
    </AbsoluteFill>
  );
};
