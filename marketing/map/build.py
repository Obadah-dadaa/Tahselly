"""Builds "The collection map" teaser in Arabic (map/) and English (map-en/).

One schematic map of the UAE is the whole film's world: red pins for overdue invoices, Tahselly's 9:00 round sends
teal lines to every pin, the camera dives into three pins to show the real product (scenes reused from the teaser
build, re-timed), then the pins turn green. Run after the voice clips exist: python build.py
"""
import json
import os
import re
import shutil
import subprocess

HERE = os.path.dirname(os.path.abspath(__file__))
MARKETING = os.path.dirname(HERE)
TEASER = {'ar': os.path.join(MARKETING, 'teaser'), 'en': os.path.join(MARKETING, 'teaser-en')}

T = {
    'ar': {
        'dir': 'rtl', 'aed': 'درهم',
        'overdue_lbl': 'فواتير متأخرة', 'collected_lbl': 'حُصّلت بعد التذكير (30 يوماً)',
        'areas': {'deira': 'ديرة', 'alquoz': 'القوز', 'bbay': 'الخليج التجاري', 'sharjah': 'الشارقة الصناعية', 'mussafah': 'مصفّح'},
        'cities': {'dubai': 'دبي', 'sharjah': 'الشارقة', 'abudhabi': 'أبوظبي'}, 'gulf': 'الخليج العربي',
        'clock_sub': 'الجولة اليومية', 'word': 'تحصيلي',
        'po_line': 'دبّوس بعد دبّوس، <span class="k">الخريطة تخضرّ.</span>',
        'ec_claim': 'يتابع فواتيرك في كل الإمارات.', 'ec_price': 'من 99 درهم شهرياً', 'ec_cta': 'ابدأ مجاناً 14 يوماً',
        'ec_aud': 'شركات التجارة · الصيانة · الوكالات · مكاتب المحاسبة · الخدمات',
    },
    'en': {
        'dir': 'ltr', 'aed': 'AED',
        'overdue_lbl': 'Overdue invoices', 'collected_lbl': 'Collected after reminders (30 days)',
        'areas': {'deira': 'Deira', 'alquoz': 'Al Quoz', 'bbay': 'Business Bay', 'sharjah': 'Sharjah Industrial', 'mussafah': 'Mussafah'},
        'cities': {'dubai': 'Dubai', 'sharjah': 'Sharjah', 'abudhabi': 'Abu Dhabi'}, 'gulf': 'Arabian Gulf',
        'clock_sub': 'Daily round', 'word': 'Tahselly',
        'po_line': 'Pin by pin, <span class="k">the map turns green.</span>',
        'ec_claim': 'Following up on your invoices across the Emirates.', 'ec_price': 'From AED 99 / month', 'ec_cta': 'Start free for 14 days',
        'ec_aud': 'Trading · Maintenance · Agencies · Accounting firms · Services',
    },
}

# World is 3200 x 1800. Schematic coast runs south-west (Abu Dhabi) to north-east (Sharjah); the Gulf is to the north-west.
COAST = 'M0,0 H3200 V260 C2900,380 2560,560 2250,740 C1950,900 1550,1080 1150,1250 C820,1390 480,1500 0,1640 Z'
PINS = {  # world x, y
    'sharjah': (2480, 700), 'deira': (2170, 860), 'bbay': (1960, 1010), 'alquoz': (1790, 1110), 'mussafah': (720, 1660),
}
CITIES = {'sharjah': (2640, 860), 'dubai': (2050, 1240), 'abudhabi': (560, 1520)}
HUB = (1600, 420)
# more invoices appearing near each area (deterministic)
DOTS = [(2420, 760), (2540, 790), (2230, 930), (2100, 800), (2020, 1090), (1880, 980), (1730, 1180), (1850, 1190),
        (660, 1720), (800, 1700), (2600, 720), (1690, 1060), (2300, 880), (760, 1600)]

