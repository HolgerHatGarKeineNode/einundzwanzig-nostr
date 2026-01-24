import "./index.css";
import { Composition, Folder } from "remotion";
import { MyComposition } from "./Composition";
import { Nip05Tutorial } from "./Nip05Tutorial";
import { Nip05TutorialMobile } from "./Nip05TutorialMobile";
import { PortalPresentation } from "./PortalPresentation";

export const RemotionRoot: React.FC = () => {
  return (
    <>
      <Composition
        id="MyComp"
        component={MyComposition}
        durationInFrames={60}
        fps={30}
        width={1280}
        height={720}
      />
      <Folder name="NIP-05-Tutorial">
        <Composition
          id="Nip05Tutorial"
          component={Nip05Tutorial}
          durationInFrames={56 * 30}
          fps={30}
          width={1920}
          height={1080}
        />
        <Composition
          id="Nip05TutorialMobile"
          component={Nip05TutorialMobile}
          durationInFrames={56 * 30}
          fps={30}
          width={1080}
          height={1920}
        />
      </Folder>
      <Folder name="Portal">
        <Composition
          id="PortalPresentation"
          component={PortalPresentation}
          durationInFrames={90 * 30}
          fps={30}
          width={1920}
          height={1080}
        />
      </Folder>
    </>
  );
};
