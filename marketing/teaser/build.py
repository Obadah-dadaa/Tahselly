"""Builds the Tahselly teaser in Arabic (teaser/) and English (teaser-en/) from one set of scenes.

Run: python build.py   (after the voice clips exist in each project's assets/voice/)
Scene lengths follow each language's voiceover; layouts mirror for LTR.
"""
import json
import os
import shutil
import subprocess

HERE = os.path.dirname(os.path.abspath(__file__))
MARKETING = os.path.dirname(HERE)
PROMO = os.path.join(MARKETING, 'promo')
APP_PUBLIC = os.path.join(os.path.dirname(MARKETING), 'public')

T = {
    'ar': {
        'dir': 'rtl',
        'p1_place': 'ديرة', 'p1_kind': 'شركة تجارة عامة',
        'p2_place': 'القوز', 'p2_kind': 'شركة صيانة',
        'p3_place': 'الخليج التجاري', 'p3_kind': 'وكالة تسويق',
        'overdue': 'متأخرة', 'days': 'يوم', 'paid': 'مدفوعة',
        'banner': 'مشكلة واحدة: <span class="red">فواتير لم تُدفع</span>',
        'counter_label': 'يوم تأخير',
        'call': 'اتصال', 'call_sub': 'لم يُرَد',
        'msg': 'نرجو التكرّم بسداد الفاتورة INV-1047', 'msg_sub': 'لم تُقرأ',
        'wait': 'بانتظار الرد…',
        'word': 'تحصيلي', 'reveal_line': 'توقّف عن الملاحقة. <span class="k">النظام يتابع عنك.</span>',
        'tr_chip': 'شركة التجارة · ديرة', 'tr_title': 'ارفع ملف Excel كما هو',
        'tr_head': 'تمت قراءة الملف، والعملاء أُنشئوا تلقائياً', 'tr_ok': '✓ تم الاستيراد',
        'mt_chip': 'شركة الصيانة · القوز', 'mt_title1': 'جدول التذكير، مرّة واحدة', 'mt_title2': 'بالبريد والواتساب، قبل الموعد وبعده',
        'wa_head': 'تذكيرات واتساب المستحقة اليوم', 'wa_sub': 'الرسالة جاهزة باسم العميل والمبلغ ورقم الفاتورة',
        'wa_all': 'إرسال الكل', 'wa_send': 'إرسال', 'wa_done': 'تم', 'wa_left': 'متبقية', 'final': 'إنذار نهائي', 'due': 'الاستحقاق',
        'ag_chip': 'وكالة التسويق · الخليج التجاري', 'ag_title1': 'نسبة التحصيل أمامك', 'ag_title2': 'وأعمار الديون في لوحة واحدة',
        'po_line': 'فواتير تُدفع، <span class="k">من دون ملاحقة.</span>',
        'ec_claim': 'يتابع فواتيرك، لتتفرّغ لعملك.', 'ec_price': 'من 99 درهم شهرياً',
        'ec_cta': 'جرّبه مجاناً 14 يوماً', 'ec_aud': 'شركات التجارة · الصيانة · الوكالات · مكاتب المحاسبة · الخدمات',
        'aed': 'درهم',
    },
    'en': {
        'dir': 'ltr',
        'p1_place': 'Deira', 'p1_kind': 'General trading company',
        'p2_place': 'Al Quoz', 'p2_kind': 'Maintenance firm',
        'p3_place': 'Business Bay', 'p3_kind': 'Marketing agency',
        'overdue': 'Overdue', 'days': 'days', 'paid': 'Paid',
        'banner': 'One problem: <span class="red">unpaid invoices</span>',
        'counter_label': 'days overdue',
        'call': 'Call', 'call_sub': 'no answer',
        'msg': 'Kindly settle invoice INV-1047', 'msg_sub': 'not read',
        'wait': 'Waiting for a reply…',
        'word': 'Tahselly', 'reveal_line': 'Stop chasing. <span class="k">It follows up for you.</span>',
        'tr_chip': 'Trading company · Deira', 'tr_title': 'Upload your Excel file as it is',
        'tr_head': 'File read, clients created automatically', 'tr_ok': '✓ Imported',
        'mt_chip': 'Maintenance firm · Al Quoz', 'mt_title1': 'Set the schedule once', 'mt_title2': 'Email and WhatsApp, before and after due',
        'wa_head': 'WhatsApp reminders due today', 'wa_sub': "Each message ready with the client's name, amount and invoice",
        'wa_all': 'Send all', 'wa_send': 'Send', 'wa_done': 'Done', 'wa_left': 'left', 'final': 'Final notice', 'due': 'due',
        'ag_chip': 'Marketing agency · Business Bay', 'ag_title1': 'Your collection rate, at a glance', 'ag_title2': 'And debt aging on one dashboard',
        'po_line': 'Invoices get paid, <span class="k">without the chasing.</span>',
        'ec_claim': 'It does the chasing. You run the business.', 'ec_price': 'From AED 99 / month',
        'ec_cta': 'Try it free for 14 days', 'ec_aud': 'Trading · Maintenance · Agencies · Accounting firms · Services',
        'aed': 'AED',
    },
}

# The three businesses and their (illustrative) unpaid invoices
PANES = [
    {'id': 'p1', 'client': 'Gulf Star Supermarket', 'num': 'INV-1047', 'amount': '18,400.00', 'days': 62},
    {'id': 'p2', 'client': 'Marina Heights Towers', 'num': 'AMC-0312', 'amount': '7,250.00', 'days': 45},
    {'id': 'p3', 'client': 'Pearl Beauty Lounge', 'num': 'CH-2503', 'amount': '16,200.00', 'days': 70},
]

FONT_FACES = ''.join(
    f'@font-face {{ font-family: "Plex Arabic"; font-weight: {w}; src: url("assets/fonts/plex-arabic-{w}-arabic.woff2") format("woff2"); unicode-range: U+0600-06FF, U+0750-077F, U+FB50-FDFF, U+FE70-FEFC; }}\n'
    f'@font-face {{ font-family: "Plex Arabic"; font-weight: {w}; src: url("assets/fonts/plex-arabic-{w}-latin.woff2") format("woff2"); unicode-range: U+0000-00FF, U+2000-206F, U+2190-21FF, U+2713; }}\n'
    for w in (400, 600, 700)
)


