{{-- Atlas theme (Orion family): live bento hero (spotlight, seconds ring, count-up, filling meters, company ticker), tabbed pill nav, bento sections. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $fonts = 'family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500';
    $topSkills = collect($skills)->sortByDesc('proficiency')->take(5);
    $latest = collect($experiences)->sortByDesc('id')->first();
    $companies = collect($experiences)->sortByDesc('id')->pluck('company')->filter()->unique()->values();
    $education = $education ?? [];
    $logoUrl = \App\Helpers\Utils::getLogo();
    $hasLogo = $logoUrl && !\Illuminate\Support\Str::contains($logoUrl, 'logo/default.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.theme.partials.head')
    <style>
        :root { --bg: #090b10; --card: #121620; --card-2: #181d29; --text: #e8edf6; --muted: #8f9ab0; --line: #232a38; --accent: #3b82f6; --accent-ink: #ffffff; --accent-soft: #15213a; --dark: #3b82f6; --on-dark: #ffffff; color-scheme: dark; }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 90px; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 500 16px/1.6 'Manrope', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1200px, 100% - 24px); margin-inline: auto; }
        .mono, .label { font: 500 12px 'JetBrains Mono', monospace; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }

        /* Masthead and footer: shared with the blog and the store */
        .masthead { position: sticky; top: 0; z-index: 20; background: color-mix(in srgb, var(--card) 88%, transparent); backdrop-filter: blur(12px); border-bottom: 1px solid var(--line); }
        .mast-row { display: flex; align-items: center; gap: 20px; min-height: 68px; position: relative; }
        .brand { display: flex; align-items: center; gap: 10px; text-decoration: none; flex: none; }
        .logo { width: 36px; height: 36px; border-radius: 10px; overflow: hidden; flex: none; background: var(--accent); color: var(--accent-ink); display: grid; place-items: center; font-weight: 800; font-size: 14px; letter-spacing: -.04em; }
        .brand-text { display: flex; flex-direction: column; line-height: 1.1; }
        .brand-text b { font-weight: 800; letter-spacing: -.02em; }
        .brand-text small { font-size: 10px; }
        .nav { list-style: none; flex: 1; display: flex; gap: 22px; margin: 0; padding: 0; }
        .nav a { display: block; padding: 22px 0; text-decoration: none; font-weight: 600; font-size: 14px; color: var(--muted); white-space: nowrap; }
        .nav a:hover { color: var(--text); }
        .nav-mobile-only { display: none; }
        .top-actions { display: flex; align-items: center; gap: 8px; }
        .top-actions .btn { padding: 8px 14px; border-radius: 10px; font-size: 14px; background: var(--card); }
        .top-actions .coffee { padding: 7px 12px; background: #ffdd00; color: #0d0c22; border-color: #ffdd00; white-space: nowrap; }
        .site-foot { margin-top: 48px; border-top: 1px solid var(--line); background: var(--card); }
        .foot { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 16px 28px; padding: 24px 0 28px; color: var(--muted); }
        .foot-brand .logo { width: 28px; height: 28px; border-radius: 8px; font-size: 11px; }
        .foot-links { display: flex; flex-wrap: wrap; gap: 6px 20px; font-weight: 600; font-size: 14px; }
        .foot-links a { color: var(--muted); text-decoration: none; }
        .foot-links a:hover { color: var(--accent); }
        .foot-copy { font-size: 12px; }
        .icon-btn { width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--line); background: var(--card); color: var(--text); cursor: pointer; display: grid; place-items: center; font-size: 16px; }
        .menu-btn { display: none; }

        .bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: minmax(150px, auto); gap: 10px; padding: 24px 0 0; }
        /* Flat tiles: gutters and card colour separate them; hover shows the cursor spotlight and an accent edge, no lift. */
        .tile { background: var(--card); border: 1px solid transparent; border-radius: 20px; padding: 24px; position: relative; overflow: hidden; transition: border-color .2s; }
        .tile:hover { border-color: color-mix(in srgb, var(--accent) 45%, transparent); }
        .bento .tile::before { content: ''; position: absolute; inset: 0; pointer-events: none; background: radial-gradient(260px circle at var(--mx, -999px) var(--my, -999px), color-mix(in srgb, var(--accent) 14%, transparent), transparent 60%); }
        .bento .tile:not(.photo) > * { position: relative; }
        .intro { grid-column: span 2; grid-row: span 2; display: flex; flex-direction: column; justify-content: space-between; gap: 24px; }
        .intro h1 { font-size: clamp(42px, 6vw, 80px); line-height: .94; letter-spacing: -.055em; margin: 10px 0 14px; font-weight: 800; }
        .intro .role { font-size: 20px; margin: 0; color: var(--muted); }
        .intro .role b { color: var(--text); }
        #tagline { display: inline-block; transition: opacity .22s; }
        #tagline.swap { opacity: 0; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; }
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 12px 18px; border-radius: 14px; font: 700 14px 'Manrope', sans-serif; text-decoration: none; border: 1px solid var(--line); background: var(--card-2); color: var(--text); cursor: pointer; }
        .btn.primary { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
        .btn:hover { filter: brightness(.96); }
        .photo { grid-row: span 2; padding: 0; }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .photo .badge { position: absolute; left: 12px; bottom: 12px; right: 12px; padding: 10px 12px; border-radius: 14px; background: color-mix(in srgb, var(--card) 85%, transparent); backdrop-filter: blur(8px); font-weight: 700; font-size: 14px; }
        .avail { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); display: flex; flex-direction: column; justify-content: space-between; }
        .avail .mono { color: var(--accent-ink); opacity: .7; }
        .avail strong { font-size: 22px; line-height: 1.2; }
        .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--accent-ink); display: inline-block; animation: beat 1.6s infinite; }
        @keyframes beat { 50% { transform: scale(1.5); opacity: .5; } }
        .clock { display: flex; flex-direction: column; justify-content: space-between; gap: 10px; }
        .clock-row { display: flex; align-items: center; gap: 12px; }
        .clock .ring { width: 52px; height: 52px; flex: none; }
        .clock strong { font: 700 26px/1 'JetBrains Mono', monospace; letter-spacing: -.04em; font-variant-numeric: tabular-nums; }
        .big { font-size: 52px; font-weight: 800; letter-spacing: -.05em; line-height: 1; display: block; margin-top: 14px; }
        .stack { grid-column: span 2; }
        .stack ul { list-style: none; margin: 14px 0 0; padding: 0; display: grid; gap: 10px; }
        .stack li { display: grid; grid-template-columns: 110px 1fr 40px; gap: 12px; align-items: center; font-weight: 700; font-size: 14px; }
        .stack .meter { height: 8px; border-radius: 8px; background: var(--card-2); overflow: hidden; }
        .stack .meter i { display: block; height: 100%; border-radius: 8px; background: var(--accent); transition: width 1.2s cubic-bezier(.2,.8,.2,1); }
        .stack.pending .meter i { width: 0 !important; }
        .now { grid-column: span 2; background: var(--accent-soft); }
        .now.solo { grid-column: 1 / -1; }
        .shipped { grid-column: span 2; display: flex; flex-direction: column; justify-content: center; gap: 14px; }
        .shipped .track { overflow: hidden; mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
        .ticker { display: flex; gap: 28px; width: max-content; white-space: nowrap; animation: tick 30s linear infinite; font-weight: 800; font-size: 20px; letter-spacing: -.02em; }
        .ticker span::after { content: '·'; margin-left: 28px; color: var(--muted); }
        .shipped:hover .ticker { animation-play-state: paused; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .now .mono { color: var(--muted); }
        .now h3 { font-size: 26px; margin: 12px 0 2px; letter-spacing: -.02em; }

        section.block { padding: 72px 0 12px; }
        .head { display: flex; justify-content: space-between; align-items: end; gap: 16px; margin-bottom: 20px; }
        h2 { font-size: clamp(30px, 4vw, 48px); letter-spacing: -.045em; margin: 6px 0 0; line-height: 1; font-weight: 800; }

        /* Bento lists: the first item leads as a 2x2 tile and the last one widens so a row never ends in a hole. */
        .bento-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 10px; }
        .bento-list > :first-child { grid-column: span 2; grid-row: span 2; }
        .bento-list > :only-child { grid-column: 1 / -1; grid-row: auto; }
        .bento-list > :nth-child(3n+1):last-child { grid-column: 1 / -1; }
        .bento-list > :nth-child(3n+2):last-child { grid-column: span 2; }
        .bento-list > :nth-child(2):last-child { grid-column: auto; grid-row: span 2; }
        /* Text-only lists lead with a wide, single-row tile instead. */
        .bento-list.wide-lead > :first-child { grid-row: auto; background: var(--accent-soft); border-color: transparent; }
        .bento-list.wide-lead > :nth-child(n+2):last-child { grid-column: auto; grid-row: auto; }
        .bento-list.wide-lead > :nth-child(3n):last-child { grid-column: 1 / -1; }
        .bento-list.wide-lead > :nth-child(3n+1):last-child { grid-column: span 2; }
        /* Project cards keep their one-third size even when the last one sits alone on its row. */
        .bento-list.works > :last-child:not(:first-child) { grid-column: auto; grid-row: auto; }
        .jobs > :first-child h3 { font-size: 30px; }
        .job h3 { margin: 10px 0 0; font-size: 22px; letter-spacing: -.02em; }
        .job .pos { margin: 0 0 10px; color: var(--muted); }
        .job ul { margin: 0; padding-left: 18px; color: var(--muted); font-weight: 500; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: var(--card-2); border: 1px solid var(--line); font: 500 12px 'JetBrains Mono', monospace; }

        .work { padding: 0; text-decoration: none; display: flex; flex-direction: column; }
        .work img { width: 100%; flex: 1; min-height: 180px; object-fit: cover; }
        .work .body { padding: 16px 20px 20px; }
        .work h3 { margin: 6px 0 4px; font-size: 19px; }
        .work p { margin: 0; color: var(--muted); font-size: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .svcs > :first-child { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
        .svcs > :first-child .n { background: var(--accent-ink); color: var(--accent); }
        .svcs > :first-child p { color: inherit; opacity: .85; }
        .svcs > :first-child { display: flex; flex-direction: column; justify-content: space-between; gap: 24px; }
        .svcs > :first-child .n { width: 64px; height: 64px; border-radius: 20px; font-size: 26px; }
        .svcs > :first-child h3 { font-size: clamp(28px, 3.4vw, 44px); letter-spacing: -.035em; line-height: 1.05; margin-top: auto; }
        .svcs > :first-child p { font-size: 17px; max-width: 46ch; }
        .svc .n { width: 44px; height: 44px; border-radius: 14px; background: var(--accent); color: var(--accent-ink); display: grid; place-items: center; font-weight: 800; }
        .svc h3 { margin: 16px 0 6px; font-size: 19px; }
        .svc p { margin: 0; color: var(--muted); font-size: 15px; }

        .contact { display: grid; grid-template-columns: 1fr 1.4fr; gap: 10px; }
        .contact .cta { background: var(--accent-soft); }
        .logo img { width: 100%; height: 100%; object-fit: cover; }
        .contact .cta h2 { font-size: clamp(34px, 4vw, 52px); }
        .contact .cta a { color: inherit; }
        .contact-form { display: grid; gap: 10px; }
        .contact-form .label { display: block; margin-bottom: 4px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        input, textarea { width: 100%; font: 500 16px 'Manrope', sans-serif; color: var(--text); background: var(--card-2); border: 1px solid var(--line); border-radius: 14px; padding: 12px 14px; }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }
        .form-note { min-height: 1.5em; color: var(--muted); margin: 0; }

        .reveal { opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .6s, transform .6s; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 1000px) {
            .menu-btn { display: grid; }
            .top-actions { margin-left: auto; }
            .nav { display: none; position: absolute; top: 100%; left: -12px; right: -12px; flex-direction: column; gap: 0; padding: 8px 24px; background: var(--card); border-bottom: 1px solid var(--line); box-shadow: 0 16px 40px rgba(15,23,42,.12); }
            .nav.open { display: flex; }
            .nav a { padding: 12px 0; }
            .bento { grid-template-columns: repeat(2, 1fr); }
            .bento-list { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .bento-list > * { grid-column: auto !important; grid-row: auto !important; }
            .bento-list > :first-child, .bento-list > :nth-child(even):last-child { grid-column: 1 / -1 !important; }
            .bento-list.works > :nth-child(even):last-child:not(:first-child) { grid-column: auto !important; }
        }
        @media (max-width: 700px) {
            .hide-sm { display: none; }
            .mast-row { gap: 0; }
            .top-actions { gap: 6px; }
            .nav-mobile-only { display: block; }
            .foot { flex-direction: column; align-items: flex-start; }
            .bento, .bento-list, .contact, .two { grid-template-columns: 1fr; }
            .intro, .stack, .now, .shipped, .bento-list > * { grid-column: auto !important; grid-row: auto !important; }
            .photo { grid-row: auto; aspect-ratio: 4/5; }
        }
        @media (prefers-reduced-motion: reduce) { .dot, .ticker { animation: none; } .stack .meter i { transition: none; } .tile { transition: none; } html { scroll-behavior: auto; } }
    </style>
    <script src="{{ asset('assets/es-apps.js') }}?v={{ filemtime(public_path('assets/es-apps.js')) }}" defer></script>
    <script src="{{ asset('assets/es-account.js') }}?v={{ filemtime(public_path('assets/es-account.js')) }}" defer></script>
</head>
<body>
    <header class="masthead">
        <div class="wrap mast-row">
            <a class="brand" href="#top" aria-label="{{ $about->name }}, home">
                <span class="logo">@if ($hasLogo)<img src="{{ $logoUrl }}" alt="">@else{{ collect(explode(' ', $about->name))->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}@endif</span>
                <span class="brand-text"><b>{{ $about->name }}</b><small class="mono">Portfolio</small></span>
            </a>
            <ul class="nav" id="menu">
                @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">Experience</a></li>@endif
                @if ($visibility['education'] && count($education))<li><a href="#education">Education</a></li>@endif
                @if ($visibility['projects'] && count($projects))<li><a href="#work">Work</a></li>@endif
                @if ($visibility['services'] && count($services))<li><a href="#services">Services</a></li>@endif
                @if ($visibility['contact'])<li><a href="#contact">Contact</a></li>@endif
                @if ($developersUrl)<li><a href="{{ $developersUrl }}">Developers</a></li>@endif
            </ul>
            <div class="top-actions">
                {{-- The ES app launcher and Sign in, shared by every ES app; es-account.js swaps in the signed-in admin --}}
                @if ($coffeeUrl)<a class="btn coffee" href="{{ $coffeeUrl }}" target="_blank" rel="noopener" aria-label="Buy me a coffee">☕<span class="hide-sm">Buy me a coffee</span></a>@endif
                <es-apps current="portfolio" sign-in-url="{{ url('/admin') }}" dashboard-url="{{ url('/admin/dashboard') }}"></es-apps>
                <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="menu">☰</button>
            </div>
        </div>
    </header>

    <main id="top" class="wrap">
        <div class="bento">
            <div class="tile intro">
                <div>
                    <span class="mono">Hello, I'm</span>
                    <h1>{{ $about->name }}</h1>
                    @if (count($taglines))<p class="role">A <b id="tagline">{{ $taglines[0] }}</b> based in {{ $about->address }}.</p>@endif
                </div>
                <div class="actions">
                    @if ($visibility['contact'])<a class="btn primary" href="#contact">Hire me →</a>@endif
                    @if ($visibility['cv'] && $about->cv)<a class="btn" href="{{ media_url($about->cv) }}" download>CV</a>@endif
                    @foreach ($socials as $social)<a class="btn" href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }}</a>@endforeach
                </div>
            </div>
            @php $portrait = \App\Helpers\Utils::portraitUrl($about); @endphp
            @if ($portrait)
                <div class="tile photo">
                    <img src="{{ $portrait }}" alt="Portrait of {{ $about->name }}" width="300" height="400">
                    <span class="badge">👋 {{ explode(' ', $about->name)[0] }}</span>
                </div>
            @endif
            @if ($availability)
                <div class="tile avail">
                    <span class="mono"><span class="dot"></span>&nbsp; Status</span>
                    <strong>{{ $availability }}</strong>
                </div>
            @endif
            @if ($years)
                <div class="tile">
                    <span class="mono">Years of experience</span>
                    <span class="big"><span data-count="{{ $years }}">{{ $years }}</span>+</span>
                </div>
            @endif
            <div class="tile clock">
                <span class="mono">Local time · {{ $timezoneCity }}</span>
                <div class="clock-row">
                    <svg class="ring" viewBox="0 0 64 64" aria-hidden="true"><circle cx="32" cy="32" r="28" fill="none" stroke="var(--line)" stroke-width="6"/><circle id="ring" cx="32" cy="32" r="28" fill="none" stroke="var(--accent)" stroke-width="6" stroke-linecap="round" stroke-dasharray="176" stroke-dashoffset="176" transform="rotate(-90 32 32)"/></svg>
                    <strong id="clock" data-timezone="{{ $timezone }}">--:--</strong>
                </div>
                <span class="mono">{{ $utcOffset }}@if ($timeNote) · {{ $timeNote }}@endif</span>
            </div>
            @if ($visibility['skills'] && count($skills))
                <div class="tile stack pending">
                    <span class="mono">Top of the stack</span>
                    <ul>
                        @foreach ($topSkills as $skill)
                            <li><span>{{ $skill->name }}</span>@if ($visibility['skillProficiency'])<span class="meter"><i style="width: {{ (int) $skill->proficiency }}%"></i></span><span class="mono">{{ $skill->proficiency }}</span>@endif</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if ($visibility['about'])
                <div class="tile">
                    <span class="mono">About</span>
                    <p style="margin:10px 0 0;color:var(--muted)">{{ \Illuminate\Support\Str::limit(strip_tags($about->description), 150) }}</p>
                </div>
            @endif
            @if ($latest)
                <div class="tile now{{ $companies->count() > 1 ? '' : ' solo' }}">
                    <span class="mono">Currently</span>
                    <h3>{{ $latest->position }} at {{ $latest->company }}</h3>
                    <span class="mono">{{ $latest->period }}</span>
                </div>
            @endif
            @if ($companies->count() > 1)
                <div class="tile shipped">
                    <span class="mono">Worked at</span>
                    <div class="track"><div class="ticker">@foreach ([1, 2] as $copy)@foreach ($companies as $company)<span @if ($copy === 2) aria-hidden="true" @endif>{{ $company }}</span>@endforeach @endforeach</div></div>
                </div>
            @endif
        </div>

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <div class="head"><div><span class="mono">Experience</span><h2>Where I've worked</h2></div></div>
                <div class="bento-list wide-lead jobs">
                    @foreach (collect($experiences)->sortByDesc('id') as $experience)
                        <article class="tile job" data-reveal>
                            <span class="pill">{{ $experience->period }}</span>
                            <h3>{{ $experience->company }}</h3>
                            <p class="pos">{{ $experience->position }}</p>
                            @if ($experience->details){!! $experience->details !!}@endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['education'] && count($education))
            <section class="block" id="education">
                <div class="head"><div><span class="mono">Education</span><h2>Where I studied</h2></div></div>
                <div class="bento-list wide-lead jobs">
                    @foreach (collect($education)->sortByDesc('id') as $study)
                        <article class="tile job" data-reveal>
                            @if ($study->period)<span class="pill">{{ $study->period }}</span>@endif
                            <h3>{{ $study->institution }}</h3>
                            <p class="pos">{{ $study->degree }}@if ($study->department) · {{ $study->department }}@endif</p>
                            @if ($study->cgpa)<p>Grade: {{ $study->cgpa }}</p>@endif
                            @if ($study->thesis)<p>{{ $study->thesis }}</p>@endif
                        </article>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['projects'] && count($projects))
            <section class="block" id="work">
                <div class="head"><div><span class="mono">Work</span><h2>Selected projects</h2></div></div>
                <div class="bento-list works">
                    @foreach ($projects as $project)
                        @php $categories = $project->categories ? (array) safe_json_decode($project->categories) : []; @endphp
                        <a class="tile work" data-reveal @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
                            @if ($project->thumbnail)<img src="{{ media_url($project->thumbnail) }}" alt="" loading="lazy" width="640" height="400">@endif
                            <div class="body">
                                @foreach ($categories as $category)<span class="pill">{{ $category }}</span>@endforeach
                                <h3>{{ $project->title }}</h3>
                                @if ($project->details)<p>{{ strip_tags($project->details) }}</p>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['services'] && count($services))
            <section class="block" id="services">
                <div class="head"><div><span class="mono">Services</span><h2>How I can help</h2></div></div>
                <div class="bento-list svcs">
                    @foreach ($services as $service)
                        <div class="tile svc" data-reveal>
                            <span class="n">{{ $loop->iteration }}</span>
                            <h3>{{ $service->title }}</h3>
                            @if ($service->details)<p>{{ strip_tags($service->details) }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['contact'])
            <section class="block" id="contact">
                <div class="contact">
                    <div class="tile cta" data-reveal>
                        <span class="mono" style="color:inherit;opacity:.65">Contact</span>
                        <h2>Got a project or a role?</h2>
                        <p style="opacity:.8">Contract, part-time or full-time remote. I usually reply within a day.</p>
                        @if ($about->email)<p><a href="mailto:{{ $about->email }}">{{ $about->email }}</a></p>@endif
                        @if ($upworkUrl || $supportUrl)
                            <div class="actions">
                                @if ($upworkUrl)<a class="btn" href="{{ $upworkUrl }}" target="_blank" rel="noopener noreferrer">Hire me on Upwork ↗</a>@endif
                                @if ($supportUrl)<a class="btn" href="{{ $supportUrl }}" target="_blank" rel="noopener noreferrer">Support ↗</a>@endif
                            </div>
                        @endif
                    </div>
                    <div class="tile" data-reveal>@include('frontend.theme.partials.contact-form')</div>
                </div>
            </section>
        @endif
    </main>

    @if ($visibility['footer'])
        <footer class="site-foot">
            <div class="wrap foot">
                <a class="brand foot-brand" href="#top">
                    <span class="logo">@if ($hasLogo)<img src="{{ $logoUrl }}" alt="">@else{{ collect(explode(' ', $about->name))->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}@endif</span>
                    <span class="brand-text"><b>{{ $about->name }}</b><small class="mono">Portfolio</small></span>
                </a>
                <nav class="foot-links" aria-label="Footer">
                    @if ($visibility['projects'] && count($projects))<a href="#work">Work</a>@endif
                    @if ($visibility['services'] && count($services))<a href="#services">Services</a>@endif
                    @foreach ($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }} ↗</a>@endforeach
                    @if ($blogUrl)<a href="{{ $blogUrl }}">Blog ↗</a>@endif
                    @if ($developersUrl)<a href="{{ $developersUrl }}">Developers ↗</a>@endif
                    @if ($storeUrl)<a href="{{ $storeUrl }}">Store ↗</a>@endif
                    @if ($coffeeUrl)<a href="{{ $coffeeUrl }}" target="_blank" rel="noopener">☕ Buy me a coffee ↗</a>@endif
                    @if ($visibility['contact'])<a href="#contact">Contact</a>@endif
                </nav>
                <span class="foot-copy mono">© {{ date('Y') }} {{ $about->name }}</span>
            </div>
        </footer>
    @endif

    @include('frontend.theme.partials.scripts')
    <script>
        (function () {
            var el = document.getElementById('clock');
            if (!el) return;
            var ring = document.getElementById('ring');
            var reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
            var fmt = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', second: '2-digit', timeZone: el.getAttribute('data-timezone') || 'UTC' });
            function tick() {
                var now = new Date();
                el.textContent = fmt.format(now);
                ring.setAttribute('stroke-dashoffset', String(176 - 176 * (now.getSeconds() / 60)));
            }
            tick();
            setInterval(tick, 1000);

            // Years count up, meters fill in, and a soft spotlight follows the cursor across the tiles.
            document.querySelectorAll('[data-count]').forEach(function (n) {
                var to = +n.getAttribute('data-count');
                if (reduce || !to) return;
                var t0 = performance.now();
                (function step(t) {
                    var k = Math.min(1, (t - t0) / 1200);
                    n.textContent = Math.round(to * (1 - Math.pow(1 - k, 3)));
                    if (k < 1) requestAnimationFrame(step);
                })(t0);
            });
            var stack = document.querySelector('.stack.pending');
            if (stack) requestAnimationFrame(function () { requestAnimationFrame(function () { stack.classList.remove('pending'); }); });
            var bento = document.querySelector('.bento');
            if (bento && !reduce && matchMedia('(hover: hover)').matches) {
                bento.addEventListener('pointermove', function (e) {
                    bento.querySelectorAll('.tile').forEach(function (t) {
                        var r = t.getBoundingClientRect();
                        t.style.setProperty('--mx', (e.clientX - r.left) + 'px');
                        t.style.setProperty('--my', (e.clientY - r.top) + 'px');
                    });
                });
            }
        })();
    </script>
</body>
</html>
