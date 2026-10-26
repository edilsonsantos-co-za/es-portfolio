{{-- Atlas theme (Orion family): bento-grid hero with live local clock, tabbed pill nav, card-driven sections. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $themeKey = 'atlas-theme';
    $fonts = 'family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500';
    $topSkills = collect($skills)->sortByDesc('proficiency')->take(5);
    $latest = collect($experiences)->sortByDesc('id')->first();
    $logoUrl = \App\Helpers\Utils::getLogo();
    $hasLogo = $logoUrl && !\Illuminate\Support\Str::contains($logoUrl, 'logo/default.png');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.theme.partials.head')
    <style>
        :root { --bg: #eef1f6; --card: #ffffff; --card-2: #f5f7fb; --text: #0f172a; --muted: #5b6478; --line: #dde3ee; --accent: #2563eb; --accent-ink: #ffffff; --accent-soft: #e6eeff; --dark: #0f172a; --on-dark: #f1f5f9; }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) { --bg: #090b10; --card: #121620; --card-2: #181d29; --text: #e8edf6; --muted: #8f9ab0; --line: #232a38; --accent: #3b82f6; --accent-ink: #ffffff; --accent-soft: #15213a; --dark: #3b82f6; --on-dark: #ffffff; }
        }
        :root[data-theme="dark"] { --bg: #090b10; --card: #121620; --card-2: #181d29; --text: #e8edf6; --muted: #8f9ab0; --line: #232a38; --accent: #3b82f6; --accent-ink: #ffffff; --accent-soft: #15213a; --dark: #3b82f6; --on-dark: #ffffff; }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 90px; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 500 16px/1.6 'Manrope', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1200px, 100% - 24px); margin-inline: auto; }
        .mono, .label { font: 500 12px 'JetBrains Mono', monospace; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); }

        .top { position: sticky; top: 0; z-index: 20; padding: 12px 0; background: color-mix(in srgb, var(--bg) 85%, transparent); backdrop-filter: blur(10px); }
        .top .wrap { display: flex; align-items: center; gap: 12px; }
        .logo { width: 40px; height: 40px; border-radius: 12px; overflow: hidden; flex: none; background: var(--accent); color: var(--accent-ink); display: grid; place-items: center; font-weight: 800; text-decoration: none; letter-spacing: -.04em; }
        .tabs { list-style: none; display: flex; gap: 4px; margin: 0 auto; padding: 4px; border-radius: 14px; background: var(--card); border: 1px solid var(--line); }
        .tabs a { display: block; padding: 8px 16px; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 14px; color: var(--muted); }
        .tabs a:hover { background: var(--card-2); color: var(--text); }
        .icon-btn { width: 40px; height: 40px; border-radius: 12px; border: 1px solid var(--line); background: var(--card); color: var(--text); cursor: pointer; display: grid; place-items: center; font-size: 16px; }
        .menu-btn { display: none; }

        .bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: minmax(150px, auto); gap: 12px; padding: 12px 0 0; }
        .tile { background: var(--card); border: 1px solid var(--line); border-radius: 24px; padding: 24px; position: relative; overflow: hidden; transition: transform .25s, box-shadow .25s; }
        .tile:hover { transform: translateY(-3px); box-shadow: 0 16px 40px rgba(0,0,0,.08); }
        .intro { grid-column: span 2; grid-row: span 2; display: flex; flex-direction: column; justify-content: space-between; gap: 24px; }
        .intro h1 { font-size: clamp(40px, 5.4vw, 68px); line-height: .98; letter-spacing: -.045em; margin: 10px 0 14px; font-weight: 800; }
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
        .clock strong { display: block; font: 700 44px/1 'JetBrains Mono', monospace; letter-spacing: -.04em; margin-top: 18px; }
        .big { font-size: 52px; font-weight: 800; letter-spacing: -.05em; line-height: 1; display: block; margin-top: 14px; }
        .stack { grid-column: span 2; }
        .stack ul { list-style: none; margin: 14px 0 0; padding: 0; display: grid; gap: 10px; }
        .stack li { display: grid; grid-template-columns: 110px 1fr 40px; gap: 12px; align-items: center; font-weight: 700; font-size: 14px; }
        .stack .meter { height: 8px; border-radius: 8px; background: var(--card-2); overflow: hidden; }
        .stack .meter i { display: block; height: 100%; border-radius: 8px; background: var(--accent); }
        .now { grid-column: 1 / -1; background: var(--accent-soft); }
        .now .mono { color: var(--muted); }
        .now h3 { font-size: 26px; margin: 12px 0 2px; letter-spacing: -.02em; }

        section.block { padding: 72px 0 12px; }
        .head { display: flex; justify-content: space-between; align-items: end; gap: 16px; margin-bottom: 20px; }
        h2 { font-size: clamp(30px, 4vw, 48px); letter-spacing: -.045em; margin: 6px 0 0; line-height: 1; font-weight: 800; }

        .jobs { display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; }
        .job h3 { margin: 10px 0 0; font-size: 22px; letter-spacing: -.02em; }
        .job .pos { margin: 0 0 10px; color: var(--muted); }
        .job ul { margin: 0; padding-left: 18px; color: var(--muted); font-weight: 500; }
        .pill { display: inline-block; padding: 4px 10px; border-radius: 999px; background: var(--card-2); border: 1px solid var(--line); font: 500 12px 'JetBrains Mono', monospace; }

        .works { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .works .tile:first-child { grid-column: span 2; grid-row: span 2; }
        .work { padding: 0; text-decoration: none; display: flex; flex-direction: column; }
        .work img { width: 100%; flex: 1; min-height: 180px; object-fit: cover; }
        .work .body { padding: 16px 20px 20px; }
        .work h3 { margin: 6px 0 4px; font-size: 19px; }
        .work p { margin: 0; color: var(--muted); font-size: 14px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }

        .svcs { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .svc .n { width: 44px; height: 44px; border-radius: 14px; background: var(--accent); color: var(--accent-ink); display: grid; place-items: center; font-weight: 800; }
        .svc h3 { margin: 16px 0 6px; font-size: 19px; }
        .svc p { margin: 0; color: var(--muted); font-size: 15px; }

        .contact { display: grid; grid-template-columns: 1fr 1.4fr; gap: 12px; }
        .contact .cta { background: var(--accent-soft); }
        .logo img { width: 100%; height: 100%; object-fit: cover; }
        .signin { padding: 9px 14px; border-radius: 12px; font-size: 14px; }
        .contact .cta h2 { font-size: clamp(34px, 4vw, 52px); }
        .contact .cta a { color: inherit; }
        .contact-form { display: grid; gap: 10px; }
        .contact-form .label { display: block; margin-bottom: 4px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        input, textarea { width: 100%; font: 500 16px 'Manrope', sans-serif; color: var(--text); background: var(--card-2); border: 1px solid var(--line); border-radius: 14px; padding: 12px 14px; }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }
        .form-note { min-height: 1.5em; color: var(--muted); margin: 0; }
        footer { padding: 40px 0; text-align: center; color: var(--muted); font-size: 14px; }
        footer a { margin: 0 6px; }

        .reveal { opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .6s, transform .6s; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 1000px) {
            .bento { grid-template-columns: repeat(2, 1fr); }
            .works, .svcs { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 700px) {
            .menu-btn { display: grid; margin-left: auto; }
            .tabs { display: none; position: absolute; top: 64px; left: 12px; right: 12px; flex-direction: column; }
            .tabs.open { display: flex; }
            .bento, .jobs, .works, .svcs, .contact, .two { grid-template-columns: 1fr; }
            .intro, .stack, .works .tile:first-child { grid-column: auto; grid-row: auto; }
            .photo { grid-row: auto; aspect-ratio: 4/5; }
        }
        @media (prefers-reduced-motion: reduce) { .dot { animation: none; } .tile:hover { transform: none; } html { scroll-behavior: auto; } }
    </style>
</head>
<body>
    <header class="top">
        <div class="wrap">
            <a class="logo" href="#top" aria-label="{{ $about->name }}, home">@if ($hasLogo)<img src="{{ $logoUrl }}" alt="">@else{{ collect(explode(' ', $about->name))->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}@endif</a>
            <ul class="tabs" id="menu">
                @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">Experience</a></li>@endif
                @if ($visibility['projects'] && count($projects))<li><a href="#work">Work</a></li>@endif
                @if ($visibility['services'] && count($services))<li><a href="#services">Services</a></li>@endif
                @if ($blogUrl)<li><a href="{{ $blogUrl }}">Blog ↗</a></li>@endif
                @if ($storeUrl)<li><a href="{{ $storeUrl }}">Store ↗</a></li>@endif
                @if ($visibility['contact'])<li><a href="#contact">Contact</a></li>@endif
            </ul>
            <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false">☰</button>
            @include('frontend.theme.partials.theme-toggle')
            <a class="btn signin" href="{{ url('/admin') }}">Sign in</a>
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
            @if ($about->avatar)
                <div class="tile photo">
                    <img src="{{ media_url($about->avatar) }}" alt="Portrait of {{ $about->name }}" width="300" height="400">
                    <span class="badge">👋 {{ explode(' ', $about->name)[0] }}</span>
                </div>
            @endif
            <div class="tile avail">
                <span class="mono"><span class="dot"></span>&nbsp; Status</span>
                <strong>Open to remote contract work</strong>
            </div>
            <div class="tile clock">
                <span class="mono">Local time · Johannesburg</span>
                <strong id="clock">--:--</strong>
                <span class="mono">UTC+2 · overlaps UK and EU</span>
            </div>
            @if ($years)
                <div class="tile">
                    <span class="mono">In production</span>
                    <span class="big">{{ $years }}+</span>
                    <span class="mono">years</span>
                </div>
            @endif
            @if ($visibility['skills'] && count($skills))
                <div class="tile stack">
                    <span class="mono">Top of the stack</span>
                    <ul>
                        @foreach ($topSkills as $skill)
                            <li><span>{{ $skill->name }}</span><span class="meter"><i style="width: {{ (int) $skill->proficiency }}%"></i></span><span class="mono">{{ $skill->proficiency }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <div class="tile">
                <span class="mono">About</span>
                <p style="margin:10px 0 0;color:var(--muted)">{{ \Illuminate\Support\Str::limit($about->description, 150) }}</p>
            </div>
            @if ($latest)
                <div class="tile now">
                    <span class="mono">Currently</span>
                    <h3>{{ $latest->position }} at {{ $latest->company }}</h3>
                    <span class="mono">{{ $latest->period }}</span>
                </div>
            @endif
        </div>

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <div class="head"><div><span class="mono">Experience</span><h2>Where I've worked</h2></div></div>
                <div class="jobs">
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

        @if ($visibility['projects'] && count($projects))
            <section class="block" id="work">
                <div class="head"><div><span class="mono">Work</span><h2>Selected projects</h2></div></div>
                <div class="works">
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
                <div class="svcs">
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
                    </div>
                    <div class="tile" data-reveal>@include('frontend.theme.partials.contact-form')</div>
                </div>
            </section>
        @endif
    </main>

    <footer>
        © {{ date('Y') }} {{ $about->name }} ·
        @if ($blogUrl)<a href="{{ $blogUrl }}">Blog</a>@endif
        @if ($storeUrl)<a href="{{ $storeUrl }}">Store</a>@endif
        @foreach ($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }}</a>@endforeach
    </footer>

    @include('frontend.theme.partials.scripts')
    <script>
        (function () {
            var el = document.getElementById('clock');
            if (!el) return;
            var fmt = new Intl.DateTimeFormat('en-GB', { hour: '2-digit', minute: '2-digit', timeZone: 'Africa/Johannesburg' });
            function tick() { el.textContent = fmt.format(new Date()); }
            tick();
            setInterval(tick, 15000);
        })();
    </script>
</body>
</html>