def duration(path):
    return float(subprocess.check_output(['ffprobe', '-v', 'error', '-show_entries', 'format=duration', '-of', 'csv=p=0', path]).decode())


def scene(cid, dur, lang, css, body, js):
    t = T[lang]
    return f"""<template>
  <style>
{FONT_FACES}
    #root {{ position: absolute; inset: 0; overflow: hidden; direction: {t['dir']}; font-family: "Plex Arabic"; background: #f1f5f9; }}
    .red {{ color: #e11d48; }} .k {{ color: #14b8a6; }}
    .chip {{ display: inline-block; font-size: 30px; font-weight: 600; color: #0b1220; background: #ffffff; border-radius: 999px; padding: 6px 26px; box-shadow: 0 10px 24px rgba(15,23,42,.12); }}
    .title {{ position: absolute; inset-inline-start: 120px; bottom: 70px; background: #ffffff; border-radius: 28px; padding: 18px 44px 26px; box-shadow: 0 24px 50px rgba(15,23,42,.16); }}
    .title .t {{ font-size: 76px; font-weight: 700; color: #0b1220; line-height: 1.25; white-space: nowrap; }}
    .shot {{ position: absolute; left: 0; top: 0; width: 1920px; height: 1200px; transform-origin: 0 0; }}
    .cursor {{ position: absolute; width: 70px; height: 70px; left: 0; top: 0; }}
{css}
  </style>
  <div id="root" data-composition-id="{cid}" data-width="1920" data-height="1080">
{body}
  </div>
  <script>
    const tl = gsap.timeline({{ paused: true }});
    const D = {dur:.2f};
    // camera helper: put screenshot point (fx, fy) at the frame centre at scale s
    const cam = (fx, fy, s) => ({{ x: 960 - fx * s, y: 540 - fy * s, scale: s }});
{js}
    window.__timelines["{cid}"] = tl;
  </script>
</template>
"""


CURSOR = '<svg class="cursor" id="{id}" viewBox="0 0 24 24"><path d="M3 2l17 10-7.5 1.6L9 21z" fill="#ffffff" stroke="#0b1220" stroke-width="1.4" stroke-linejoin="round"/></svg>'


def panes_css():
    return """
    #panes { position: absolute; inset: 0; display: flex; }
    .pane { position: relative; width: 640px; height: 1080px; overflow: hidden; }
    .pane + .pane { border-inline-start: 6px solid #ffffff; }
    .p1 { background: #f3e6cf; } .p2 { background: #e2e8f0; } .p3 { background: #eef6f8; }
    .place { position: absolute; inset-inline-start: 56px; top: 70px; font-size: 96px; font-weight: 700; color: #0b1220; line-height: 1.2; white-space: nowrap; }
    .p3 .place { font-size: 78px; top: 88px; }
    .kind { position: absolute; inset-inline-start: 60px; top: 210px; font-size: 38px; font-weight: 600; color: #475569; }
    .world { position: absolute; inset-inline-end: 0; top: 300px; width: 640px; height: 330px; }
    .inv { position: absolute; inset-inline: 36px; top: 640px; background: #ffffff; border-radius: 28px; padding: 30px 34px; box-shadow: 0 30px 60px rgba(15,23,42,.14); }
    .inv .c { font-size: 36px; font-weight: 700; color: #0b1220; direction: ltr; text-align: start; }
    .inv .n { font-size: 28px; font-weight: 600; color: #0f766e; margin-top: 6px; direction: ltr; text-align: start; }
    .inv .row { display: flex; align-items: center; justify-content: space-between; margin-top: 22px; }
    .inv .a { font-size: 44px; font-weight: 700; color: #0b1220; direction: ltr; font-variant-numeric: tabular-nums; }
    .inv .a small { font-size: 26px; color: #64748b; margin-inline-start: 8px; }
    .pill { position: relative; display: grid; font-size: 25px; font-weight: 700; border-radius: 999px; }
    .pill span { grid-area: 1 / 1; padding: 6px 18px; border-radius: 999px; white-space: nowrap; text-align: center; }
    .pill .o { color: #be123c; background: #fff1f2; border: 2px solid #fecdd3; }
    .pill .d { color: #047857; background: #ecfdf5; border: 2px solid #a7f3d0; opacity: 0; }
    /* worlds */
    .carton { position: absolute; background: #c79a6b; border: 3px solid #a87b4f; border-radius: 6px; }
    .carton::after { content: ""; position: absolute; left: 44%; width: 12%; top: 0; bottom: 0; background: #e9d3a8; }
    .hazard { position: absolute; left: 0; right: 0; top: 0; height: 46px; background: repeating-linear-gradient(45deg, #facc15 0 34px, #0b1220 34px 68px); }
    """


def panes_html(lang, prefix):
    t = T[lang]
    worlds = {
        'p1': '<div class="carton" style="left:330px;top:150px;width:200px;height:150px"></div>'
              '<div class="carton" style="left:110px;top:150px;width:200px;height:150px"></div>'
              '<div class="carton" style="left:220px;top:-10px;width:200px;height:150px"></div>',
        'p2': '<div class="hazard"></div>'
              '<svg style="position:absolute;left:170px;top:70px;width:300px;height:250px" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">'
              '<path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
        'p3': '<svg style="position:absolute;left:40px;top:20px;width:560px;height:310px" viewBox="0 0 560 310">'
              '<rect x="20" y="140" width="70" height="170" fill="#cbd5e1"/><rect x="100" y="60" width="80" height="250" fill="#94a3b8"/>'
              '<rect x="190" y="110" width="60" height="200" fill="#cbd5e1"/><path d="M270 310 V40 L310 0 L350 40 V310 Z" fill="#64748b"/>'
              '<rect x="365" y="90" width="80" height="220" fill="#94a3b8"/><rect x="455" y="160" width="80" height="150" fill="#cbd5e1"/></svg>',
    }
    out = ['    <div id="%spanes" class="clip" data-start="0" data-duration="@@DUR@@" style="position:absolute;inset:0;display:flex">' % prefix]
    for p in PANES:
        i = p['id']
        out.append(f"""      <div class="pane {i}" id="{prefix}{i}">
        <div class="place">{t[i + '_place']}</div><div class="kind">{t[i + '_kind']}</div>
        <div class="world">{worlds[i]}</div>
        <div class="inv" id="{prefix}{i}-inv"><div class="c">{p['client']}</div><div class="n">{p['num']}</div>
          <div class="row"><div class="a">{p['amount']}<small>AED</small></div>
          <div class="pill" id="{prefix}{i}-pill"><span class="o">{t['overdue']} · {p['days']} {t['days']}</span><span class="d" id="{prefix}{i}-paid">✓ {t['paid']}</span></div></div></div>
      </div>""")
    out.append('    </div>')
    return '\n'.join(out)


