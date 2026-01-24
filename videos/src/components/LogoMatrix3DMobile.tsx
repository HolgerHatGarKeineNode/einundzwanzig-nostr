import {
  useCurrentFrame,
  useVideoConfig,
  interpolate,
  spring,
  Img,
  staticFile,
  Easing,
} from "remotion";
import { useMemo } from "react";

// Seeded random for consistent renders
function seededRandom(seed: number): () => number {
  return () => {
    seed = (seed * 9301 + 49297) % 233280;
    return seed / 233280;
  };
}

// All meetup logos from public/logos (same as desktop)
const MEETUP_LOGOS = [
  "21BanskaBystrica.svg",
  "21BissendorfEst798421.jpg",
  "21BitcoinMeetupZypern.jpg",
  "21ElsenburgKaubAmRhein.png",
  "21Giessen.jpg",
  "21Levice.svg",
  "21MeetupPaphosZypern.jpg",
  "21Neumarkt.jpeg",
  "21ZitadelleUckermark.jpg",
  "32EinezwanzgSeeland.jpg",
  "Aschaffenburg.png",
  "AshevilleBitcoiners.jpg",
  "BadenBitcoinClub.png",
  "BGBTCMeetUp.png",
  "BielefelderBitcoiner.jpg",
  "Bitcoin21.jpeg",
  "BitcoinAlps.jpg",
  "BitcoinAustria.jpg",
  "BitcoinBeachLubeckTravemunde.png",
  "BitcoinDresden.jpeg",
  "BitcoinersBulgaria.png",
  "BitcoinMagdeburg.png",
  "BitcoinMeetUpChiemseeChiemgau.jpg",
  "BitcoinMeetupEinundzwanzigPotsdam.jpg",
  "BitcoinMeetupHalleSaale.jpg",
  "BitcoinMeetupHarz.jpg",
  "BitcoinMeetupJever.png",
  "BitcoinMeetupSchwerinEinundzwanzig.jpg",
  "BitcoinMeetupZurich.jpg",
  "BitcoinMunchen.jpg",
  "BitcoinOnlyMeetupInBulgaria.jpg",
  "BitcoinTalkNetwork.png",
  "BitcoinTenerifePuertoDeLaCruz.png",
  "BitcoinWalkHamburg.jpg",
  "BitcoinWalkKoln.jpeg",
  "BitcoinWalkWurzburg.jpg",
  "BitcoinWallis.jpeg",
  "BitcoinWesterwald.jpg",
  "Bocholt21.jpeg",
  "BTCSchwyz.png",
  "BTCStammtischOberfranken.jpg",
  "Bussen.png",
  "CharlestonBitcoinMeetup.jpg",
  "DwadziesciaJedenKrakow.png",
  "DwadziesciaJedenPoznan.png",
  "DwadziesciaJedenWarszawa.png",
  "DwadziesciaJedenWroclaw.png",
  "DwadzisciaJedynKatowice.png",
  "EenanzwanzegLetzebuerg.png",
  "EinundzwanigWandernChiemgauBerchtesgarden.jpg",
  "Einundzwanzig3LanderEck.png",
  "EinundzwanzigAachen.jpg",
  "EinundzwanzigAlsfeld.jpg",
  "EinundzwanzigAmbergSulzbach.png",
  "EinundzwanzigAmstetten.png",
  "EinundzwanzigAnsbach.png",
  "EinundzwanzigAusserfern.jpg",
  "EinundzwanzigAutobahnA9.png",
  "EinundzwanzigBadIburg.jpg",
  "EinundzwanzigBadKissingen.png",
  "EinundzwanzigBasel.png",
  "EinundzwanzigBeckum.png",
  "EinundzwanzigBensheim.png",
  "EinundzwanzigBerlin.jpg",
  "EinundzwanzigBerlinNord.png",
  "EinundzwanzigBielBienne.jpg",
  "EinundzwanzigBitburg.jpg",
  "EINUNDZWANZIGBochum.jpg",
  "EinundzwanzigBonn.jpg",
  "EinundzwanzigBraunau.jpeg",
  "EinundzwanzigBraunschweig.jpg",
  "EinundzwanzigBremen.jpg",
  "EinundzwanzigBremerhaven.png",
  "EinundzwanzigBruhl.png",
  "EinundzwanzigCelleResidenzstadtMeetup.jpg",
  "EinundzwanzigCham.png",
  "EinundzwanzigDarmstadt.png",
  "EinundzwanzigDetmoldLippe.png",
  "EinundzwanzigDingolfingLandau.jpg",
  "EinundzwanzigDortmund.jpg",
  "EinundzwanzigElbeElster.PNG",
  "EinundzwanzigEllwangen.jpg",
  "EinundzwanzigErding.png",
  "EinundzwanzigErfurt.png",
  "EinundzwanzigEschenbach.jpg",
  "EinundzwanzigEssen.jpg",
  "EinundzwanzigFehmarn.jpg",
  "EinundzwanzigFranken.jpg",
  "EinundzwanzigFrankfurtAmMain.png",
  "EinundzwanzigFreising.PNG",
  "EinundzwanzigFriedrichshafen.png",
  "EinundzwanzigFulda.png",
  "EinundzwanzigGarmischPartenkirchen.png",
  "EinundzwanzigGastein.png",
  "EinundzwanzigGelnhausen.jpg",
  "EINUNDZWANZIGGelsenkirchen.png",
  "EinundzwanzigGiessen.jpg",
  "EinundzwanzigGrenzland.jpg",
  "EinundzwanzigGrunstadt.jpg",
  "EinundzwanzigGummersbach.png",
  "EinundzwanzigHamburg.jpg",
  "EinundzwanzigHameln.png",
  "EinundzwanzigHannover.png",
  "EinundzwanzigHeilbronn.jpg",
  "EinundzwanzigHennef.jpg",
  "EinundzwanzigHildesheim.jpg",
  "EinundzwanzigHochschwarzwald.png",
  "EinundzwanzigHuckelhoven.jpg",
  "EinundzwanzigIngolstadt.png",
  "EinundzwanzigJena.png",
  "EinundzwanzigKasselBitcoin.jpg",
  "EinundzwanzigKempten.jpg",
  "EinundzwanzigKiel.jpg",
  "EinundzwanzigKirchdorfOO.jpg",
  "EinundzwanzigKoblenz.jpg",
  "EinundzwanzigKonstanz.jpg",
  "EinundzwanzigLandau.jpg",
  "EinundzwanzigLandshut.png",
  "EinundzwanzigLangen.png",
  "EinundzwanzigLechrain.png",
  "EINUNDZWANZIGLEIPZIG.jpg",
  "EinundzwanzigLimburg.jpg",
  "EinundzwanzigLingen.jpg",
  "EinundzwanzigLinz.jpg",
  "EinundzwanzigLubeck.jpg",
  "EinundzwanzigLudwigsburg.jpg",
  "EinundzwanzigLuzern.jpg",
  "EinundzwanzigMainz.jpg",
  "EinundzwanzigMannheim.jpg",
  "EinundzwanzigMarburg.jpg",
  "EinundzwanzigMeetupAltotting.png",
  "EinundzwanzigMeetupDusseldorf.jpg",
  "EinundzwanzigMeetupMuhldorfAmInn.png",
  "EinundzwanzigMeetupPfaffikonSZ.png",
  "EINUNDZWANZIGMeetupWieselburg.jpg",
  "EinundzwanzigMemmingen.jpg",
  "EinundzwanzigMoers.jpg",
  "EinundzwanzigMonchengladbach.jpg",
  "EINUNDZWANZIGMunchen.jpg",
  "EinundzwanzigMunster.png",
  "EinundzwanzigNeubrandenburg.jpeg",
  "EinundzwanzigNiederrhein.jpg",
  "EinundzwanzigNordburgenland.jpg",
  "EinundzwanzigNorderstedt.jpg",
  "EinundzwanzigNordhausen.png",
  "EinundzwanzigOberland.jpg",
  "EinundzwanzigOdenwald.jpg",
  "EinundzwanzigOldenburg.jpg",
  "EinundzwanzigOrtenaukreisOffenburg.jpg",
  "EinundzwanzigOstBrandenburg.png",
  "EinundzwanzigOstBrandenburgAltlandsberg.jpg",
  "EinundzwanzigOstschweiz.jpg",
  "EinundzwanzigOsttirol.jpg",
  "EinundzwanzigOWL.jpeg",
  "EinundzwanzigPeine.png",
  "EinundzwanzigPfalz.jpg",
  "EinundzwanzigPfarrkirchenRottalInn.jpg",
  "EinundzwanzigPforzheim.jpg",
  "EinundzwanzigRegensburg.png",
  "EinundzwanzigRemstal.jpg",
  "EinundzwanzigReutlingen.png",
  "EinundzwanzigRheinhessen.png",
  "EinundzwanzigRheinischBergischerKreis.png",
  "EinundzwanzigRinteln.png",
  "EinundzwanzigRohrbach.png",
  "EinundzwanzigRostock.jpg",
  "EinundzwanzigRothenburgObDerTauber.jpg",
  "EinundzwanzigRothSchwabachWeissenburg.jpeg",
  "EinundzwanzigRottweil.jpg",
  "EinundzwanzigRugen.png",
  "EinundzwanzigSaarbrucken.png",
  "EinundzwanzigSaarland.jpg",
  "EinundzwanzigSaarlouis.jpg",
  "EinundzwanzigSalzburg.jpg",
  "EinundzwanzigSauerland.jpeg",
  "EinundzwanzigSchafstall.jpg",
  "EinundzwanzigScharding.jpg",
  "EinundzwanzigSchwarzwaldBaar.jpg",
  "EinundzwanzigSchweden.png",
  "EinundzwanzigSchweinfurt.png",
  "EinundzwanzigSeelze.png",
  "EinundzwanzigSigmaringen.JPG",
  "EinundzwanzigSolingen.jpg",
  "EinundzwanzigSpeyer.png",
  "EinundzwanzigSpreewald.jpg",
  "EinundzwanzigStarnbergBitcoinMeetup.jpg",
  "EinundzwanzigStormarn.png",
  "EinundzwanzigStrohgau.png",
  "EinundzwanzigStuttgart.jpg",
  "EinundzwanzigStyria.jpg",
  "EinundzwanzigSudniedersachsen.png",
  "EinundzwanzigSudtirol.jpg",
  "EinundzwanzigThurgau.jpg",
  "EinundzwanzigTirol.png",
  "EinundzwanzigTrier.jpg",
  "EinundzwanzigUelzen.png",
  "EinundzwanzigUlm.jpg",
  "EinundzwanzigUndLibertarePassau.png",
  "EinundzwanzigVillingenSchwenningen.png",
  "EinundzwanzigVorarlberg.jpg",
  "EinundzwanzigVulkaneifel.jpg",
  "EinundzwanzigWaldenrath.jpg",
  "EinundzwanzigWeiden.jpg",
  "EinundzwanzigWestmunsterland.jpg",
  "EinundzwanzigWetterau.png",
  "EinundzwanzigWien.png",
  "EinundzwanzigWiesbaden.png",
  "EinundzwanzigWinterthurBITCOINWINTI.png",
  "EinundzwanzigZollernAlbKreisBalingen.png",
  "Flensburg.png",
  "HerBitcoinLaPalma.jpg",
  "KalmarBitcoinMeetUp.png",
  "KirchheimTeck.jpg",
  "MagicCityBitcoin.png",
  "MicroMeetUpPragerPlatz.png",
  "MittwochMountainMeetup.jpg",
  "MKEinundzwanzig.jpg",
  "Munchberg.jpg",
  "NostrMeetup.jpg",
  "RabbitBitcoinClubMagdeburg.png",
  "ReichenbachAnDerFils.jpg",
  "SatoshisCoffeeshop.jpg",
  "Sylt.jpg",
  "TjugoettStockholm.jpg",
  "TWENTYONEUSA.png",
  "VINTEEUMFunchal.jpg",
  "Wurzburg.png",
  "Zeitz.png",
  "ZollernalbBalingen.png",
];

