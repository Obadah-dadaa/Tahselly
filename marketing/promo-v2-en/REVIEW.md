# Review — Tahselly promo v2 (English)

## Creative calls
- Storyline built: **Same chat, new ending** (see STORYLINES.md). Not built: "One invoice's life", "The number lands".
- Every feature beat uses the real product: fresh 2x captures from tahselly.test (invoice page, owner dashboard, manager dashboard, reminder generator) plus components rebuilt 1:1 from the real screens and the real seeded reminder templates (import page, reminder schedule 3 days before → final notice, email AR/EN, WhatsApp overdue template, aging buckets with the demo company's figures, team roles, reminder log, referral page).
- All figures are the seeded demo company's sample data (217,470 AED overdue is labelled on screen as a sample from the demo account). No prices, customers, testimonials, ratings or results. The only offer number is the founding 50%.
- Voiceover: free edge-tts neural voice (same tool as the earlier promos' temporary VO). Music: generated locally with MusicGen-small (no HeyGen sign-in available), two 30 s seeds crossfaded to 76 s, normalised, ducked under the voice by level (bed at 0.22).

## Measured vs looked at
- `hyperframes check`: passes (ok: true) for both languages.
- Snapshot review sheets, seams and targeted frames were looked at for both languages; bidi/RTL fixed (dates isolated, bubbles on the correct side, timelines and bars flow from the start side).
- Audio was measured with `hyperframes audio` on the rendered file, not heard. MusicGen quality in particular has not been listened to.

## Warnings kept on purpose
- `line_unread` on the email body, WhatsApp message and demo banner: these are product UI shown working, the VO and titles carry the line; the viewer is not meant to read the full email.
- `contrast_aa_failure` on WhatsApp's blue read-ticks: that is WhatsApp's real colour, decorative.

## Weakest shot / next time
- The owner-dashboard push (insight, 39-43 s) is a camera move over a still screenshot; with more time, rebuild the "invoiced vs collected" chart and let its bars grow.
- The music bed is a local model output; swap in a licensed track (HeyGen catalog or the brand's own) for the paid campaign.
- Voice is a TTS stand-in; a HeyGen or human Gulf voice would raise warmth.

## Render
- 1920x1080, 30 fps, H.264 + AAC, 76.0 s. Loudness measured: AR -13.1 LUFS / EN -13.7 LUFS integrated, true peak about -1.1 dBFS.
- Poster: frame at 73 s (end card).
- Source of the scene files: generated from one template for both languages (scratchpad build script); edit the files in compositions/ directly from now on.
