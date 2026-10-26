{{-- Nova theme (Orion family): aurora gradient hero, floating glass nav, glowing timeline. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $themeKey = 'nova-theme';
    $fonts = 'family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.theme.partials.head')
    <style>
        :root {
            --bg: #f7f5ff; --surface: rgba(255,255,255,.72); --solid: #ffffff; --text: #17142b; --muted: #5f5a78;
            --line: rgba(23,20,43,.1); --a1: #7c3aed; --a2: #0891b2; --glow: rgba(124,58,237,.18);
            --blob1: #c4b5fd; --blob2: #a5f3fc; --blob3: #fbcfe8;
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #07060f; --surface: rgba(22,19,40,.6); --solid: #120f22; --text: #eeeaff; --muted: #a39dbf;
                --line: rgba(238,234,255,.1); --a1: #a78bfa; --a2: #22d3ee; --glow: rgba(167,139,250,.25);
                --blob1: #5b21b6; --blob2: #0e7490; --blob3: #9d174d;
            }
        }
        :root[data-theme="dark"] {
            --bg: #07060f; --surface: rgba(22,19,40,.6); --solid: #120f22; --text: #eeeaff; --muted: #a39dbf;
            --line: rgba(238,234,255,.1); --a1: #a78bfa; --a2: #22d3ee; --glow: rgba(167,139,250,.25);
            --blob1: #5b21b6; --blob2: #0e7490; --blob3: #9d174d;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 90px; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 400 17px/1.65 'Space Grotesk', system-ui, sans-serif; -webkit-font-smoothing: antialiased; overflow-x: hidden; }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1120px, 100% - 32px); margin-inline: auto; }
        .mono, .label { font-family: 'JetBrains Mono', monospace; font-size: 12px; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }
        .grad { background: linear-gradient(100deg, var(--a1), var(--a2)); -webkit-background-clip: text; background-clip: text; color: transparent; }

        /* Aurora */
        .aurora { position: absolute; inset: 0 0 auto 0; height: 900px; overflow: hidden; pointer-events: none; z-index: 0; }
        .aurora i { position: absolute; width: 560px; height: 560px; border-radius: 50%; filter: blur(90px); opacity: .55; animation: drift 18s ease-in-out infinite alternate; }
        .aurora i:nth-child(1) { background: var(--blob1); top: -160px; left: -120px; }
        .aurora i:nth-child(2) { background: var(--blob2); top: 60px; right: -160px; animation-delay: -6s; }
        .aurora i:nth-child(3) { background: var(--blob3); top: 360px; left: 30%; width: 420px; height: 420px; animation-delay: -12s; opacity: .35; }
        .aurora::after { content: ''; position: absolute; inset: 0; background: linear-gradient(to bottom, transparent 55%, var(--bg)); }
        @keyframes drift { to { transform: translate(80px, 60px) scale(1.15); } }

        /* Floating nav */
        .nav { position: fixed; top: 16px; left: 50%; transform: translateX(-50%); z-index: 30; width: min(860px, 100% - 24px);
            display: flex; align-items: center; gap: 8px; padding: 8px 8px 8px 20px; border-radius: 999px;
            background: var(--surface); border: 1px solid var(--line); backdrop-filter: blur(16px) saturate(1.4); box-shadow: 0 10px 40px rgba(0,0,0,.12); }
        .brand { font-weight: 700; text-decoration: none; white-space: nowrap; }
        .nav ul { list-style: none; display: flex; gap: 2px; margin: 0 0 0 auto; padding: 0; }
        .nav ul a { display: block; padding: 8px 14px; border-radius: 999px; text-decoration: none; color: var(--muted); font-size: 15px; }
        .nav ul a:hover { color: var(--text); background: var(--line); }
        .icon-btn { border: 1px solid var(--line); background: var(--solid); color: var(--text); width: 38px; height: 38px; border-radius: 50%; cursor: pointer; display: grid; place-items: center; flex: none; }
        .menu-btn { display: none; margin-left: auto; }

        /* Hero */
        .hero { position: relative; padding: 170px 0 90px; text-align: center; }
        .hero > .wrap { position: relative; z-index: 1; }
        .avatar { width: 112px; height: 112px; border-radius: 50%; object-fit: cover; margin: 0 auto 26px; padding: 4px; background: linear-gradient(135deg, var(--a1), var(--a2)); box-shadow: 0 0 60px var(--glow); }
        .pill { display: inline-flex; align-items: center; gap: 10px; padding: 7px 14px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); backdrop-filter: blur(8px); }
        .pill::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #22c55e; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(34,197,94,.6); } 100% { box-shadow: 0 0 0 10px rgba(34,197,94,0); } }
        h1 { font-size: clamp(48px, 9vw, 104px); line-height: .98; letter-spacing: -.045em; margin: 24px 0 18px; font-weight: 700; }
        .role { font-size: clamp(20px, 2.8vw, 28px); margin: 0 0 18px; color: var(--muted); }
        #tagline { display: inline-block; transition: opacity .22s, transform .22s; font-weight: 600; }
        #tagline.swap { opacity: 0; transform: translateY(8px); }
        .lead { max-width: 60ch; margin: 0 auto 34px; color: var(--muted); }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; justify-content: center; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 13px 22px; border-radius: 999px; font: 600 15px 'Space Grotesk', sans-serif; text-decoration: none; cursor: pointer;
            border: 1px solid var(--line); background: var(--surface); color: var(--text); backdrop-filter: blur(8px); transition: transform .2s, box-shadow .2s; }
        .btn.primary { border: 0; color: #fff; background: linear-gradient(100deg, var(--a1), var(--a2)); box-shadow: 0 8px 30px var(--glow); }
        .btn:hover { transform: translateY(-2px); }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; margin-top: 70px; text-align: left; }
        .stat { padding: 22px 24px; border-radius: 20px; border: 1px solid var(--line); background: var(--surface); backdrop-filter: blur(10px); }
        .stat strong { display: block; font-size: 34px; letter-spacing: -.03em; }
        .stat span { color: var(--muted); font-size: 14px; }

        section.block { padding: 90px 0; position: relative; }
        .head { text-align: center; margin-bottom: 48px; }
        h2 { font-size: clamp(32px, 5vw, 52px); letter-spacing: -.035em; line-height: 1.05; margin: 10px 0 0; }

        /* Glow timeline */
        .timeline { position: relative; max-width: 860px; margin: 0 auto; padding-left: 34px; }
        .timeline::before { content: ''; position: absolute; left: 8px; top: 6px; bottom: 6px; width: 2px; background: linear-gradient(var(--a1), var(--a2)); opacity: .6; }
        .job { position: relative; margin-bottom: 18px; padding: 24px 26px; border-radius: 20px; border: 1px solid var(--line); background: var(--surface); backdrop-filter: blur(10px); }
        .job::before { content: ''; position: absolute; left: -32px; top: 30px; width: 14px; height: 14px; border-radius: 50%; background: var(--bg); border: 3px solid var(--a1); box-shadow: 0 0 18px var(--a1); }
        .job h3 { margin: 4px 0 0; font-size: 22px; }
        .job .pos { margin: 0 0 10px; color: var(--muted); }
        .job ul { margin: 0; padding-left: 18px; color: var(--muted); }
        .job li::marker { color: var(--a2); }

        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .card { position: relative; border-radius: 22px; padding: 26px; background: var(--surface); border: 1px solid var(--line); backdrop-filter: blur(10px); transition: transform .25s, box-shadow .25s; }
        .card:hover { transform: translateY(-4px); box-shadow: 0 20px 50px var(--glow); }
        .card h3 { margin: 6px 0 8px; font-size: 20px; }
        .card p { margin: 0; color: var(--muted); }
        .project { padding: 0; overflow: hidden; text-decoration: none; display: block; }
        .project img { width: 100%; aspect-ratio: 16/10; object-fit: cover; transition: transform .5s; }
        .project:hover img { transform: scale(1.05); }
        .project .body { padding: 20px 22px 24px; }
        .project p { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .chip { display: inline-block; font: 500 11px 'JetBrains Mono', monospace; padding: 3px 9px; border-radius: 999px; border: 1px solid var(--line); color: var(--a1); margin-right: 4px; }

        .skills { display: flex; flex-wrap: wrap; gap: 10px; justify-content: center; max-width: 900px; margin: 0 auto; }
        .skill { display: inline-flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 999px; border: 1px solid var(--line); background: var(--surface); font-weight: 600; }
        .dots { display: inline-flex; gap: 3px; }
        .dots i { width: 6px; height: 6px; border-radius: 50%; background: var(--line); }
        .dots i.on { background: linear-gradient(var(--a1), var(--a2)); }

        .icon { width: 44px; height: 44px; border-radius: 14px; display: grid; place-items: center; font-weight: 700; color: #fff; background: linear-gradient(135deg, var(--a1), var(--a2)); }

        .contact { display: grid; grid-template-columns: 1fr 1.3fr; gap: 40px; padding: 40px; border-radius: 28px; background: var(--surface); border: 1px solid var(--line); backdrop-filter: blur(12px); }
        .contact h2 { margin-top: 0; }
        .contact-form { display: grid; gap: 12px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        input, textarea { width: 100%; font: inherit; font-size: 16px; color: var(--text); background: var(--solid); border: 1px solid var(--line); border-radius: 14px; padding: 12px 14px; margin-top: 4px; }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--a1); outline-offset: 2px; }
        .form-note { min-height: 1.5em; color: var(--muted); margin: 0; }

        footer { padding: 40px 0 60px; color: var(--muted); text-align: center; }
        footer a { margin: 0 8px; }

        .reveal { opacity: 0; transform: translateY(24px); transition: opacity .7s, transform .7s; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 820px) {
            .menu-btn { display: grid; }
            .nav ul { display: none; position: absolute; top: 60px; left: 0; right: 0; flex-direction: column; padding: 10px; border-radius: 22px; background: var(--solid); border: 1px solid var(--line); }
            .nav ul.open { display: flex; }
            .stats, .contact, .two { grid-template-columns: 1fr; }
            .contact { padding: 24px; }
            .hero { padding-top: 130px; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .aurora i, .pill::before { animation: none; }
            .btn:hover, .card:hover { transform: none; }
        }
    </style>
</head>
<body>
    <nav class="nav" aria-label="Main">
        <a class="brand" href="#top">{{ $about->name }}</a>
        <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false">☰</button>
        <ul id="menu">
            @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">Experience</a></li>@endif
            @if ($visibility['projects'] && count($projects))<li><a href="#work">Work</a></li>@endif
            @if ($visibility['services'] && count($services))<li><a href="#services">Services</a></li>@endif
            @if ($blogUrl)<li><a href="{{ $blogUrl }}">Blog ↗</a></li>@endif
            @if ($visibility['contact'])<li><a href="#contact">Contact</a></li>@endif
        </ul>
        @include('frontend.theme.partials.theme-toggle')
    </nav>

    <main id="top">
        <header class="hero">
            <div class="aurora" aria-hidden="true"><i></i><i></i><i></i></div>
            <div class="wrap">
                @if ($about->avatar)<img class="avatar" src="{{ media_url($about->avatar) }}" alt="Portrait of {{ $about->name }}" width="112" height="112">@endif
                <span class="pill mono">Open to remote contract work · {{ $about->address }}</span>
                <h1><span class="grad">{{ $about->name }}</span></h1>
                @if (count($taglines))<p class="role">I'm a <span id="tagline">{{ $taglines[0] }}</span></p>@endif
                <p class="lead">{{ $about->description }}</p>
                <div class="actions">
                    @if ($visibility['contact'])<a class="btn primary" href="#contact">Start a conversation</a>@endif
                    @if ($visibility['cv'] && $about->cv)<a class="btn" href="{{ media_url($about->cv) }}" download>Download CV</a>@endif
                    @foreach ($socials as $social)
                        <a class="btn" href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }} ↗</a>
                    @endforeach
                </div>
                <div class="stats">
                    @if ($years)<div class="stat"><strong class="grad">{{ $years }}+ yrs</strong><span>shipping production software</span></div>@endif
                    <div class="stat"><strong class="grad">{{ count($experiences) }}</strong><span>companies: telecoms, financial services, SaaS</span></div>
                    <div class="stat"><strong class="grad">UTC+2</strong><span>full overlap with UK and EU hours</span></div>
                </div>
            </div>
        </header>

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <div class="wrap">
                    <div class="head" data-reveal><span class="mono">Experience</span><h2>The journey so far</h2></div>
                    <div class="timeline">
                        @foreach ($experiences as $experience)
                            <article class="job" data-reveal>
                                <span class="mono">{{ $experience->period }}</span>
                                <h3>{{ $experience->company }}</h3>
                                <p class="pos">{{ $experience->position }}</p>
                                @if ($experience->details){!! $experience->details !!}@endif
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['skills'] && count($skills))
            <section class="block" id="skills">
                <div class="wrap">
                    <div class="head" data-reveal><span class="mono">Toolkit</span><h2>What I build with</h2></div>
                    <div class="skills" data-reveal>
                        @foreach ($skills as $skill)
                            @php $level = (int) round($skill->proficiency / 20); @endphp
                            <span class="skill" title="{{ $skill->proficiency }}%">{{ $skill->name }}
                                <span class="dots" aria-label="{{ $level }} of 5">@for ($d = 1; $d <= 5; $d++)<i class="{{ $d <= $level ? 'on' : '' }}"></i>@endfor</span>
                            </span>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['projects'] && count($projects))
            <section class="block" id="work">
                <div class="wrap">
                    <div class="head" data-reveal><span class="mono">Work</span><h2>Selected projects</h2></div>
                    <div class="cards">
                        @foreach ($projects as $project)
                            @php $categories = $project->categories ? (array) safe_json_decode($project->categories) : []; @endphp
                            <a class="card project" data-reveal @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
                                @if ($project->thumbnail)<img src="{{ media_url($project->thumbnail) }}" alt="" loading="lazy" width="640" height="400">@endif
                                <div class="body">
                                    @foreach ($categories as $category)<span class="chip">{{ $category }}</span>@endforeach
                                    <h3>{{ $project->title }}</h3>
                                    @if ($project->details)<p>{{ strip_tags($project->details) }}</p>@endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['services'] && count($services))
            <section class="block" id="services">
                <div class="wrap">
                    <div class="head" data-reveal><span class="mono">Services</span><h2>How I can help</h2></div>
                    <div class="cards">
                        @foreach ($services as $service)
                            <div class="card" data-reveal>
                                <span class="icon" aria-hidden="true">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3>{{ $service->title }}</h3>
                                @if ($service->details)<p>{{ strip_tags($service->details) }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['contact'])
            <section class="block" id="contact">
                <div class="wrap">
                    <div class="contact" data-reveal>
                        <div>
                            <span class="mono">Contact</span>
                            <h2>Let's build <span class="grad">something good</span></h2>
                            <p class="lead" style="margin:14px 0 20px">Contract, part-time or full-time remote roles. I usually reply within a day.</p>
                            @if ($about->email)<p><a href="mailto:{{ $about->email }}">{{ $about->email }}</a></p>@endif
                        </div>
                        @include('frontend.theme.partials.contact-form')
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer>
        <div class="wrap">
            © {{ date('Y') }} {{ $about->name }} ·
            @if ($blogUrl)<a href="{{ $blogUrl }}">Blog</a>@endif
            @foreach ($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }}</a>@endforeach
        </div>
    </footer>

    @include('frontend.theme.partials.scripts')
</body>
</html>