// Extract readable meetup name from filename
function extractMeetupName(filename: string): string {
  const nameWithoutExt = filename.replace(/\.(svg|jpg|jpeg|png|PNG|JPG|jfif|JPG)$/i, "");
  return nameWithoutExt
    .replace(/([a-z])([A-Z])/g, "$1 $2")
    .replace(/([A-Z]+)([A-Z][a-z])/g, "$1 $2")
    .replace(/^(Einundzwanzig|EINUNDZWANZIG|21|Bitcoin)/i, "")
    .replace(/Meetup/gi, "")
    .replace(/^[\s-]+|[\s-]+$/g, "")
    .trim() || nameWithoutExt;
}

interface LogoPosition {
  x: number;
  y: number;
  z: number;
  rotateX: number;
  rotateY: number;
  rotateZ: number;
  scale: number;
  logo: string;
  name: string;
  delay: number;
}

interface SpotlightMeetup {
  logo: string;
  name: string;
  startFrame: number;
  duration: number;
}

/**
 * Mobile-optimized 3D Logo Matrix
 * - Portrait layout (1080x1920)
 * - Smaller logo cards
 * - Adjusted camera movements for vertical format
 */
export const LogoMatrix3DMobile: React.FC = () => {
  const frame = useCurrentFrame();
  const { fps, width, height } = useVideoConfig();

  // Animation phases (in seconds)
  const PHASE = {
    MATRIX_FORM: { start: 0, end: 4 },
    CAMERA_FLIGHT: { start: 4, end: 16 },
    SPOTLIGHTS: { start: 16, end: 26 },
    CONVERGENCE: { start: 26, end: 30 },
  };

  // Generate deterministic logo positions - optimized for portrait
  const logoPositions = useMemo<LogoPosition[]>(() => {
    const random = seededRandom(21);
    const positions: LogoPosition[] = [];
    const gridCols = 8;
    const spacingX = 140;
    const spacingY = 180;
    const halfCols = (gridCols - 1) / 2;

    MEETUP_LOGOS.forEach((logo, index) => {
      const gridX = index % gridCols;
      const gridY = Math.floor(index / gridCols);

      positions.push({
        x: (gridX - halfCols) * spacingX + (random() - 0.5) * 40,
        y: (gridY - 7) * spacingY + (random() - 0.5) * 50,
        z: (random() - 0.5) * 600 - 300,
        rotateX: (random() - 0.5) * 25,
        rotateY: (random() - 0.5) * 25,
        rotateZ: (random() - 0.5) * 12,
        scale: 0.5 + random() * 0.35,
        logo,
        name: extractMeetupName(logo),
        delay: random() * 25,
      });
    });

    return positions;
  }, []);

  // Select random meetups for spotlight reveals
  const spotlightMeetups = useMemo<SpotlightMeetup[]>(() => {
    const random = seededRandom(42);
    const shuffled = [...logoPositions].sort(() => random() - 0.5);
    const selected = shuffled.slice(0, 6);
    const spotlightDuration = 2.5 * fps;
    const startFrame = PHASE.SPOTLIGHTS.start * fps;

    return selected.map((pos, index) => ({
      logo: pos.logo,
      name: pos.name,
      startFrame: startFrame + index * Math.floor(spotlightDuration * 0.4),
      duration: spotlightDuration,
    }));
  }, [fps, logoPositions]);

  // Camera movement - vertical sweep for portrait
  // Note: Keyframes must be strictly monotonically increasing
  const cameraZ = interpolate(
    frame,
    [
      0,                              // Start
      PHASE.MATRIX_FORM.end * fps,    // 120 - Matrix formed
      PHASE.CAMERA_FLIGHT.end * fps,  // 480 - Flight done
      PHASE.CONVERGENCE.start * fps,  // 780 - Start convergence
      PHASE.CONVERGENCE.end * fps,    // 900 - End
    ],
    [1800, 1000, -400, -400, 600],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  const cameraY = interpolate(
    frame,
    [
      PHASE.CAMERA_FLIGHT.start * fps,
      PHASE.CAMERA_FLIGHT.start * fps + 4 * fps,
      PHASE.CAMERA_FLIGHT.start * fps + 8 * fps,
      PHASE.CAMERA_FLIGHT.end * fps,
    ],
    [0, -400, 350, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  const cameraX = interpolate(
    frame,
    [
      PHASE.CAMERA_FLIGHT.start * fps,
      PHASE.CAMERA_FLIGHT.start * fps + 6 * fps,
      PHASE.CAMERA_FLIGHT.end * fps,
    ],
    [0, 150, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  const cameraRotateX = interpolate(
    frame,
    [
      0,                              // Start
      PHASE.MATRIX_FORM.end * fps,    // 120
      PHASE.CAMERA_FLIGHT.end * fps,  // 480
    ],
    [20, 8, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  // Matrix formation progress
  const formationProgress = spring({
    frame,
    fps,
    config: { damping: 80, stiffness: 30 },
  });

  // Convergence animation
  const convergenceProgress = interpolate(
    frame,
    [PHASE.CONVERGENCE.start * fps, PHASE.CONVERGENCE.end * fps],
    [0, 1],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp", easing: Easing.inOut(Easing.cubic) }
  );

  // Check if a spotlight is active
  const getActiveSpotlight = () => {
    return spotlightMeetups.find(
      (s) => frame >= s.startFrame && frame < s.startFrame + s.duration
    );
  };

  const activeSpotlight = getActiveSpotlight();

  // Spotlight animation
  const spotlightSpring = activeSpotlight
    ? spring({
        frame: frame - activeSpotlight.startFrame,
        fps,
        config: { damping: 12, stiffness: 80 },
      })
    : 0;

  const spotlightScale = interpolate(spotlightSpring, [0, 0.5, 1], [1, 2.2, 2]);
  const spotlightOpacity = interpolate(
    frame - (activeSpotlight?.startFrame || 0),
    [0, 15, (activeSpotlight?.duration || 60) - 15, activeSpotlight?.duration || 60],
    [0, 1, 1, 0],
    { extrapolateLeft: "clamp", extrapolateRight: "clamp" }
  );

  // Global matrix glow intensity
  const glowPulse = interpolate(
    Math.sin(frame * 0.05),
    [-1, 1],
    [0.3, 0.7]
  );

  return (
    <div
      className="absolute inset-0 overflow-hidden"
      style={{
        perspective: 1500,
        perspectiveOrigin: "50% 50%",
      }}
    >
      {/* 3D Scene Container */}
      <div
        className="absolute"
        style={{
          width: "100%",
          height: "100%",
          transformStyle: "preserve-3d",
          transform: `
            translateZ(${cameraZ}px)
            translateX(${cameraX}px)
            translateY(${cameraY}px)
            rotateX(${cameraRotateX}deg)
          `,
        }}
      >
        {/* Logo Grid */}
        {logoPositions.map((pos, index) => {
          const isSpotlit = activeSpotlight?.logo === pos.logo;
          const entryDelay = pos.delay;

          // Individual logo entrance
          const logoEntrance = spring({
            frame: frame - entryDelay,
            fps,
            config: { damping: 20, stiffness: 50 },
          });

          // Floating animation
          const floatY = Math.sin((frame + index * 17) * 0.03) * 12;
          const floatRotate = Math.sin((frame + index * 23) * 0.02) * 4;

          // Calculate final position (convergence to center)
          const finalX = interpolate(convergenceProgress, [0, 1], [pos.x, 0]);
          const finalY = interpolate(convergenceProgress, [0, 1], [pos.y, 0]);
          const finalZ = interpolate(convergenceProgress, [0, 1], [pos.z, 0]);
          const finalScale = interpolate(
            convergenceProgress,
            [0, 1],
            [pos.scale, 0.08]
          );
          const finalOpacity = interpolate(convergenceProgress, [0, 0.7, 1], [1, 1, 0]);

          // Distance from camera for depth fog
          const distance = Math.sqrt(finalX ** 2 + finalY ** 2 + (finalZ - cameraZ) ** 2);
          const fogOpacity = interpolate(distance, [0, 1200, 2500], [1, 0.6, 0.2], {
            extrapolateLeft: "clamp",
            extrapolateRight: "clamp",
          });

          return (
            <div
              key={pos.logo}
              className="absolute"
              style={{
                left: width / 2,
                top: height / 2,
                transformStyle: "preserve-3d",
                transform: `
                  translate3d(${finalX}px, ${finalY + floatY}px, ${finalZ}px)
                  rotateX(${pos.rotateX + floatRotate}deg)
                  rotateY(${pos.rotateY}deg)
                  rotateZ(${pos.rotateZ}deg)
                  scale(${finalScale * logoEntrance * formationProgress})
                `,
                opacity: fogOpacity * logoEntrance * finalOpacity,
                zIndex: isSpotlit ? 1000 : Math.round(1000 - pos.z),
              }}
            >
              {/* Logo Card - smaller for mobile */}
              <div
                className="relative"
                style={{
                  width: 90,
                  height: 90,
                  marginLeft: -45,
                  marginTop: -45,
                  borderRadius: 10,
                  background: "rgba(24, 24, 27, 0.9)",
                  boxShadow: `
                    0 0 ${16 * glowPulse}px rgba(247, 147, 26, ${0.3 * glowPulse}),
                    0 3px 15px rgba(0, 0, 0, 0.5)
                  `,
                  border: "1px solid rgba(247, 147, 26, 0.3)",
                  overflow: "hidden",
                  transform: isSpotlit ? `scale(${spotlightScale})` : "scale(1)",
                  transition: "transform 0.1s",
                }}
              >
                <Img
                  src={staticFile(`logos/${pos.logo}`)}
                  style={{
                    width: "100%",
                    height: "100%",
                    objectFit: "cover",
                  }}
                />
                {/* Glow overlay */}
                <div
                  className="absolute inset-0"
                  style={{
                    background: `radial-gradient(circle, rgba(247, 147, 26, ${0.15 * glowPulse}) 0%, transparent 70%)`,
                  }}
                />
              </div>
            </div>
          );
        })}
      </div>

      {/* Spotlight Meetup Name Display - positioned for mobile */}
      {activeSpotlight && (
        <div
          className="absolute inset-x-0 bottom-48 flex flex-col items-center justify-center px-6"
          style={{ opacity: spotlightOpacity }}
        >
          <div
            className="px-6 py-3 rounded-xl"
            style={{
              background: "rgba(24, 24, 27, 0.95)",
              boxShadow: "0 0 50px rgba(247, 147, 26, 0.4), 0 8px 30px rgba(0, 0, 0, 0.5)",
              border: "2px solid rgba(247, 147, 26, 0.6)",
              transform: `scale(${spotlightSpring})`,
            }}
          >
            <h2
              className="text-2xl font-bold text-white text-center"
              style={{
                textShadow: "0 0 25px rgba(247, 147, 26, 0.8)",
              }}
            >
              {activeSpotlight.name}
            </h2>
          </div>
        </div>
      )}

      {/* Ambient particles - fewer for mobile */}
      {Array.from({ length: 20 }).map((_, i) => {
        const random = seededRandom(i + 100);
        const particleX = random() * width;
        const particleY = (random() * height + frame * (0.5 + random() * 1)) % (height + 100) - 50;
        const particleSize = 2 + random() * 3;
        const particleOpacity = 0.3 + random() * 0.4;

        return (
          <div
            key={`particle-${i}`}
            className="absolute rounded-full"
            style={{
              left: particleX,
              top: particleY,
              width: particleSize,
              height: particleSize,
              background: `rgba(247, 147, 26, ${particleOpacity})`,
              boxShadow: `0 0 ${particleSize * 3}px rgba(247, 147, 26, ${particleOpacity * 0.5})`,
            }}
          />
        );
      })}

      {/* Vignette */}
      <div
        className="absolute inset-0 pointer-events-none"
        style={{
          boxShadow: "inset 0 0 200px 80px rgba(0, 0, 0, 0.8)",
        }}
      />
    </div>
  );
};