FONT_FACES = ''.join(
    f'@font-face {{ font-family: "Plex Arabic"; font-weight: {w}; src: url("assets/fonts/plex-arabic-{w}-arabic.woff2") format("woff2"); unicode-range: U+0600-06FF, U+0750-077F, U+FB50-FDFF, U+FE70-FEFC; }}\n'
    f'@font-face {{ font-family: "Plex Arabic"; font-weight: {w}; src: url("assets/fonts/plex-arabic-{w}-latin.woff2") format("woff2"); unicode-range: U+0000-00FF, U+2000-206F, U+2190-21FF, U+2713; }}\n'
    for w in (400, 600, 700)
)


def duration(path):
    return float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', path]).decode())


def retime_dive(src, cid, new_d):
    """Copy a teaser scene and stretch the clips that ran to its end."""
    html = open(src, encoding='utf-8').read()
    old_d = float(re.search(r'const D = ([\d.]+);', html).group(1))

    def fix(m):
        start, dur = float(m.group(2)), float(m.group(3))
        if abs(start + dur - old_d) < 0.02:
            dur = new_d - start
        return f'{m.group(1)}data-start="{m.group(2)}" data-duration="{dur:.2f}"'
    html = re.sub(r'(<[^>]*?)data-start="([\d.]+)" data-duration="([\d.]+)"', fix, html)
    html = html.replace(f'const D = {old_d:.2f};', f'const D = {new_d:.2f};')
    old_id = re.search(r'data-composition-id="([\w-]+)"', html).group(1)
    html = html.replace(f'data-composition-id="{old_id}"', f'data-composition-id="{cid}"').replace(f'window.__timelines["{old_id}"]', f'window.__timelines["{cid}"]')
    return html


