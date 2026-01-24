import { AbsoluteFill, Sequence, useVideoConfig, Img, staticFile } from "remotion";
import { inconsolataFont } from "./fonts/inconsolata";
import { PortalIntroSceneMobile } from "./scenes/portal/mobile/PortalIntroSceneMobile";
import { PortalTitleSceneMobile } from "./scenes/portal/mobile/PortalTitleSceneMobile";
import { DashboardOverviewSceneMobile } from "./scenes/portal/mobile/DashboardOverviewSceneMobile";
import { MeetupShowcaseSceneMobile } from "./scenes/portal/mobile/MeetupShowcaseSceneMobile";
import { CountryStatsSceneMobile } from "./scenes/portal/mobile/CountryStatsSceneMobile";
import { TopMeetupsSceneMobile } from "./scenes/portal/mobile/TopMeetupsSceneMobile";
import { ActivityFeedSceneMobile } from "./scenes/portal/mobile/ActivityFeedSceneMobile";
import { CallToActionSceneMobile } from "./scenes/portal/mobile/CallToActionSceneMobile";
import { PortalOutroSceneMobile } from "./scenes/portal/mobile/PortalOutroSceneMobile";
import { PortalAudioManager } from "./components/PortalAudioManager";

/**
 * PortalPresentationMobile - Mobile composition for the Einundzwanzig Portal presentation video
 *
 * Resolution: 1080x1920 (9:16 portrait)
 *
 * Scene Structure (108 seconds total @ 30fps = 3240 frames):
 * 1. Logo Reveal (6s) - Frames 0-180
 * 2. Portal Title (4s) - Frames 180-300
 * 3. Dashboard Overview (12s) - Frames 300-660
 * 4. Meine Meetups (12s) - Frames 660-1020
 * 5. Top Länder (12s) - Frames 1020-1380
 * 6. Top Meetups (10s) - Frames 1380-1680
 * 7. Activity Feed (10s) - Frames 1680-1980
 * 8. Call to Action (12s) - Frames 1980-2340
 * 9. Outro - Cinematic Logo Matrix (30s) - Frames 2340-3240
 */
