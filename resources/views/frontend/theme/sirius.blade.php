{{-- Sirius theme (Orion family): blueprint grid, animated system-architecture diagram, cursor spotlight, bracketed cards. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $themeKey = 'sirius-theme';
    $fonts = 'family=IBM+Plex+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500';
    $index = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.theme.partials.head')
    <style>
        :root { --bg: #f5f8fc; --card: #ffffff; --text: #0b1f3a; --muted: #4f6382; --line: rgba(21,74,153,.16); --grid: rgba(21,74,153,.07); --accent: #1557c0; --accent-2: #0ea5a4; --spot: rgba(21,87,192,.08); }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) { --bg: #071a33; --card: #0b2344; --text: #e6f0ff; --muted: #92a9cc; --line: rgba(125,180,255,.22); --grid: rgba(125,180,255,.07); --accent: #6cb4ff; --accent-2: #4fe0d0; --spot: rgba(108,180,255,.1); }
        }
        :root[data-theme="dark"] { --bg: #071a33; --card: #0b2344; --text: #e6f0ff; --muted: #92a9cc; --line: rgba(125,180,255,.22); --grid: rgba(125,180,255,.07); --accent: #6cb4ff; --accent-2: #4fe0d0; --spot: rgba(108,180,255,.1); }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 80px; }
        body {
            margin: 0; color: var(--text); font: 400 17px/1.65 'IBM Plex Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased;
            background-color: var(--bg);
            background-image: linear-gradient(var(--grid) 1px, transparent 1px), linear-gradient(90deg, var(--grid) 1px, transparent 1px),
                linear-gradient(var(--grid) 1px, transparent 1px), linear-gradient(90deg, var(--grid) 1px, transparent 1px);
            background-size: 120px 120px, 120px 120px, 24px 24px, 24px 24px;
        }
        body::before { content: ''; position: fixed; inset: 0; pointer-events: none; z-index: 0;
            background: radial-gradient(500px circle at var(--mx, 50%) var(--my, 30%), var(--spot), transparent 60%); }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1160px, 100% - 32px); margin-inline: auto; position: relative; z-index: 1; }
        .mono, .label { font: 500 12px 'IBM Plex Mono', monospace; letter-spacing: .08em; text-transform: uppercase; color: var(--muted); }

        .nav { position: sticky; top: 0; z-index: 20; border-bottom: 1px solid var(--line); background: color-mix(in srgb, var(--bg) 88%, transparent); backdrop-filter: blur(8px); }
        .nav .wrap { display: flex; align-items: center; gap: 24px; height: 62px; }
        .brand { font-weight: 700; text-decoration: none; display: flex; align-items: center; gap: 10px; }
        .brand svg { color: var(--accent); }
        .nav ul { list-style: none; display: flex; gap: 26px; margin: 0 0 0 auto; padding: 0; counter-reset: nav; }
        .nav ul a { text-decoration: none; font-size: 15px; color: var(--muted); }
        .nav ul a::before { counter-increment: nav; content: '0' counter(nav) ' '; font: 500 11px 'IBM Plex Mono', monospace; color: var(--accent); }
        .nav ul a:hover { color: var(--text); }
        .icon-btn { width: 36px; height: 36px; border: 1px solid var(--line); background: var(--card); color: var(--text); border-radius: 6px; cursor: pointer; }
        .menu-btn { display: none; }

        .hero { padding: 80px 0 60px; }
        .hero .grid { display: grid; grid-template-columns: 1.05fr 1fr; gap: 48px; align-items: center; }
        .spec { display: inline-flex; gap: 14px; padding: 6px 12px; border: 1px dashed var(--accent); border-radius: 4px; }
        .spec b { color: var(--accent-2); font-weight: 500; }
        h1 { font-size: clamp(44px, 6.4vw, 78px); line-height: 1.02; letter-spacing: -.035em; margin: 22px 0 14px; font-weight: 700; }
        .role { font-size: clamp(19px, 2.2vw, 24px); color: var(--muted); margin: 0 0 16px; }
        #tagline { color: var(--accent); font-weight: 600; transition: opacity .22s; }
        #tagline.swap { opacity: 0; }
        .lead { color: var(--muted); max-width: 54ch; margin: 0 0 28px; }
        .actions { display: flex; gap: 12px; flex-wrap: wrap; }
        .btn { display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 6px; font: 600 15px 'IBM Plex Sans', sans-serif; text-decoration: none; border: 1px solid var(--line); background: var(--card); color: var(--text); cursor: pointer; }
        .btn.primary { background: var(--accent); border-color: var(--accent); color: var(--bg); }
        .btn:hover { border-color: var(--accent); }

        /* Diagram */
        .diagram { position: relative; border: 1px solid var(--line); background: color-mix(in srgb, var(--card) 70%, transparent); border-radius: 8px; padding: 18px; }
        .diagram .mono { position: absolute; top: 10px; left: 14px; }
        .diagram svg { width: 100%; height: auto; display: block; }
        .diagram .node rect { fill: var(--card); stroke: var(--accent); stroke-width: 1.5; }
        .diagram .node.alt rect { stroke: var(--accent-2); }
        .diagram .node text { fill: var(--text); font: 600 13px 'IBM Plex Sans', sans-serif; }
        .diagram .node text.sub { fill: var(--muted); font: 400 10px 'IBM Plex Mono', monospace; }
        .diagram .flow { fill: none; stroke: var(--accent); stroke-width: 1.5; stroke-dasharray: 6 6; animation: flow 1.2s linear infinite; }
        .diagram .flow.alt { stroke: var(--accent-2); animation-duration: 1.8s; }
        .diagram .avatar-ring { fill: none; stroke: var(--accent-2); stroke-width: 2; }
        @keyframes flow { to { stroke-dashoffset: -24; } }

        .metrics { display: grid; grid-template-columns: repeat(3, 1fr); border: 1px solid var(--line); margin-top: 56px; background: var(--card); border-radius: 6px; }
        .metrics div { padding: 18px 22px; }
        .metrics div + div { border-left: 1px solid var(--line); }
        .metrics strong { display: block; font: 600 28px 'IBM Plex Mono', monospace; color: var(--accent); }
        .metrics span { color: var(--muted); font-size: 14px; }

        section.block { padding: 80px 0; }
        .head { display: flex; align-items: baseline; gap: 18px; margin-bottom: 30px; border-bottom: 1px solid var(--line); padding-bottom: 16px; }
        .head .idx { font: 500 14px 'IBM Plex Mono', monospace; color: var(--accent); }
        h2 { font-size: clamp(28px, 3.8vw, 42px); letter-spacing: -.03em; margin: 0; line-height: 1.1; }

        .bracket { position: relative; background: var(--card); border: 1px solid var(--line); border-radius: 4px; padding: 24px 26px; }
        .bracket::before, .bracket::after { content: ''; position: absolute; width: 14px; height: 14px; border: 2px solid var(--accent); transition: all .25s; }
        .bracket::before { top: -2px; left: -2px; border-right: 0; border-bottom: 0; }
        .bracket::after { bottom: -2px; right: -2px; border-left: 0; border-top: 0; }
        .bracket:hover::before, .bracket:hover::after { width: 28px; height: 28px; }

        .exp { display: grid; gap: 16px; }
        .exp .bracket { display: grid; grid-template-columns: 190px 1fr; gap: 24px; }
        .exp h3 { margin: 0; font-size: 21px; }
        .exp .pos { margin: 0 0 8px; color: var(--muted); }
        .exp ul { margin: 0; padding-left: 18px; color: var(--muted); }
        .exp li::marker { content: '→ '; color: var(--accent); }

        .grid3 { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 18px; }
        .proj { padding: 0; text-decoration: none; display: block; }
        .proj img { width: 100%; aspect-ratio: 16/10; object-fit: cover; border-bottom: 1px solid var(--line); }
        .proj .body { padding: 18px 20px 22px; }
        .proj h3 { margin: 6px 0; font-size: 19px; }
        .proj p, .svc p { margin: 0; color: var(--muted); font-size: 15px; }
        .proj p { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .svc h3 { margin: 8px 0 6px; font-size: 19px; }

        .skills { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 0; border: 1px solid var(--line); background: var(--card); border-radius: 4px; }
        .skill { padding: 14px 18px; border-right: 1px solid var(--line); border-bottom: 1px solid var(--line); display: flex; justify-content: space-between; align-items: center; gap: 10px; font-weight: 600; }
        .skill svg { flex: none; }
        .skill circle.track { stroke: var(--line); }
        .skill circle.val { stroke: var(--accent); transition: stroke-dashoffset 1s; }

        .contact { display: grid; grid-template-columns: 1fr 1.3fr; gap: 36px; }
        .contact-form { display: grid; gap: 12px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        input, textarea { width: 100%; font: inherit; font-size: 16px; color: var(--text); background: var(--bg); border: 1px solid var(--line); border-radius: 4px; padding: 12px 14px; margin-top: 4px; }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .form-note { min-height: 1.5em; color: var(--muted); margin: 0; }
        footer { border-top: 1px solid var(--line); padding: 28px 0 44px; color: var(--muted); }
        footer .wrap { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 14px; }
        footer a { margin-left: 14px; }

        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .6s, transform .6s; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 900px) {
            .hero .grid, .contact { grid-template-columns: 1fr; }
            .exp .bracket { grid-template-columns: 1fr; gap: 6px; }
            .menu-btn { display: block; margin-left: auto; }
            .nav ul { display: none; position: absolute; top: 62px; left: 0; right: 0; flex-direction: column; gap: 0; padding: 8px 16px 16px; background: var(--bg); border-bottom: 1px solid var(--line); }
            .nav ul.open { display: flex; }
            .nav ul a { display: block; padding: 10px 0; }
        }
        @media (max-width: 560px) { .metrics, .two { grid-template-columns: 1fr; } .metrics div + div { border-left: 0; border-top: 1px solid var(--line); } }
        @media (prefers-reduced-motion: reduce) { .diagram .flow { animation: none; } html { scroll-behavior: auto; } }
    </style>
</head>
<body>
    <header class="nav">
        <div class="wrap">
            <a class="brand" href="#top">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 2l3 7 7 3-7 3-3 7-3-7-7-3 7-3z"/></svg>
                {{ $about->name }}
            </a>
            <ul id="menu">
                @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">Experience</a></li>@endif
                @if ($visibility['projects'] && count($projects))<li><a href="#work">Work</a></li>@endif
                @if ($visibility['services'] && count($services))<li><a href="#services">Services</a></li>@endif
                @if ($blogUrl)<li><a href="{{ $blogUrl }}">Blog ↗</a></li>@endif
                @if ($visibility['contact'])<li><a href="#contact">Contact</a></li>@endif
            </ul>
            <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false">☰</button>
            <button class="icon-btn" type="button" id="theme-btn" aria-label="Toggle colour theme">◐</button>
        </div>
    </header>

    <main id="top">
        <section class="hero">
            <div class="wrap">
                <div class="grid">
                    <div>
                        <span class="spec mono"><span>REV {{ date('Y.m') }}</span><b>● AVAILABLE</b><span>UTC+2</span></span>
                        <h1>{{ $about->name }}</h1>
                        @if (count($taglines))<p class="role">I'm a <span id="tagline">{{ $taglines[0] }}</span></p>@endif
                        <p class="lead">{{ $about->description }}</p>
                        <div class="actions">
                            @if ($visibility['contact'])<a class="btn primary" href="#contact">Discuss a project</a>@endif
                            @if ($visibility['cv'] && $about->cv)<a class="btn" href="{{ media_url($about->cv) }}" download>Download CV</a>@endif
                            @foreach ($socials as $social)<a class="btn" href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }} ↗</a>@endforeach
                        </div>
                    </div>
                    <figure class="diagram" style="margin:0" aria-label="Diagram of the kind of systems I build: clients, an API layer, workers and a database">
                        <span class="mono">fig. 1 · what I build</span>
                        <svg viewBox="0 0 480 360" role="img" aria-hidden="true">
                            <path class="flow" d="M110 80 C 180 80, 180 180, 230 180" />
                            <path class="flow" d="M110 180 L 230 180" />
                            <path class="flow" d="M110 280 C 180 280, 180 180, 230 180" />
                            <path class="flow alt" d="M330 180 C 370 180, 370 100, 400 100" />
                            <path class="flow alt" d="M330 180 C 370 180, 370 260, 400 260" />
                            <g class="node"><rect x="20" y="58" width="90" height="44" rx="4"/><text x="34" y="78">Web app</text><text class="sub" x="34" y="93">twig · react</text></g>
                            <g class="node"><rect x="20" y="158" width="90" height="44" rx="4"/><text x="34" y="178">Partners</text><text class="sub" x="34" y="193">webhooks</text></g>
                            <g class="node"><rect x="20" y="258" width="90" height="44" rx="4"/><text x="34" y="278">Shopify</text><text class="sub" x="34" y="293">storefront</text></g>
                            <g class="node"><rect x="230" y="146" width="100" height="68" rx="4"/><text x="246" y="174">API</text><text class="sub" x="246" y="190">symfony</text><text class="sub" x="246" y="203">laravel</text></g>
                            <g class="node alt"><rect x="400" y="78" width="70" height="44" rx="4"/><text x="410" y="98">Queue</text><text class="sub" x="410" y="113">workers</text></g>
                            <g class="node alt"><rect x="400" y="238" width="70" height="44" rx="4"/><text x="410" y="258">MySQL</text><text class="sub" x="410" y="273">doctrine</text></g>
                        </svg>
                    </figure>
                </div>
                <div class="metrics">
                    @if ($years)<div><strong>{{ $years }}+ yrs</strong><span>building production systems</span></div>@endif
                    <div><strong>{{ str_pad(count($experiences), 2, '0', STR_PAD_LEFT) }}</strong><span>companies: telecoms, financial services, SaaS</span></div>
                    <div><strong>UTC+2</strong><span>overlaps the full UK and EU working day</span></div>
                </div>
            </div>
        </section>

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <div class="wrap">
                    <div class="head"><span class="idx">{{ str_pad(++$index, 2, '0', STR_PAD_LEFT) }}</span><h2>Experience</h2></div>
                    <div class="exp">
                        @foreach (collect($experiences)->sortByDesc('id') as $experience)
                            <article class="bracket" data-reveal>
                                <span class="mono">{{ $experience->period }}</span>
                                <div>
                                    <h3>{{ $experience->company }}</h3>
                                    <p class="pos">{{ $experience->position }}</p>
                                    @if ($experience->details){!! $experience->details !!}@endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['projects'] && count($projects))
            <section class="block" id="work">
                <div class="wrap">
                    <div class="head"><span class="idx">{{ str_pad(++$index, 2, '0', STR_PAD_LEFT) }}</span><h2>Selected work</h2></div>
                    <div class="grid3">
                        @foreach ($projects as $project)
                            @php $categories = $project->categories ? (array) safe_json_decode($project->categories) : []; @endphp
                            <a class="bracket proj" data-reveal @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
                                @if ($project->thumbnail)<img src="{{ media_url($project->thumbnail) }}" alt="" loading="lazy" width="640" height="400">@endif
                                <div class="body">
                                    <span class="mono">{{ implode(' / ', $categories) }}</span>
                                    <h3>{{ $project->title }}</h3>
                                    @if ($project->details)<p>{{ strip_tags($project->details) }}</p>@endif
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['skills'] && count($skills))
            <section class="block" id="skills">
                <div class="wrap">
                    <div class="head"><span class="idx">{{ str_pad(++$index, 2, '0', STR_PAD_LEFT) }}</span><h2>Specification</h2></div>
                    <div class="skills" data-reveal>
                        @foreach ($skills as $skill)
                            @php $c = 2 * M_PI * 14; $offset = $c * (1 - $skill->proficiency / 100); @endphp
                            <div class="skill">
                                <span>{{ $skill->name }}</span>
                                <svg width="36" height="36" viewBox="0 0 36 36" role="img" aria-label="{{ $skill->proficiency }}%">
                                    <circle class="track" cx="18" cy="18" r="14" fill="none" stroke-width="3"/>
                                    <circle class="val" cx="18" cy="18" r="14" fill="none" stroke-width="3" stroke-linecap="round" stroke-dasharray="{{ round($c, 2) }}" stroke-dashoffset="{{ round($offset, 2) }}" transform="rotate(-90 18 18)"/>
                                </svg>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['services'] && count($services))
            <section class="block" id="services">
                <div class="wrap">
                    <div class="head"><span class="idx">{{ str_pad(++$index, 2, '0', STR_PAD_LEFT) }}</span><h2>Services</h2></div>
                    <div class="grid3">
                        @foreach ($services as $service)
                            <div class="bracket svc" data-reveal>
                                <span class="mono">Module {{ chr(64 + $loop->iteration) }}</span>
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
                    <div class="head"><span class="idx">{{ str_pad(++$index, 2, '0', STR_PAD_LEFT) }}</span><h2>Contact</h2></div>
                    <div class="contact">
                        <div>
                            <p class="lead">Contract, part-time or full-time remote roles. Send the brief and I'll reply within a day.</p>
                            @if ($about->email)<p><span class="mono">Email</span><br><a href="mailto:{{ $about->email }}">{{ $about->email }}</a></p>@endif
                            @if ($about->address)<p><span class="mono">Location</span><br>{{ $about->address }}</p>@endif
                        </div>
                        <div class="bracket">@include('frontend.theme.partials.contact-form')</div>
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer>
        <div class="wrap mono">
            <span>© {{ date('Y') }} {{ $about->name }}</span>
            <span>@if ($blogUrl)<a href="{{ $blogUrl }}">Blog</a>@endif @foreach ($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }}</a>@endforeach</span>
        </div>
    </footer>

    @include('frontend.theme.partials.scripts')
    <script>
        // Spotlight follows the pointer
        (function () {
            if (matchMedia('(prefers-reduced-motion: reduce)').matches || !matchMedia('(pointer: fine)').matches) return;
            var root = document.documentElement;
            window.addEventListener('pointermove', function (e) {
                root.style.setProperty('--mx', e.clientX + 'px');
                root.style.setProperty('--my', e.clientY + 'px');
            }, { passive: true });
        })();
    </script>
</body>
</html>