def build(lang, project):
    t = T[lang]
    teaser = TEASER[lang]
    os.makedirs(os.path.join(project, 'compositions'), exist_ok=True)
    for sub in ('fonts', 'sfx', 'screens'):
        dst = os.path.join(project, 'assets', sub)
        if os.path.isdir(dst):
            shutil.rmtree(dst)
        shutil.copytree(os.path.join(teaser, 'assets', sub), dst)
    shutil.copy(os.path.join(teaser, 'assets', 'mark.png'), os.path.join(project, 'assets', 'mark.png'))

    vo = {f'vo{i}': duration(os.path.join(project, 'assets', 'voice', f'vo{i}.mp3')) for i in range(1, 9)}
    scenes = [('hook', vo['vo1'] + 0.7), ('tension', vo['vo2'] + 0.5), ('reveal', max(vo['vo3'] + 0.8, 4.2)),
              ('dive1', max(vo['vo4'] + 0.9, 6.4)), ('hop1', 1.3), ('dive2', max(vo['vo5'] + 0.9, 7.7)), ('hop2', 1.3), ('dive3', max(vo['vo6'] + 0.9, 7.0)),
              ('payoff', max(vo['vo7'] + 1.4, 4.6)), ('endcard', vo['vo8'] + 1.3)]
    S, s = {}, 0.0
    for n, d in scenes:
        S[n] = s
        s += d
    D = dict(scenes)
    total = s
    map_end = S['endcard']
    vo_at = {'vo1': 0.3, 'vo2': S['tension'] + 0.1, 'vo3': S['reveal'] + 0.3, 'vo4': S['dive1'] + 0.5, 'vo5': S['dive2'] + 0.4,
             'vo6': S['dive3'] + 0.4, 'vo7': S['payoff'] + 0.3, 'vo8': S['endcard'] + 0.6}

    # ---- the map: one world for the whole film ----
    def cam(x, y, sc):
        cx = min(0, max(1920 - 3200 * sc, 960 - x * sc))
        cy = min(0, max(1080 - 1800 * sc, 540 - y * sc))
        return f'{{ x: {cx:.1f}, y: {cy:.1f}, scale: {sc} }}'
    overview = cam(1600, 900, 0.6)
    pins_svg, labels = [], []
    for k, (x, y) in PINS.items():
        pins_svg.append(f'<g id="pin-{k}" class="pin" transform="translate({x} {y})"><g class="pin-body">'
                        f'<path class="pr" d="M0,0 C-34,-52 -52,-74 -52,-104 A52,52 0 1 1 52,-104 C52,-74 34,-52 0,0 Z" fill="#e11d48"/>'
                        f'<path class="pg" d="M0,0 C-34,-52 -52,-74 -52,-104 A52,52 0 1 1 52,-104 C52,-74 34,-52 0,0 Z" fill="#059669" opacity="0"/>'
                        f'<circle cx="0" cy="-104" r="20" fill="#f1f5f9"/></g></g>')
        side = f'right:{3200 - (x - 70)}px' if k in ('bbay', 'alquoz') else f'left:{x + 70}px'  # west of the pin where pins crowd
        labels.append(f'<div class="lbl" id="lbl-{k}" style="{side};top:{y - 150}px">{t["areas"][k]}</div>')
    dots_svg = ''.join(f'<g class="dot" id="dot{i}" transform="translate({x} {y})"><circle class="dr" r="22" fill="#e11d48" stroke="#ffffff" stroke-width="6"/><circle class="dg" r="22" fill="#059669" stroke="#ffffff" stroke-width="6" opacity="0"/></g>' for i, (x, y) in enumerate(DOTS))
    lines_svg = ''.join(f'<path class="ln" id="ln-{k}" d="M{HUB[0]},{HUB[1]} Q{(HUB[0] + x) / 2},{min(HUB[1], y) - 260} {x},{y - 110}" fill="none" stroke="#14b8a6" stroke-width="10" stroke-linecap="butt" pathLength="1" stroke-dasharray="1" stroke-dashoffset="1"/>' for k, (x, y) in PINS.items())
    cities = ''.join(f'<div class="city" style="left:{x}px;top:{y}px">{t["cities"][k]}</div>' for k, (x, y) in CITIES.items())

    tension_stops = ['deira', 'alquoz', 'sharjah', 'mussafah']
    stop_at = [S['tension'] + 0.1 + vo['vo2'] * f for f in (0.0, 0.17, 0.34, 0.55)]
    dive_pins = {'dive1': 'deira', 'dive2': 'alquoz', 'dive3': 'bbay'}
    js = [f"tl.set('#world', {overview}, 0);", "tl.set('#po-line', { y: 150 }, 0);",
          # hook: slow push over the red map, overdue counter lands
          f"tl.fromTo('#world', {overview}, {{ ...{cam(1900, 1000, 0.7)}, duration: {D['hook']:.2f}, ease: 'none' }}, 0);",
          "tl.fromTo('.pin-body', { scale: 1, transformOrigin: '50% 100%' }, { scale: 1.15, duration: 0.2, yoyo: true, repeat: 1, stagger: 0.12 }, 0.9);",
          "tl.fromTo('#hud', { y: -40, opacity: 0 }, { y: 0, opacity: 1, duration: 0.45, ease: 'power3.out' }, 0.6);",
          f"const ov = {{ v: 0 }}; tl.to(ov, {{ v: 167370, duration: {min(3.2, vo['vo1'] * 0.6):.2f}, ease: 'power2.out', onUpdate: () => {{ document.getElementById('hud-num').textContent = Math.round(ov.v).toLocaleString('en-US'); }} }}, 0.8);"]
    for i, (k, at) in enumerate(zip(tension_stops, stop_at)):
        x, y = PINS[k]
        js.append(f"tl.to('#world', {{ ...{cam(x, y - 60, 1.15)}, duration: 0.7, ease: 'expo.inOut' }}, {at:.2f});")
        js.append(f"tl.fromTo('#lbl-{k}', {{ scale: 0.6, opacity: 0 }}, {{ scale: 1, opacity: 1, duration: 0.35, ease: 'back.out(1.7)' }}, {at + 0.45:.2f});")
    # "every day, another red pin"
    js.append(f"tl.to('#world', {{ ...{overview}, duration: 0.8, ease: 'expo.inOut' }}, {S['tension'] + vo['vo2'] * 0.72:.2f});")
    js.append(f"tl.fromTo('#lbl-bbay', {{ scale: 0.6, opacity: 0 }}, {{ scale: 1, opacity: 1, duration: 0.35, ease: 'back.out(1.7)' }}, {S['tension'] + vo['vo2'] * 0.75:.2f});")
    js.append(f"tl.fromTo('.dot', {{ scale: 0, transformOrigin: '50% 50%' }}, {{ scale: 1, duration: 0.3, ease: 'back.out(2)', stagger: 0.09 }}, {S['tension'] + vo['vo2'] * 0.78:.2f});")
    # reveal: 9:00, the hub appears and draws a teal line to every pin
    r = S['reveal']
    js += [f"tl.fromTo('#clock', {{ scale: 0.5, opacity: 0 }}, {{ scale: 1, opacity: 1, duration: 0.45, ease: 'back.out(1.7)' }}, {r + 0.1:.2f});",
           f"const cl = {{ m: 0 }}; tl.fromTo(cl, {{ m: 525 }}, {{ m: 540, duration: 0.9, ease: 'power2.out', onUpdate: () => {{ const m = Math.round(cl.m); document.getElementById('clock-t').textContent = String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0'); }} }}, {r + 0.2:.2f});",
           f"tl.fromTo('#hub', {{ scale: 0, transformOrigin: '50% 50%' }}, {{ scale: 1, duration: 0.5, ease: 'back.out(1.8)' }}, {r + 1.1:.2f});",
           f"tl.to('.ln', {{ strokeDashoffset: 0, duration: 0.7, ease: 'power2.inOut', stagger: 0.12 }}, {r + 1.5:.2f});"]
    # dives: the camera zooms through the pin's head until it fills the frame (the product's own ground colour),
    # the dive plays with the map hidden, then the camera comes back out of the same pin
    for n, k in dive_pins.items():
        x, y = PINS[k]
        js.append(f"tl.to('#world', {{ ...{cam(x, y - 104, 60)}, duration: 0.75, ease: 'expo.in' }}, {S[n] - 0.75:.2f});")
        js.append(f"tl.set('#mapwrap', {{ opacity: 0 }}, {S[n]:.2f}); tl.set('#mapwrap', {{ opacity: 1 }}, {S[n] + D[n]:.2f});")
        if n != 'dive3':
            js.append(f"tl.to('#world', {{ ...{cam(x, y - 60, 1.6)}, duration: 0.45, ease: 'expo.out' }}, {S[n] + D[n]:.2f});")
    # the map's cards and labels step aside for the dives (nothing of the map may draw over a dive circle)
    js.append(f"tl.to(['#hud', '#clock', '.lbl'], {{ opacity: 0, duration: 0.25 }}, {S['dive1'] - 0.35:.2f});")
    js.append(f"tl.to(['#hud', '#clock', '.lbl'], {{ opacity: 1, duration: 0.3 }}, {S['payoff'] + 0.1:.2f});")
    # payoff: back to the whole map, every pin and dot turns green while collected counts up
    p = S['payoff']
    js += [f"tl.to('#world', {{ ...{overview}, duration: 0.9, ease: 'expo.out' }}, {p:.2f});",
           f"tl.to('#hud-a', {{ opacity: 0, duration: 0.2 }}, {p + 0.3:.2f});",
           f"tl.fromTo('#hud-b', {{ opacity: 0 }}, {{ opacity: 1, duration: 0.25 }}, {p + 0.45:.2f});",
           f"const co = {{ v: 0 }}; tl.to(co, {{ v: 177980, duration: 2.2, ease: 'power2.out', onUpdate: () => {{ document.getElementById('hud-col').textContent = Math.round(co.v).toLocaleString('en-US'); }} }}, {p + 0.5:.2f});"]
    order = ['mussafah', 'alquoz', 'bbay', 'deira', 'sharjah']
    for i, k in enumerate(order):
        at = p + 0.6 + i * 0.32
        js.append(f"tl.to('#pin-{k} .pr', {{ opacity: 0, duration: 0.15 }}, {at:.2f}); tl.to('#pin-{k} .pg', {{ opacity: 1, duration: 0.15 }}, {at:.2f});")
        js.append(f"tl.fromTo('#pin-{k} .pin-body', {{ scale: 1 }}, {{ scale: 1.25, duration: 0.15, yoyo: true, repeat: 1, transformOrigin: '50% 100%' }}, {at:.2f});")
    js.append(f"tl.to('.dr', {{ opacity: 0, duration: 0.12, stagger: 0.07 }}, {p + 0.8:.2f}); tl.to('.dg', {{ opacity: 1, duration: 0.12, stagger: 0.07 }}, {p + 0.8:.2f});")
    js.append(f"tl.fromTo('#po-line', {{ y: 150 }}, {{ y: 0, duration: 0.45, ease: 'power3.out' }}, {p + 1.9:.2f});")

    map_html = f"""<template>
  <style>
{FONT_FACES}
    #root {{ position: absolute; inset: 0; overflow: hidden; direction: {t['dir']}; font-family: "Plex Arabic"; background: #f3e6cf; }}
    #world {{ position: absolute; left: 0; top: 0; width: 3200px; height: 1800px; transform-origin: 0 0; background: #f3e6cf; }}
    #world svg {{ position: absolute; left: 0; top: 0; }}
    .city {{ position: absolute; font-size: 64px; font-weight: 700; color: #7c5d3a; transform: translate(-50%, -50%); white-space: nowrap; }}
    .gulf {{ position: absolute; left: 1000px; top: 640px; font-size: 70px; font-weight: 600; color: #3f7f8f; letter-spacing: 0; transform: rotate(-24deg); white-space: nowrap; }}
    .lbl {{ position: absolute; font-size: 54px; font-weight: 700; color: #0b1220; background: #ffffff; border-radius: 20px; padding: 6px 26px 12px; box-shadow: 0 16px 30px rgba(15,23,42,.18); white-space: nowrap; opacity: 0; }}
    #hub {{ position: absolute; left: {HUB[0] - 110}px; top: {HUB[1] - 110}px; width: 220px; height: 220px; border-radius: 54px; box-shadow: 0 0 0 18px rgba(20,184,166,.25); }}
    #hud {{ position: absolute; inset-inline-start: 70px; top: 60px; background: #ffffff; border-radius: 28px; padding: 22px 40px 26px; box-shadow: 0 24px 50px rgba(15,23,42,.16); min-width: 620px; }}
    #hud .l {{ font-size: 34px; font-weight: 600; color: #475569; }}
    #hud .n {{ font-size: 92px; font-weight: 700; line-height: 1.1; direction: ltr; text-align: start; font-variant-numeric: tabular-nums; }}
    #hud .n small {{ font-size: 40px; color: #64748b; margin-inline-start: 12px; }}
    #hud-b {{ position: absolute; inset: 22px 40px 26px; opacity: 0; }}
    #clock {{ position: absolute; inset-inline-end: 70px; top: 60px; background: #0f766e; color: #ffffff; border-radius: 28px; padding: 18px 44px 24px; text-align: center; box-shadow: 0 24px 50px rgba(15,118,110,.35); opacity: 0; }}
    #clock-t {{ font-size: 96px; font-weight: 700; line-height: 1.1; direction: ltr; font-variant-numeric: tabular-nums; }}
    #clock .s {{ font-size: 32px; font-weight: 600; color: #99f6e4; }}
    #po-line {{ position: absolute; left: 0; right: 0; bottom: 0; height: 150px; background: #0b1220; display: flex; align-items: center; justify-content: center; font-size: 70px; font-weight: 700; color: #ffffff; }}
    #po-line .k {{ color: #5eead4; }}
  </style>
  <div id="root" data-composition-id="map" data-width="1920" data-height="1080">
   <div id="mapwrap" style="position:absolute;inset:0">
    <div id="world" class="clip" data-start="0" data-duration="{map_end:.2f}">
      <svg width="3200" height="1800" viewBox="0 0 3200 1800"><path d="{COAST}" fill="#cfe6ec" stroke="#b8d8e0" stroke-width="10"/></svg>
      <div class="gulf">{t['gulf']}</div>
      {cities}
      <svg width="3200" height="1800" viewBox="0 0 3200 1800">{lines_svg}{dots_svg}{''.join(pins_svg)}</svg>
      <img id="hub" src="assets/mark.png" alt="Tahselly" />
      {''.join(labels)}
    </div>
    <div id="hud" class="clip" data-start="0" data-duration="{map_end:.2f}">
      <div id="hud-a"><div class="l">{t['overdue_lbl']}</div><div class="n" style="color:#e11d48"><span id="hud-num">0</span><small>{t['aed']}</small></div></div>
      <div id="hud-b"><div class="l">{t['collected_lbl']}</div><div class="n" style="color:#059669"><span id="hud-col">0</span><small>{t['aed']}</small></div></div>
    </div>
    <div id="clock" class="clip" data-start="0" data-duration="{map_end:.2f}"><div id="clock-t">08:45</div><div class="s">{t['clock_sub']}</div></div>
    <div id="po-line" class="clip" data-start="0" data-duration="{map_end:.2f}"><div>{t['po_line']}</div></div>
   </div>
  </div>
  <script>
    const tl = gsap.timeline({{ paused: true }});
    {chr(10).join('    ' + j for j in js).strip()}
    window.__timelines["map"] = tl;
  </script>
</template>
"""
    files = {'map': map_html}
    # The dives reuse the teaser's real-product scenes
    for n, src in (('dive1', 'trading'), ('dive2', 'upkeep'), ('dive3', 'agency')):
        files[n] = retime_dive(os.path.join(teaser, 'compositions', src + '.html'), n, D[n])
    # End card: same as the teaser's, new copy
    ec = open(os.path.join(teaser, 'compositions', 'endcard.html'), encoding='utf-8').read()
    ec = re.sub(r'(<div id="ec-claim"[^>]*>)[^<]*', lambda m: m.group(1) + t['ec_claim'], ec)
    ec = re.sub(r'(<div id="ec-price"[^>]*>)[^<]*', lambda m: m.group(1) + t['ec_price'], ec)
    ec = re.sub(r'(<div id="ec-cta"[^>]*>)[^<]*', lambda m: m.group(1) + t['ec_cta'], ec)
    ec = re.sub(r'(<div id="ec-aud"[^>]*>)[^<]*', lambda m: m.group(1) + t['ec_aud'], ec)
    old_d = float(re.search(r'const D = ([\d.]+);', ec).group(1))
    ec = ec.replace(f'data-duration="{old_d:.2f}"', f'data-duration="{D["endcard"]:.2f}"').replace(f'const D = {old_d:.2f};', f'const D = {D["endcard"]:.2f};')
    files['endcard'] = ec

    for name, html in files.items():
        with open(os.path.join(project, 'compositions', name + '.html'), 'w', encoding='utf-8', newline='\n') as f:
            f.write(html)

    sfx = [('pop', 0.25, .25), ('impact-bass-1', 0.8, .3)]
    sfx += [('whoosh-short', at, .22) for at in stop_at]
    sfx += [('pop', S['tension'] + vo['vo2'] * 0.78, .25), ('notification', r + 0.2, .3), ('whoosh', r + 1.5, .3)]
    for n in dive_pins:
        sfx.append(('whoosh', S[n] - 0.8, .3))
        if n != 'dive3':
            sfx.append(('whoosh-short', S[n] + D[n], .22))
    sfx += [('click', S['dive2'] + 3.93, .5), ('pop', S['dive2'] + 4.25, .22), ('pop', S['dive2'] + 5.05, .22),
            ('whoosh-short', S['dive3'] + 3.3, .2)]
    sfx += [('pop', p + 0.62 + i * 0.32, .3) for i in range(5)]
    sfx += [('whoosh', S['endcard'] - 0.05, .35), ('impact-bass-1', S['endcard'] + 0.2, .3)]
    sfx_dur = {f[:-4]: duration(os.path.join(project, 'assets', 'sfx', f)) for f in os.listdir(os.path.join(project, 'assets', 'sfx'))}

    hosts = [f'      <div id="map" data-composition-id="map" data-composition-src="compositions/map.html" data-start="0" data-duration="{map_end:.2f}" data-track-index="1" data-track-kind="graphics" data-width="1920" data-height="1080"></div>']
    hosts += [f'      <div id="{n}" data-composition-id="{n}" data-composition-src="compositions/{n}.html" data-start="{S[n]:.2f}" data-duration="{D[n]:.2f}" data-track-index="2" data-track-kind="graphics" data-width="1920" data-height="1080"></div>'
              for n in ('dive1', 'dive2', 'dive3', 'endcard')]
    voices = [f'      <audio id="{v}" class="clip" src="assets/voice/{v}.mp3" data-start="{vo_at[v]:.2f}" data-duration="{vo[v]:.2f}" data-track-index="3" data-volume="1"></audio>' for v in vo]
    effects = [f'      <audio id="sfx-{i + 1}" class="clip" src="assets/sfx/{f}.mp3" data-start="{at:.2f}" data-duration="{min(sfx_dur[f], total - at):.2f}" data-track-index="{5 + i % 4}" data-volume="{vol}"></audio>'
               for i, (f, at, vol) in enumerate(sfx)]
    index = f"""<!doctype html>
<html lang="{lang}" dir="{t['dir']}">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=1920, height=1080" />
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.2/dist/gsap.min.js"></script>
    <style>
{FONT_FACES}
      * {{ margin: 0; padding: 0; box-sizing: border-box; }}
      html, body {{ margin: 0; width: 1920px; height: 1080px; overflow: hidden; background: #cfe6ec; }}
      #root {{ width: 100%; height: 100%; font-family: "Plex Arabic"; direction: {t['dir']}; }}
      /* the map is the ground; the dives and the end card open above it */
      #map {{ position: absolute; inset: 0; z-index: 1 !important; }}
      #dive1, #dive2, #dive3, #endcard {{ position: absolute; inset: 0; z-index: 2 !important; }}
    </style>
  </head>
  <body>
    <div id="root" data-composition-id="main" data-start="0" data-duration="{total:.2f}" data-width="1920" data-height="1080">
{chr(10).join(hosts)}
      <!-- Voiceover (see VOICEOVER.md) -->
{chr(10).join(voices)}
      <!-- Effects on visible actions, under the voice -->
{chr(10).join(effects)}
    </div>
    <script>
      window.__timelines = window.__timelines || {{}};
      window.__timelines["main"] = gsap.timeline({{ paused: true }});
    </script>
  </body>
</html>
"""
    with open(os.path.join(project, 'index.html'), 'w', encoding='utf-8', newline='\n') as f:
        f.write(index)
    shutil.copy(os.path.join(teaser, 'hyperframes.json'), os.path.join(project, 'hyperframes.json'))
    name = os.path.basename(project)
    with open(os.path.join(project, 'meta.json'), 'w') as f:
        json.dump({'id': f'tahselly-{name}', 'name': f'tahselly-{name}', 'createdAt': '2026-10-30T09:00:00.000Z'}, f, indent=2)
    with open(os.path.join(project, 'package.json'), 'w') as f:
        json.dump({'name': f'tahselly-{name}', 'private': True, 'type': 'module', 'scripts': {
            'dev': 'npx --yes hyperframes@0.8.207 preview', 'check': 'npx --yes hyperframes@0.8.207 check',
            'render': 'npx --yes hyperframes@0.8.207 render'}}, f, indent=2)
    print(f'{name}: {total:.1f} s, ' + ', '.join(f'{n} {S[n]:.1f}+{d:.1f}' for n, d in scenes))


build('ar', HERE)
build('en', os.path.join(MARKETING, 'map-en'))