export const PortalPresentationMobile: React.FC = () => {
  const { fps } = useVideoConfig();

  // Scene durations in seconds (same as desktop)
  const SCENE_DURATIONS = {
    logoReveal: 6,
    portalTitle: 4,
    dashboardOverview: 12,
    meineMeetups: 12,
    topLaender: 12,
    topMeetups: 10,
    activityFeed: 10,
    callToAction: 12,
    outro: 30, // Extended for cinematic logo matrix animation
  };

  // Calculate frame positions for each scene
  const sceneFrames = {
    logoReveal: { from: 0, duration: SCENE_DURATIONS.logoReveal * fps },
    portalTitle: {
      from: SCENE_DURATIONS.logoReveal * fps,
      duration: SCENE_DURATIONS.portalTitle * fps,
    },
    dashboardOverview: {
      from: (SCENE_DURATIONS.logoReveal + SCENE_DURATIONS.portalTitle) * fps,
      duration: SCENE_DURATIONS.dashboardOverview * fps,
    },
    meineMeetups: {
      from:
        (SCENE_DURATIONS.logoReveal +
          SCENE_DURATIONS.portalTitle +
          SCENE_DURATIONS.dashboardOverview) *
        fps,
      duration: SCENE_DURATIONS.meineMeetups * fps,
    },
    topLaender: {
      from:
        (SCENE_DURATIONS.logoReveal +
          SCENE_DURATIONS.portalTitle +
          SCENE_DURATIONS.dashboardOverview +
          SCENE_DURATIONS.meineMeetups) *
        fps,
      duration: SCENE_DURATIONS.topLaender * fps,
    },
    topMeetups: {
      from:
        (SCENE_DURATIONS.logoReveal +
          SCENE_DURATIONS.portalTitle +
          SCENE_DURATIONS.dashboardOverview +
          SCENE_DURATIONS.meineMeetups +
          SCENE_DURATIONS.topLaender) *
        fps,
      duration: SCENE_DURATIONS.topMeetups * fps,
    },
    activityFeed: {
      from:
        (SCENE_DURATIONS.logoReveal +
          SCENE_DURATIONS.portalTitle +
          SCENE_DURATIONS.dashboardOverview +
          SCENE_DURATIONS.meineMeetups +
          SCENE_DURATIONS.topLaender +
          SCENE_DURATIONS.topMeetups) *
        fps,
      duration: SCENE_DURATIONS.activityFeed * fps,
    },
    callToAction: {
      from:
        (SCENE_DURATIONS.logoReveal +
          SCENE_DURATIONS.portalTitle +
          SCENE_DURATIONS.dashboardOverview +
          SCENE_DURATIONS.meineMeetups +
          SCENE_DURATIONS.topLaender +
          SCENE_DURATIONS.topMeetups +
          SCENE_DURATIONS.activityFeed) *
        fps,
      duration: SCENE_DURATIONS.callToAction * fps,
    },
    outro: {
      from:
        (SCENE_DURATIONS.logoReveal +
          SCENE_DURATIONS.portalTitle +
          SCENE_DURATIONS.dashboardOverview +
          SCENE_DURATIONS.meineMeetups +
          SCENE_DURATIONS.topLaender +
          SCENE_DURATIONS.topMeetups +
          SCENE_DURATIONS.activityFeed +
          SCENE_DURATIONS.callToAction) *
        fps,
      duration: SCENE_DURATIONS.outro * fps,
    },
  };

  return (
    <AbsoluteFill
      className="bg-gradient-to-br from-zinc-900 to-zinc-800"
      style={{ fontFamily: inconsolataFont }}
    >
      {/* Background Music with fade in/out */}
      <PortalAudioManager />

      {/* Wallpaper Background */}
      <Img
        src={staticFile("einundzwanzig-wallpaper.png")}
        className="absolute inset-0 w-full h-full object-cover opacity-20"
      />

      {/* Scene 1: Logo Reveal (6s) */}
      <Sequence
        from={sceneFrames.logoReveal.from}
        durationInFrames={sceneFrames.logoReveal.duration}
        premountFor={fps}
      >
        <PortalIntroSceneMobile />
      </Sequence>

      {/* Scene 2: Portal Title (4s) */}
      <Sequence
        from={sceneFrames.portalTitle.from}
        durationInFrames={sceneFrames.portalTitle.duration}
        premountFor={fps}
      >
        <PortalTitleSceneMobile />
      </Sequence>

      {/* Scene 3: Dashboard Overview (12s) */}
      <Sequence
        from={sceneFrames.dashboardOverview.from}
        durationInFrames={sceneFrames.dashboardOverview.duration}
        premountFor={fps}
      >
        <DashboardOverviewSceneMobile />
      </Sequence>

      {/* Scene 4: Meine Meetups (12s) */}
      <Sequence
        from={sceneFrames.meineMeetups.from}
        durationInFrames={sceneFrames.meineMeetups.duration}
        premountFor={fps}
      >
        <MeetupShowcaseSceneMobile />
      </Sequence>

      {/* Scene 5: Top Länder (12s) */}
      <Sequence
        from={sceneFrames.topLaender.from}
        durationInFrames={sceneFrames.topLaender.duration}
        premountFor={fps}
      >
        <CountryStatsSceneMobile />
      </Sequence>

      {/* Scene 6: Top Meetups (10s) */}
      <Sequence
        from={sceneFrames.topMeetups.from}
        durationInFrames={sceneFrames.topMeetups.duration}
        premountFor={fps}
      >
        <TopMeetupsSceneMobile />
      </Sequence>

      {/* Scene 7: Activity Feed (10s) */}
      <Sequence
        from={sceneFrames.activityFeed.from}
        durationInFrames={sceneFrames.activityFeed.duration}
        premountFor={fps}
      >
        <ActivityFeedSceneMobile />
      </Sequence>

      {/* Scene 8: Call to Action (12s) */}
      <Sequence
        from={sceneFrames.callToAction.from}
        durationInFrames={sceneFrames.callToAction.duration}
        premountFor={fps}
      >
        <CallToActionSceneMobile />
      </Sequence>

      {/* Scene 9: Outro (12s) */}
      <Sequence
        from={sceneFrames.outro.from}
        durationInFrames={sceneFrames.outro.duration}
        premountFor={fps}
      >
        <PortalOutroSceneMobile />
      </Sequence>
    </AbsoluteFill>
  );
};
