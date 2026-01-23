import { AbsoluteFill, interpolate, spring, useCurrentFrame, useVideoConfig, Img, staticFile } from "remotion";
import { ThreeCanvas } from "@remotion/three";
import { BitcoinEffect } from "../components/BitcoinEffect";
import { AnimatedLogo } from "../components/AnimatedLogo";

export const UIShowcaseScene: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width, height } = useVideoConfig();

  const cardSpring = spring({
    frame,
    fps,
    config: { damping: 20, stiffness: 200 },
  });

  const cardScale = interpolate(cardSpring, [0, 1], [0.8, 1]);
  const cardOpacity = interpolate(cardSpring, [0, 1], [0, 1]);

  // 3D background elements
  const bgRotation = frame * 0.01;

  const titleSpring = spring({
    frame: frame - 0.3 * fps,
    fps,
    config: { damping: 200 },
  });
  const titleY = interpolate(titleSpring, [0, 1], [50, 0]);
  const titleOpacity = interpolate(titleSpring, [0, 1], [0, 1]);

  return (
    <AbsoluteFill>
      {/* Wallpaper Background */}
      <Img
        src={staticFile("einundzwanzig-wallpaper.png")}
        className="absolute inset-0 w-full h-full object-cover"
      />
      <div className="absolute inset-0 bg-neutral-950/80" />

      {/* Bitcoin Effect */}
      <BitcoinEffect />

      {/* Animated Logos in corners */}
      <div className="absolute top-10 left-10 opacity-50">
        <AnimatedLogo size={150} delay={0.2 * fps} />
      </div>
      <div className="absolute top-10 right-10 opacity-50">
        <AnimatedLogo size={150} delay={0.4 * fps} />
      </div>

      {/* 3D Background */}
      <div className="absolute inset-0 opacity-20">
        <ThreeCanvas width={width} height={height}>
          <ambientLight intensity={0.3} />
          <directionalLight position={[5, 5, 5]} intensity={0.6} />
          <mesh rotation={[0, bgRotation, 0]} position={[-3, 0, 0]}>
            <sphereGeometry args={[1.5, 32, 32]} />
            <meshStandardMaterial color="#f7931a" wireframe />
          </mesh>
          <mesh rotation={[0, -bgRotation, 0]} position={[3, 0, 0]}>
            <octahedronGeometry args={[1.5]} />
            <meshStandardMaterial color="#f7931a" wireframe />
          </mesh>
        </ThreeCanvas>
      </div>

      {/* Main Content */}
      <div className="absolute inset-0 flex flex-col items-center justify-center px-20">
        <h2
          className="text-5xl font-bold text-white mb-12 text-center"
          style={{
            opacity: titleOpacity,
            transform: `translateY(${titleY}px)`,
          }}
        >
          Die NIP-05 Oberfläche
        </h2>

        {/* UI Card Mockup */}
        <div
          className="bg-neutral-50 rounded-xl p-8 border-4 border-neutral-200 shadow-2xl max-w-2xl w-full"
          style={{
            opacity: cardOpacity,
            transform: `scale(${cardScale})`,
          }}
        >
          <div className="flex items-start gap-4 mb-6">
            <div className="w-12 h-12 rounded-full bg-neutral-800 flex items-center justify-center flex-shrink-0">
              <div className="text-white text-2xl">✓</div>
            </div>
            <div className="flex-1">
              <h3 className="text-2xl font-semibold text-neutral-800 mb-2">
                Get NIP-05 verified
              </h3>
              <p className="text-base text-neutral-600">
                Verifiziere deine Identität mit einem menschenlesbaren Nostr-Namen.
              </p>
            </div>
          </div>

          {/* Input Preview */}
          <div className="space-y-3">
            <label className="block text-sm font-medium text-neutral-700">
              Dein NIP-05 Handle
            </label>
            <div className="flex items-center bg-white rounded-lg border-2 border-neutral-300 px-4 py-3">
              <div className="flex-1 text-neutral-400">dein-name</div>
              <div className="text-neutral-600 font-medium">@einundzwanzig.space</div>
            </div>
          </div>
        </div>
      </div>
    </AbsoluteFill>
  );
};
