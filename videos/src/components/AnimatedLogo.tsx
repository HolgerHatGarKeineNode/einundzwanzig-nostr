import { useCurrentFrame, useVideoConfig, interpolate, spring, Img, staticFile } from "remotion";

interface AnimatedLogoProps {
  delay?: number;
  size?: number;
}

// 3D Bitcoin Logo with layered depth effect - transparent version
const Bitcoin3DLogo: React.FC<{
  size: number;
  rotationY: number;
  scale: number;
  tiltX: number;
}> = ({ size, rotationY, scale, tiltX }) => {
  // Number of layers for 3D depth effect
  const layers = 6;
  const layerDepth = 2;

  // Calculate perspective shift based on rotation
  const perspectiveShiftX = Math.sin(rotationY) * 15;
  const perspectiveShiftY = Math.sin(tiltX) * 8;

  return (
    <div
      className="relative"
      style={{
        width: size,
        height: size,
        transform: `scale(${scale})`,
        transformStyle: "preserve-3d",
        perspective: "500px",
        opacity: 0.35, // Make the entire 3D Bitcoin very transparent
      }}
    >
      {/* Shadow layers (back) */}
      {Array.from({ length: layers }).map((_, i) => {
        const layerOffset = (layers - i) * layerDepth;
        const darkness = 0.4 + (i / layers) * 0.3;

        return (
          <div
            key={`shadow-${i}`}
            className="absolute inset-0 flex items-center justify-center"
            style={{
              transform: `translateX(${perspectiveShiftX + layerOffset * 0.5}px) translateY(${perspectiveShiftY + layerOffset * 0.3}px) translateZ(${-layerOffset}px)`,
              filter: `brightness(${darkness})`,
              opacity: i === 0 ? 0.5 : 0.1,
            }}
          >
            <Img
              src={staticFile("bitcoin-logo.svg")}
              style={{
                width: size * 0.85,
                height: size * 0.85,
              }}
            />
          </div>
        );
      })}

      {/* Main Bitcoin logo (front) */}
      <div
        className="absolute inset-0 flex items-center justify-center"
        style={{
          transform: `translateX(${perspectiveShiftX}px) translateY(${perspectiveShiftY}px) translateZ(0px)`,
          filter: "drop-shadow(3px 3px 6px rgba(0, 0, 0, 0.3))",
        }}
      >
        <Img
          src={staticFile("bitcoin-logo.svg")}
          style={{
            width: size * 0.85,
            height: size * 0.85,
          }}
        />
      </div>

      {/* Highlight layer (front-most) */}
      <div
        className="absolute inset-0 flex items-center justify-center pointer-events-none"
        style={{
          transform: `translateX(${perspectiveShiftX - 2}px) translateY(${perspectiveShiftY - 2}px) translateZ(5px)`,
          opacity: 0.2,
          filter: "brightness(1.5) blur(1px)",
        }}
      >
        <Img
          src={staticFile("bitcoin-logo.svg")}
          style={{
            width: size * 0.85,
            height: size * 0.85,
          }}
        />
      </div>
    </div>
  );
};

export const AnimatedLogo: React.FC<AnimatedLogoProps> = ({ delay = 0, size = 200 }) => {
  const frame = useCurrentFrame();
  const { fps } = useVideoConfig();

  // Main logo animation
  const logoSpring = spring({
    frame: frame - delay,
    fps,
    config: { damping: 15, stiffness: 80 },
  });

  const logoScale = interpolate(logoSpring, [0, 1], [0, 1]);
  const logoRotation = interpolate(logoSpring, [0, 1], [0, 360]);

  // Floating animation
  const floatY = interpolate(
    Math.sin((frame - delay) * 0.05),
    [-1, 1],
    [-10, 10]
  );

  // Glow pulse
  const glowSpring = spring({
    frame: frame - delay,
    fps,
    config: { damping: 200 },
  });
  const glowOpacity = interpolate(
    Math.sin((frame - delay) * 0.1),
    [-1, 1],
    [0.3, 0.7]
  );

  // 3D rotation for Bitcoin logo
  const bitcoinRotationY = (frame - delay) * 0.04;
  const bitcoinTiltX = Math.sin((frame - delay) * 0.03) * 0.3;

  return (
    <div className="relative" style={{ width: size, height: size }}>
      {/* Glow Effect */}
      <div
        className="absolute inset-0 blur-2xl"
        style={{
          opacity: glowOpacity * glowSpring,
          background: "radial-gradient(circle, #f7931a 0%, transparent 70%)",
          transform: `scale(${logoScale * 1.3})`,
        }}
      />

      {/* 3D Bitcoin Logo */}
      <div
        className="absolute inset-0 flex items-center justify-center"
        style={{
          transform: `translateY(${floatY}px)`,
        }}
      >
        <Bitcoin3DLogo
          size={size}
          rotationY={bitcoinRotationY}
          scale={logoScale}
          tiltX={bitcoinTiltX}
        />
      </div>

      {/* EINUNDZWANZIG Logo overlay */}
      <div
        className="absolute inset-0 flex items-center justify-center"
        style={{
          transform: `scale(${logoScale}) rotate(${logoRotation}deg) translateY(${floatY}px)`,
        }}
      >
        <Img
          src={staticFile("einundzwanzig-square-inverted.svg")}
          style={{
            width: size * 0.45,
            height: size * 0.45,
            filter: "drop-shadow(0 0 15px rgba(247, 147, 26, 0.6))",
          }}
        />
      </div>
    </div>
  );
};
