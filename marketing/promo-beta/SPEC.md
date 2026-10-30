GOAL:    a UAE agency/accounting owner thinks "this would collect my overdue invoices for me" and asks for a demo.
OUTPUT:  HyperFrames, 1920 x 1080, 30 fps, Arabic RTL, on-screen text only, no voiceover.
TIME:    45 s x 30 = 1350 frames. No music bed (no source); SFX on actions.
LOOK:    grounds: sheet white #ffffff with #e2e8f0 grid; product slate #f1f5f9; brand field teal #0f766e (reveal, end card only).
         ink #0b1220; accent teal #14b8a6 on one word per shot; status red #e11d48 only for overdue, green #059669 for paid.
         display: IBM Plex Sans Arabic 700, 112-150 px, tracking 0; body 600 at 40-56 px. Numbers Latin digits, tabular.
         medium: the product's own UI (captured at 2x) and components rebuilt 1:1 from it at 1.6-2x; card radius 28 px,
         shadow 0 30px 60px rgba(15,23,42,.12). No grain, no vignette.
OBJECTS: spreadsheet (column letters, gridlines, Arabic headers, red fill rows), sticky note (yellow #fde68a, 3deg tilt, tape),
         Excel file chip (green xlsx badge), reminder timeline (dots + "مُرسل" pills), status pill, KPI tile, cursor (white arrow, 90 px).
MOTION:  enter power3.out 0.5 s; move power2.inOut 0.6 s; exit power2.in 0.35 s; camera expo.inOut 0.8 s;
         pops back.out(1.6) 0.35 s (scale only); staggers 4-6 frames. Text lands then holds still.
SHOTS:   hook 0-9 · reveal 9-12.5 · import 12.5-17.5 · schedule 17.5-22 · reminders+payment 22-30 · payoff 30-36.5 · roles 36.5-40 · end 40-45.
BANNED:  dark void ground; glow/particles; purple gradients; glass cards; three cards in a row; title over dimmed screenshot;
         logo docked in a corner; every element fading up; invented numbers (all figures from the seeded demo company).
QA:      frames 1, 6, 10.5, 15, 20, 26, 29, 33, 38, 43 — Arabic shaping correct, nothing clipped, numbers match the app.
