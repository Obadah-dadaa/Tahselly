GOAL:    a UAE business owner (trading, maintenance, agency) thinks "this would chase my unpaid invoices for me" and starts the free trial.
OUTPUT:  HyperFrames, 1920 x 1080, 30 fps. Two builds from build.py: teaser/ (Arabic MSA, RTL, ar-AE-HamdanNeural)
         and teaser-en/ (English, LTR, en-GB-RyanNeural). Voice is the clock; scene length = its line + 0.6-1.2 s.
TIME:    AR ~64 s, EN ~58 s. No music source on this machine (no HeyGen, no local model): VO + bundled SFX only.
LOOK:    three worlds, one per business, each with its own ground and object:
           Deira trading   sand #f3e6cf, cartons #c79a6b with tape #e9d3a8;
           Al Quoz upkeep  steel #e2e8f0, hazard band #facc15/#0b1220 at 45deg, wrench;
           Business Bay    glass #eef6f8, skyline silhouettes #cbd5e1/#94a3b8.
         product world slate #f1f5f9 with real screens (captured 2x, shown 1920 wide) and components rebuilt 1:1 at 1.4-1.8x.
         brand field teal #0f766e only on reveal and end card. ink #0b1220; overdue #e11d48/#fff1f2; paid #059669/#ecfdf5;
         WhatsApp green #25D366 only on the WhatsApp card. IBM Plex Sans Arabic 700 for display (96-190 px), 600 body (34-56 px).
         cards radius 28 px, shadow 0 30px 60px rgba(15,23,42,.14). No grain, no vignette, no glow.
OBJECTS: invoice card (client, number, amount, status pill), cartons, wrench + hazard band, skyline, phone-call row, chat
         bubble with grey tick, day counter, Excel file chip, cursor (white arrow 70 px), WhatsApp list card with Send all.
MOTION:  enter power3.out 0.45 s; move power2.inOut 0.6 s; camera expo.inOut 0.8 s; pops back.out(1.7) 0.35 s (scale);
         wipes power3.inOut 0.5 s; staggers 0.12-0.35 s. Text lands, then holds still while read.
SHOTS:   hook (3 panes wipe in one per business, pills pulse on "one problem", banner) → tension (counter 30→90 days,
         call / message / waiting rows) → reveal (teal circle, mark, wordmark, "it follows up for you") → trading (import
         screen push, Excel chip dragged, rows populate) → upkeep (schedule chips push, WhatsApp list, Send all, rows → Done)
         → agency (owner dashboard, push to 93.4%, pan to aging) → payoff (3 panes return, pills flip to Paid) → end card.
BANNED:  dark void ground; glow/particles; purple gradients; glass cards; title over dimmed screenshot; logo docked in a
         corner; every element fading up; invented product UI or numbers (screens + figures from the seeded demo company;
         the three businesses and their invoices are illustrative).
QA:      AR frames 1, 5, 10, 14, 19, 25, 31, 37, 45, 52, 58, 62 (EN scaled) — shaping, nothing clipped, mirrored layout right.