def build(lang, project):
    t = T[lang]
    X = (lambda x: x) if lang == 'ar' else (lambda x: 1920 - x)
    os.makedirs(os.path.join(project, 'compositions'), exist_ok=True)
    for sub in ('fonts', 'sfx'):
        dst = os.path.join(project, 'assets', sub)
        if not os.path.isdir(dst):
            shutil.copytree(os.path.join(PROMO, 'assets', sub), dst)
    shutil.copy(os.path.join(APP_PUBLIC, 'icon-512.png'), os.path.join(project, 'assets', 'mark.png'))

    vo = {f'vo{i}': duration(os.path.join(project, 'assets', 'voice', f'vo{i}.mp3')) for i in range(1, 10)}

    # scene lengths from the voice
    hook = 0.3 + vo['vo1'] + 0.35 + vo['vo2'] + 0.5
    scenes = [
        ('hook', hook), ('tension', vo['vo3'] + 0.7), ('reveal', vo['vo4'] + 0.7),
        ('trading', max(vo['vo5'] + 0.7, 6.3)), ('upkeep', max(vo['vo6'] + 0.7, 7.6)),
        ('agency', max(vo['vo7'] + 0.7, 6.9)), ('payoff', vo['vo8'] + 1.0), ('endcard', vo['vo9'] + 1.2),
    ]
    starts, s = {}, 0.0
    for name, d in scenes:
        starts[name] = s
        s += d
    total = s
    vo_at = {'vo1': 0.3, 'vo2': 0.3 + vo['vo1'] + 0.35, 'vo3': starts['tension'] + 0.2, 'vo4': starts['reveal'] + 0.3,
             'vo5': starts['trading'] + 0.3, 'vo6': starts['upkeep'] + 0.3, 'vo7': starts['agency'] + 0.3,
             'vo8': starts['payoff'] + 0.3, 'vo9': starts['endcard'] + 0.6}
    D = dict(scenes)

    files = {}

    # 1. hook: three businesses, one problem
    pane_in = [0.15, 0.3 + vo['vo1'] * 0.34, 0.3 + vo['vo1'] * 0.64]
    problem = vo_at['vo2'] + vo['vo2'] * 0.45
    files['hook'] = scene('hook', D['hook'], lang, panes_css() + """
    #h-banner { position: absolute; left: 0; right: 0; bottom: 0; height: 150px; background: #0b1220; display: flex; align-items: center; justify-content: center; }
    #h-banner .b { font-size: 70px; font-weight: 700; color: #ffffff; }
    #h-banner .red { color: #fb7185; }
    """, panes_html(lang, 'h-').replace('@@DUR@@', f'{D["hook"]:.2f}') + f"""
    <div id="h-banner" class="clip" data-start="0" data-duration="{D['hook']:.2f}"><div class="b" id="h-banner-t">{t['banner']}</div></div>""", f"""
    {json.dumps(pane_in)}.forEach((at, i) => {{
      const p = '#h-p' + (i + 1);
      if (i === 0) return;
      tl.fromTo(p + ' .world', {{ clipPath: 'inset(100% 0% 0% 0%)' }}, {{ clipPath: 'inset(0% 0% 0% 0%)', duration: 0.5, ease: 'power3.inOut' }}, at);
      tl.fromTo(p + ' .place', {{ y: 40, opacity: 0 }}, {{ y: 0, opacity: 1, duration: 0.45, ease: 'power3.out' }}, at + 0.2);
      tl.fromTo(p + ' .kind', {{ opacity: 0 }}, {{ opacity: 1, duration: 0.4 }}, at + 0.35);
      tl.fromTo(p + '-inv', {{ y: 80, opacity: 0 }}, {{ y: 0, opacity: 1, duration: 0.5, ease: 'power3.out' }}, at + 0.4);
    }});
    ['#h-p1-pill', '#h-p2-pill', '#h-p3-pill'].forEach((p, i) => tl.fromTo(p, {{ scale: 1 }}, {{ scale: 1.18, duration: 0.18, yoyo: true, repeat: 1, ease: 'power2.out' }}, {problem:.2f} + i * 0.08));
    tl.fromTo('#h-banner', {{ y: 150 }}, {{ y: 0, duration: 0.45, ease: 'power3.out' }}, {problem - 0.1:.2f});
    tl.fromTo('#h-banner-t', {{ opacity: 0 }}, {{ opacity: 1, duration: 0.3 }}, {problem + 0.2:.2f});
    """)

    # 2. tension: call, message, waiting, while the days count up
    beats = [0.25, 0.25 + vo['vo3'] * 0.22, 0.25 + vo['vo3'] * 0.45]
    files['tension'] = scene('tension', D['tension'], lang, """
    #tn-bg { position: absolute; inset: 0; background: #ffffff; }
    #tn-count { position: absolute; inset-inline-end: 140px; top: 250px; width: 620px; text-align: center; }
    #tn-num { font-size: 300px; font-weight: 700; color: #e11d48; line-height: 1; direction: ltr; font-variant-numeric: tabular-nums; }
    #tn-lbl { font-size: 54px; font-weight: 600; color: #475569; margin-top: 10px; }
    .ev { position: absolute; inset-inline-start: 140px; width: 900px; display: flex; align-items: center; gap: 30px; background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 28px; padding: 28px 36px; }
    .ev .ic { width: 96px; height: 96px; border-radius: 24px; display: grid; place-items: center; flex: none; }
    .ev .m { font-size: 46px; font-weight: 700; color: #0b1220; }
    .ev .s { font-size: 32px; font-weight: 600; color: #64748b; margin-top: 4px; }
    #ev1 { top: 200px; } #ev2 { top: 420px; } #ev3 { top: 640px; }
    #ev2 .m { font-size: 38px; }
    """, f"""
    <div id="tn-bg" class="clip" data-start="0" data-duration="{D['tension']:.2f}"></div>
    <div id="tn-count" class="clip" data-start="0" data-duration="{D['tension']:.2f}"><div id="tn-num">30</div><div id="tn-lbl">{t['counter_label']}</div></div>
    <div class="ev clip" id="ev1" data-start="0" data-duration="{D['tension']:.2f}"><div class="ic" style="background:#fff1f2"><svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/><path d="M17 3l4 4M21 3l-4 4"/></svg></div><div><div class="m">{t['call']}</div><div class="s">{t['call_sub']}</div></div></div>
    <div class="ev clip" id="ev2" data-start="0" data-duration="{D['tension']:.2f}"><div class="ic" style="background:#ecfdf5"><svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#059669" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.4 7.4L3 21l2.1-5.6A8.5 8.5 0 1 1 21 11.5z"/></svg></div><div><div class="m">{t['msg']}</div><div class="s">✓ {t['msg_sub']}</div></div></div>
    <div class="ev clip" id="ev3" data-start="0" data-duration="{D['tension']:.2f}"><div class="ic" style="background:#fffbeb"><svg width="52" height="52" viewBox="0 0 24 24" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2h12M6 22h12M7 2v4a5 5 0 0 0 10 0V2M7 22v-4a5 5 0 0 1 10 0v4"/></svg></div><div><div class="m">{t['wait']}</div></div></div>""", f"""
    {json.dumps(beats)}.forEach((at, i) => tl.fromTo('#ev' + (i + 1), {{ x: {(-1 if lang == 'en' else 1) * -60}, opacity: 0 }}, {{ x: 0, opacity: 1, duration: 0.45, ease: 'power3.out' }}, at));
    const n = {{ v: 30 }};
    tl.to(n, {{ v: 90, duration: {vo['vo3'] * 0.9:.2f}, ease: 'power1.in', onUpdate: () => {{ document.getElementById('tn-num').textContent = Math.round(n.v); }} }}, 0.3);
    tl.fromTo('#tn-num', {{ scale: 0.85 }}, {{ scale: 1, duration: 0.4, ease: 'back.out(1.7)' }}, 0.1);
    """)

    # 3. reveal
    files['reveal'] = scene('reveal', D['reveal'], lang, """
    #rv-field { position: absolute; inset: 0; background: #0f766e; }
    #rv-lockup { position: absolute; left: 0; right: 0; top: 300px; display: flex; align-items: center; justify-content: center; gap: 48px; }
    #rv-mark { width: 220px; height: 220px; border-radius: 52px; }
    #rv-word { font-size: 190px; font-weight: 700; color: #ffffff; line-height: 1.15; padding-bottom: 30px; }
    #rv-line { position: absolute; left: 0; right: 0; top: 640px; text-align: center; font-size: 66px; font-weight: 700; color: #ffffff; }
    #rv-line .k { color: #99f6e4; }
    """, f"""
    <div id="rv-field" class="clip" data-start="0" data-duration="{D['reveal']:.2f}"></div>
    <div id="rv-lockup" class="clip" data-start="0" data-duration="{D['reveal']:.2f}"><img id="rv-mark" src="assets/mark.png" alt="Tahselly" /><div id="rv-word">{t['word']}</div></div>
    <div id="rv-line" class="clip" data-start="0" data-duration="{D['reveal']:.2f}">{t['reveal_line']}</div>""", f"""
    tl.fromTo('#rv-field', {{ clipPath: 'circle(0% at 50% 50%)' }}, {{ clipPath: 'circle(75% at 50% 50%)', duration: 0.6, ease: 'expo.inOut' }}, 0);
    tl.fromTo('#rv-mark', {{ scale: 0, rotation: -20 }}, {{ scale: 1, rotation: 0, duration: 0.55, ease: 'back.out(1.8)' }}, 0.25);
    tl.fromTo('#rv-word', {{ clipPath: '{'inset(0% 0% 0% 100%)' if lang == 'ar' else 'inset(0% 100% 0% 0%)'}' }}, {{ clipPath: 'inset(0% 0% 0% 0%)', duration: 0.6, ease: 'power3.out' }}, 0.45);
    tl.fromTo('#rv-line', {{ opacity: 0, y: 30 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, {min(1.6, vo['vo4'] * 0.3):.2f});
    """)

    # 4. trading: Excel import (drop zone at screenshot point (1106, 394) in RTL)
    fx, fy, sc = X(1106), 394, 1.45
    cx, cy = 960 - (fx - fx) * sc, 540  # drop zone lands at frame centre
    chip_start = (X(1500) - 220, 840)
    rows = [('CH-2446', 'Oud Al Sharq Perfumes', '6,150.00', 123), ('CH-2489', 'Marina Dental Clinic', '9,880.00', 119),
            ('CH-2472', 'Marina Dental Clinic', '5,510.00', 89), ('CH-2414', 'Al Noor Trading LLC', '1,370.00', 82),
            ('CH-2503', 'Pearl Beauty Lounge', '16,200.00', 70)]
    rows_html = '\n'.join(f'<div class="tr-row"><span class="num">{n}</span><span class="cl">{c}</span><span class="amt">{a}</span><span class="tp">{t["overdue"]} · {d} {t["days"]}</span></div>' for n, c, a, d in rows)
    files['trading'] = scene('trading', D['trading'], lang, """
    #tr-wrap { position: absolute; inset: 0; overflow: hidden; }
    #tr-file { position: absolute; left: 0; top: 0; display: flex; align-items: center; gap: 18px; padding: 18px 28px; background: #ffffff; border-radius: 20px; box-shadow: 0 24px 50px rgba(15,23,42,.22); font-size: 34px; font-weight: 600; color: #0b1220; direction: ltr; white-space: nowrap; }
    #tr-file .x { width: 58px; height: 58px; border-radius: 12px; background: #107c41; color: #fff; font-size: 26px; font-weight: 700; display: grid; place-items: center; }
    #tr-result { position: absolute; inset: 0; background: #f1f5f9; }
    #tr-card { position: absolute; left: 120px; right: 120px; top: 80px; background: #ffffff; border-radius: 28px; box-shadow: 0 30px 60px rgba(15,23,42,.12); overflow: hidden; }
    #tr-card .head { display: flex; align-items: center; justify-content: space-between; padding: 28px 44px; border-bottom: 1px solid #e2e8f0; }
    #tr-card .head h3 { font-size: 40px; font-weight: 700; color: #0b1220; }
    #tr-card .ok { font-size: 30px; font-weight: 600; color: #047857; background: #ecfdf5; border-radius: 999px; padding: 8px 26px; }
    .tr-row { display: grid; grid-template-columns: 200px 1fr 230px 330px; align-items: center; padding: 0 44px; height: 92px; border-bottom: 1px solid #f1f5f9; font-size: 32px; color: #334155; }
    .tr-row .num { color: #0f766e; font-weight: 700; direction: ltr; text-align: start; }
    .tr-row .cl { direction: ltr; text-align: start; }
    .tr-row .amt { font-weight: 700; color: #0b1220; font-variant-numeric: tabular-nums; direction: ltr; text-align: end; padding-inline-end: 30px; }
    .tr-row .tp { justify-self: start; font-size: 26px; font-weight: 700; color: #be123c; background: #fff1f2; border: 2px solid #fecdd3; border-radius: 999px; padding: 4px 20px; }
    #tr-title .chip { background: #f3e6cf; margin-bottom: 8px; }
    """, f"""
    <div id="tr-wrap" class="clip" data-start="0" data-duration="3.1">
      <img id="tr-shot" class="shot" src="assets/screens/import.png" alt="Excel import" />
      <div id="tr-file"><div class="x">XLS</div>invoices-october.xlsx</div>
      {CURSOR.format(id='tr-cursor')}
    </div>
    <div id="tr-result" class="clip" data-start="3.1" data-duration="{D['trading'] - 3.1:.2f}">
      <div id="tr-card"><div class="head"><h3>{t['tr_head']}</h3><span class="ok">{t['tr_ok']}</span></div>
{rows_html}
      </div>
    </div>
    <div id="tr-title" class="title clip" data-start="0" data-duration="{D['trading']:.2f}" data-layout-allow-occlusion><div class="chip">{t['tr_chip']}</div><div class="t">{t['tr_title']}</div></div>""", f"""
    tl.fromTo('#tr-shot', {{ x: 0, y: 0, scale: 1 }}, {{ ...cam({fx}, {fy}, {sc}), duration: 0.8, ease: 'expo.inOut' }}, 0.4);
    tl.set('#tr-file', {{ x: {chip_start[0]}, y: {chip_start[1]}, opacity: 0 }}, 0);
    tl.set('#tr-cursor', {{ x: {chip_start[0] + 300}, y: {chip_start[1] + 60} }}, 0);
    tl.to('#tr-file', {{ opacity: 1, duration: 0.2 }}, 1.2);
    tl.to('#tr-file', {{ x: {cx - 230:.0f}, y: {cy - 50}, duration: 0.8, ease: 'power2.inOut' }}, 1.4);
    tl.to('#tr-cursor', {{ x: {cx + 70:.0f}, y: {cy + 10}, duration: 0.8, ease: 'power2.inOut' }}, 1.4);
    tl.to('#tr-file', {{ scale: 0.6, opacity: 0, duration: 0.25, ease: 'power2.in' }}, 2.4);
    tl.fromTo('#tr-card', {{ y: 40, opacity: 0 }}, {{ y: 0, opacity: 1, duration: 0.4, ease: 'power3.out' }}, 3.1);
    tl.fromTo('.tr-row', {{ opacity: 0, x: {(-1 if lang == 'ar' else 1) * 40} }}, {{ opacity: 1, x: 0, duration: 0.3, ease: 'power3.out', stagger: 0.16 }}, 3.35);
    tl.fromTo('#tr-title', {{ opacity: 0, y: 30 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, 0.15);
    """)

    # 5. upkeep: schedule once, then WhatsApp list with Send all
    wa_rows = [('Emirates Fitness Club', '+971563334455', 'CH-2508', '260.00', '20-08-2026'),
               ('Marina Dental Clinic', '+971525556677', 'CH-2418', '5,150.00', '21-08-2026'),
               ('Marina Dental Clinic', '+971525556677', 'CH-2439', '15,400.00', '19-07-2026'),
               ('Pearl Beauty Lounge', '+971507778899', 'CH-2401', '6,640.00', '29-08-2026')]
    wa_html = '\n'.join(f"""        <div class="wa-row"><div><div class="nm">{c} <span class="ph">{ph}</span></div><div class="mt"><b>{n}</b> · {a} AED · {t['due']} {d}</div></div>
          <div class="act"><span class="st">{t['final']}</span><span class="btns"><span class="snd" id="wa-snd{i}">{t['wa_send']}</span><span class="dn" id="wa-dn{i}">✓ {t['wa_done']}</span></span></div></div>""" for i, (c, ph, n, a, d) in enumerate(wa_rows))
    sfx, sfy, ssc = X(1133), 344, 1.55
    click_at = 3.9
    files['upkeep'] = scene('upkeep', D['upkeep'], lang, """
    #mt-wrap { position: absolute; inset: 0; overflow: hidden; }
    #mt-list { position: absolute; inset: 0; background: #f1f5f9; }
    #wa-card { position: absolute; left: 110px; right: 110px; top: 60px; background: #ffffff; border-radius: 28px; box-shadow: 0 30px 60px rgba(15,23,42,.12); overflow: hidden; }
    #wa-card .hd { display: flex; align-items: center; justify-content: space-between; gap: 30px; background: #f0fdf4; padding: 26px 40px; border-bottom: 1px solid #dcfce7; }
    #wa-card .hd .l { display: flex; align-items: center; gap: 24px; }
    #wa-card .ic { width: 84px; height: 84px; border-radius: 22px; background: #25D366; display: grid; place-items: center; }
    #wa-card h3 { font-size: 42px; font-weight: 700; color: #0b1220; }
    #wa-card .sub { font-size: 28px; color: #64748b; margin-top: 2px; }
    #wa-all { display: inline-flex; align-items: center; gap: 12px; background: #25D366; color: #fff; font-size: 36px; font-weight: 700; padding: 16px 38px; border-radius: 20px; white-space: nowrap; }
    .wa-row { display: flex; align-items: center; justify-content: space-between; padding: 22px 40px; border-bottom: 1px solid #f1f5f9; }
    .wa-row .nm { font-size: 36px; font-weight: 700; color: #0b1220; direction: ltr; text-align: start; }
    .wa-row .ph { font-size: 24px; font-weight: 400; color: #94a3b8; }
    .wa-row .mt { font-size: 28px; color: #475569; margin-top: 4px; }
    .wa-row .mt b { color: #0f766e; }
    .wa-row .act { display: flex; align-items: center; gap: 24px; }
    .wa-row .st { font-size: 26px; font-weight: 700; color: #be123c; background: #fff1f2; border-radius: 999px; padding: 4px 18px; }
    .btns { display: grid; }
    .btns > span { grid-area: 1 / 1; font-size: 32px; font-weight: 700; border-radius: 16px; padding: 12px 30px; text-align: center; white-space: nowrap; }
    .snd { background: #25D366; color: #fff; }
    .dn { color: #047857; background: #ecfdf5; opacity: 0; }
    #mt-title .chip { background: #e2e8f0; margin-bottom: 8px; }
    #mt-t2 { position: absolute; inset: 18px 44px auto; opacity: 0; }
    """, f"""
    <div id="mt-wrap" class="clip" data-start="0" data-duration="2.9"><img id="mt-shot" class="shot" src="assets/screens/whatsapp-today.png" alt="Reminders center" /></div>
    <div id="mt-list" class="clip" data-start="2.9" data-duration="{D['upkeep'] - 2.9:.2f}">
      <div id="wa-card">
        <div class="hd"><div class="l"><div class="ic"><svg width="48" height="48" viewBox="0 0 24 24" fill="#ffffff"><path d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-1 1.2-.4.2-.6.1a8.2 8.2 0 0 1-4.1-3.6c-.3-.5.3-.5.9-1.6.1-.2 0-.4 0-.5l-.9-2.2c-.2-.6-.5-.5-.7-.5h-.6a1.1 1.1 0 0 0-.8.4 3.4 3.4 0 0 0-1 2.5 5.9 5.9 0 0 0 1.2 3.1 13.4 13.4 0 0 0 5.2 4.6c1.9.8 2.7.9 3.6.7a3.1 3.1 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4c-.1-.2-.3-.3-.7-.5zM12 2a10 10 0 0 0-8.6 15l-1.4 5 5.2-1.4A10 10 0 1 0 12 2z"/></svg></div>
          <div><h3>{t['wa_head']}</h3><div class="sub">{t['wa_sub']}</div></div></div>
          <span id="wa-all"><svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.2" stroke-linejoin="round"><path d="M13 2 4 14h7l-1 8 9-12h-7z"/></svg>{t['wa_all']}</span></div>
{wa_html}
      </div>
      {CURSOR.format(id='mt-cursor')}
    </div>
    <div id="mt-title" class="title clip" data-start="0" data-duration="{D['upkeep']:.2f}" data-layout-allow-occlusion><div class="chip">{t['mt_chip']}</div><div style="position:relative"><div class="t" id="mt-t1">{t['mt_title1']}</div></div></div>
    <div id="mt-title2" class="title clip" data-start="{click_at + 0.6:.2f}" data-duration="{D['upkeep'] - click_at - 0.6:.2f}"><div class="chip">{t['mt_chip']}</div><div class="t">{t['mt_title2']}</div></div>""", f"""
    tl.fromTo('#mt-shot', {{ x: 0, y: 0, scale: 1 }}, {{ ...cam({sfx}, {sfy}, {ssc}), duration: 0.8, ease: 'expo.inOut' }}, 0.4);
    tl.fromTo('#mt-title', {{ opacity: 0, y: 30 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, 0.15);
    tl.fromTo('#wa-card', {{ y: 50, opacity: 0 }}, {{ y: 0, opacity: 1, duration: 0.45, ease: 'power3.out' }}, 2.9);
    tl.fromTo('.wa-row', {{ opacity: 0 }}, {{ opacity: 1, duration: 0.3, stagger: 0.1 }}, 3.05);
    // cursor comes in and presses Send all
    tl.fromTo('#mt-cursor', {{ x: {X(960)}, y: 1100 }}, {{ x: {X(330)}, y: 140, duration: 0.7, ease: 'power2.inOut' }}, 3.1);
    tl.to('#wa-all', {{ scale: 0.92, duration: 0.08, yoyo: true, repeat: 1 }}, {click_at});
    tl.to('#mt-title', {{ opacity: 0, duration: 0.25 }}, {click_at + 0.35});
    tl.fromTo('#mt-title2', {{ opacity: 0, y: 30 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, {click_at + 0.6});
    [0, 1, 2, 3].forEach(i => {{
      tl.to('#wa-snd' + i, {{ opacity: 0, duration: 0.15 }}, {click_at + 0.3} + i * 0.4);
      tl.fromTo('#wa-dn' + i, {{ opacity: 0, scale: 0.6 }}, {{ opacity: 1, scale: 1, duration: 0.3, ease: 'back.out(1.7)' }}, {click_at + 0.3} + i * 0.4);
    }});
    """)

    # 6. agency: owner dashboard, collection rate then aging
    k = (X(989), 288, 1.8)
    a = (X(281), 653, 1.5)
    files['agency'] = scene('agency', D['agency'], lang, """
    #ag-wrap { position: absolute; inset: 0; overflow: hidden; background: #f8fafc; }
    #ag-title .chip { background: #eef6f8; margin-bottom: 8px; }
    """, f"""
    <div id="ag-wrap" class="clip" data-start="0" data-duration="{D['agency']:.2f}"><img id="ag-shot" class="shot" src="assets/screens/owner-dashboard.png" alt="Owner dashboard" /></div>
    <div id="ag-title" class="title clip" data-start="0" data-duration="3.4"><div class="chip">{t['ag_chip']}</div><div class="t">{t['ag_title1']}</div></div>
    <div id="ag-title2" class="title clip" data-start="3.4" data-duration="{D['agency'] - 3.4:.2f}"><div class="chip">{t['ag_chip']}</div><div class="t">{t['ag_title2']}</div></div>""", f"""
    tl.fromTo('#ag-shot', {{ x: 0, y: 0, scale: 1 }}, {{ ...cam({k[0]}, {k[1]}, {k[2]}), duration: 0.8, ease: 'expo.inOut' }}, 0.9);
    tl.to('#ag-shot', {{ ...cam({a[0]}, {a[1]}, {a[2]}), duration: 0.8, ease: 'expo.inOut' }}, 3.3);
    tl.fromTo('#ag-title', {{ opacity: 0, y: 30 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, 0.2);
    tl.to('#ag-title', {{ opacity: 0, duration: 0.2 }}, 3.2);
    tl.fromTo('#ag-title2', {{ opacity: 0, y: 30 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, 3.45);
    """)

    # 7. payoff: the same three invoices flip to Paid
    files['payoff'] = scene('payoff', D['payoff'], lang, panes_css() + """
    #po-line { position: absolute; left: 0; right: 0; bottom: 0; height: 150px; background: #0b1220; display: flex; align-items: center; justify-content: center; font-size: 70px; font-weight: 700; color: #ffffff; }
    #po-line .k { color: #5eead4; }
    """, panes_html(lang, 'po-').replace('@@DUR@@', f'{D["payoff"]:.2f}') + f"""
    <div id="po-line" class="clip" data-start="0" data-duration="{D['payoff']:.2f}"><div id="po-line-t">{t['po_line']}</div></div>""", f"""
    tl.fromTo('#po-panes', {{ scale: 1.06 }}, {{ scale: 1, duration: 0.6, ease: 'power3.out' }}, 0);
    ['#po-p1', '#po-p2', '#po-p3'].forEach((p, i) => {{
      const at = 0.6 + i * 0.45;
      tl.to(p + '-pill .o', {{ opacity: 0, duration: 0.15 }}, at);
      tl.fromTo(p + '-paid', {{ opacity: 0, scale: 0.5 }}, {{ opacity: 1, scale: 1, duration: 0.35, ease: 'back.out(1.7)' }}, at);
    }});
    tl.fromTo('#po-line', {{ y: 150 }}, {{ y: 0, duration: 0.45, ease: 'power3.out' }}, 2.1);
    tl.fromTo('#po-line-t', {{ opacity: 0 }}, {{ opacity: 1, duration: 0.3 }}, 2.35);
    """)

    # 8. end card
    files['endcard'] = scene('endcard', D['endcard'], lang, """
    #ec-field { position: absolute; inset: 0; background: #0f766e; }
    #ec-lockup { position: absolute; left: 0; right: 0; top: 200px; display: flex; align-items: center; justify-content: center; gap: 44px; }
    #ec-mark { width: 200px; height: 200px; border-radius: 48px; }
    #ec-word { font-size: 180px; font-weight: 700; color: #ffffff; line-height: 1.15; padding-bottom: 28px; }
    #ec-claim { position: absolute; left: 80px; right: 80px; top: 480px; text-align: center; font-size: 60px; font-weight: 600; color: #ccfbf1; }
    #ec-price { position: absolute; left: 0; right: 0; top: 600px; text-align: center; font-size: 54px; font-weight: 700; color: #ffffff; }
    #ec-cta-wrap { position: absolute; left: 0; right: 0; top: 720px; display: flex; justify-content: center; align-items: center; gap: 40px; }
    #ec-cta { background: #ffffff; color: #0f766e; font-size: 50px; font-weight: 700; padding: 24px 60px; border-radius: 26px; box-shadow: 0 20px 40px rgba(4,47,46,.35); }
    #ec-url { font-size: 50px; font-weight: 700; color: #ffffff; direction: ltr; }
    #ec-aud { position: absolute; left: 0; right: 0; bottom: 80px; text-align: center; font-size: 32px; color: #99f6e4; }
    """, f"""
    <div id="ec-field" class="clip" data-start="0" data-duration="{D['endcard']:.2f}"></div>
    <div id="ec-lockup" class="clip" data-start="0" data-duration="{D['endcard']:.2f}"><img id="ec-mark" src="assets/mark.png" alt="Tahselly" /><div id="ec-word">{t['word']}</div></div>
    <div id="ec-claim" class="clip" data-start="0" data-duration="{D['endcard']:.2f}">{t['ec_claim']}</div>
    <div id="ec-price" class="clip" data-start="0" data-duration="{D['endcard']:.2f}" data-var-text="price">{t['ec_price']}</div>
    <div id="ec-cta-wrap" class="clip" data-start="0" data-duration="{D['endcard']:.2f}"><div id="ec-cta" data-var-text="cta">{t['ec_cta']}</div><div id="ec-url">tahselly.com</div></div>
    <div id="ec-aud" class="clip" data-start="0" data-duration="{D['endcard']:.2f}">{t['ec_aud']}</div>""", f"""
    tl.fromTo('#ec-field', {{ clipPath: 'circle(0% at 50% 50%)' }}, {{ clipPath: 'circle(75% at 50% 50%)', duration: 0.6, ease: 'expo.inOut' }}, 0);
    tl.fromTo('#ec-mark', {{ scale: 0, rotation: -25 }}, {{ scale: 1, rotation: 0, duration: 0.55, ease: 'back.out(1.8)' }}, 0.2);
    tl.fromTo('#ec-word', {{ clipPath: '{'inset(0% 0% 0% 100%)' if lang == 'ar' else 'inset(0% 100% 0% 0%)'}' }}, {{ clipPath: 'inset(0% 0% 0% 0%)', duration: 0.6, ease: 'power3.out' }}, 0.4);
    tl.fromTo('#ec-claim', {{ opacity: 0, y: 24 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, 1.4);
    tl.fromTo('#ec-price', {{ opacity: 0, y: 24 }}, {{ opacity: 1, y: 0, duration: 0.45, ease: 'power3.out' }}, {vo['vo9'] * 0.55:.2f});
    tl.fromTo('#ec-cta-wrap', {{ scale: 0.7, opacity: 0 }}, {{ scale: 1, opacity: 1, duration: 0.4, ease: 'back.out(1.6)' }}, {vo['vo9'] * 0.7:.2f});
    tl.fromTo('#ec-aud', {{ opacity: 0 }}, {{ opacity: 1, duration: 0.5 }}, {vo['vo9'] * 0.8:.2f});
    """)

    for name, html in files.items():
        with open(os.path.join(project, 'compositions', name + '.html'), 'w', encoding='utf-8', newline='\n') as f:
            f.write(html)

    # sound effects: (file, time, volume)
    s = starts
    sfx = [('pop', pane_in[0] + 0.45, .3), ('pop', pane_in[1] + 0.45, .3), ('pop', pane_in[2] + 0.45, .3),
           ('impact-bass-1', problem - 0.05, .35),
           ('whoosh-short', s['tension'] - 0.05, .3), ('notification', s['tension'] + beats[1] + 0.05, .3),
           ('whoosh', s['reveal'] - 0.05, .4),
           ('whoosh-short', s['trading'] - 0.05, .3), ('pop', s['trading'] + 2.45, .3),
           ('whoosh', s['upkeep'] - 0.05, .3), ('click', s['upkeep'] + click_at + 0.03, .5),
           ('pop', s['upkeep'] + click_at + 0.35, .25), ('pop', s['upkeep'] + click_at + 1.15, .25),
           ('whoosh-short', s['agency'] - 0.05, .3), ('whoosh-short', s['agency'] + 3.3, .2),
           ('pop', s['payoff'] + 0.65, .35), ('pop', s['payoff'] + 1.1, .35), ('pop', s['payoff'] + 1.55, .35),
           ('whoosh', s['endcard'] - 0.05, .35), ('impact-bass-1', s['endcard'] + 0.2, .3)]
    sfx_dur = {f[:-4]: duration(os.path.join(project, 'assets', 'sfx', f)) for f in os.listdir(os.path.join(project, 'assets', 'sfx'))}

    hosts = '\n'.join(
        f'      <div id="{n}" data-composition-id="{n}" data-composition-src="compositions/{n}.html" data-start="{starts[n]:.2f}" data-duration="{d:.2f}" data-track-index="1" data-track-kind="graphics" data-width="1920" data-height="1080"></div>'
        for n, d in scenes)
    voices = '\n'.join(
        f'      <audio id="{v}" class="clip" src="assets/voice/{v}.mp3" data-start="{vo_at[v]:.2f}" data-duration="{vo[v]:.2f}" data-track-index="3" data-volume="1"></audio>'
        for v in vo)
    effects = '\n'.join(
        f'      <audio id="sfx-{i + 1}" class="clip" src="assets/sfx/{f}.mp3" data-start="{max(0, at):.2f}" data-duration="{min(sfx_dur[f], total - at):.2f}" data-track-index="{5 + i % 4}" data-volume="{vol}"></audio>'
        for i, (f, at, vol) in enumerate(sfx))
    index = f"""<!doctype html>
<html lang="{lang}" dir="{t['dir']}">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=1920, height=1080" />
    <script src="https://cdn.jsdelivr.net/npm/gsap@3.14.2/dist/gsap.min.js"></script>
    <style>
{FONT_FACES}
      * {{ margin: 0; padding: 0; box-sizing: border-box; }}
      html, body {{ margin: 0; width: 1920px; height: 1080px; overflow: hidden; background: #f1f5f9; }}
      #root {{ width: 100%; height: 100%; font-family: "Plex Arabic"; direction: {t['dir']}; }}
      #bg {{ position: absolute; inset: 0; background: #f1f5f9; }}
    </style>
  </head>
  <body>
    <div id="root" data-composition-id="main" data-start="0" data-duration="{total:.2f}" data-width="1920" data-height="1080">
      <div id="bg" class="clip" data-start="0" data-duration="{total:.2f}" data-track-index="0"></div>
{hosts}
      <!-- Voiceover (see VOICEOVER.md) -->
{voices}
      <!-- Effects on visible actions, under the voice -->
{effects}
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
    for meta in ('hyperframes.json',):
        shutil.copy(os.path.join(PROMO, meta), os.path.join(project, meta))
    name = os.path.basename(project)
    with open(os.path.join(project, 'meta.json'), 'w') as f:
        json.dump({'id': f'tahselly-{name}', 'name': f'tahselly-{name}', 'createdAt': '2026-10-29T12:00:00.000Z'}, f, indent=2)
    with open(os.path.join(project, 'package.json'), 'w') as f:
        json.dump({'name': f'tahselly-{name}', 'private': True, 'type': 'module', 'scripts': {
            'dev': 'npx --yes hyperframes@0.8.207 preview', 'check': 'npx --yes hyperframes@0.8.207 check',
            'render': 'npx --yes hyperframes@0.8.207 render'}}, f, indent=2)
    print(f'{name}: {total:.1f} s, scenes ' + ', '.join(f'{n} {d:.1f}' for n, d in scenes))


build('ar', HERE)
build('en', os.path.join(MARKETING, 'teaser-en'))
