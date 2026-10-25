{{-- Lyra theme (Orion family): editorial magazine layout, serif display type, skills ticker, numbered sections. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $themeKey = 'lyra-theme';
    $fonts = 'family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,800&family=Inter:wght@400;500;600&family=JetBrains+Mono:wght@400';
    $section = 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.theme.partials.head')
    <style>
        :root { --bg: #f4efe6; --paper: #fbf8f2; --ink: #141210; --muted: #6a645b; --line: #d8d0c2; --accent: #d1361b; }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) { --bg: #13110f; --paper: #1b1815; --ink: #f1ebe0; --muted: #a39b8f; --line: #35302a; --accent: #ff6b4a; }
        }
        :root[data-theme="dark"] { --bg: #13110f; --paper: #1b1815; --ink: #f1ebe0; --muted: #a39b8f; --line: #35302a; --accent: #ff6b4a; }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body { margin: 0; background: var(--bg); color: var(--ink); font: 400 18px/1.65 'Inter', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1200px, 100% - 32px); margin-inline: auto; }
        .serif { font-family: 'Fraunces', Georgia, serif; }
        .label, .mono { font: 400 12px 'JetBrains Mono', monospace; letter-spacing: .1em; text-transform: uppercase; color: var(--muted); }

        /* Masthead nav */
        .mast { border-bottom: 1px solid var(--ink); }
        .mast .wrap { display: flex; align-items: center; gap: 18px; height: 58px; }
        .mast .date { margin-right: auto; }
        .mast ul { list-style: none; display: flex; gap: 22px; margin: 0; padding: 0; }
        .mast ul a { text-decoration: none; font-weight: 500; font-size: 15px; }
        .mast ul a:hover { color: var(--accent); }
        .icon-btn { font: 500 14px 'Inter', sans-serif; background: none; border: 1px solid var(--ink); color: var(--ink); border-radius: 999px; padding: 5px 12px; cursor: pointer; }
        .menu-btn { display: none; }

        /* Hero */
        .hero { padding: 48px 0 0; }
        .kicker { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; border-bottom: 1px solid var(--line); padding-bottom: 14px; }
        h1 { font: 800 clamp(64px, 14vw, 200px)/.86 'Fraunces', Georgia, serif; letter-spacing: -.045em; margin: 26px 0 18px; }
        h1 em { font-style: italic; font-weight: 400; color: var(--accent); }
        .deck { display: grid; grid-template-columns: 1.4fr 1fr 300px; gap: 36px; padding: 26px 0 40px; border-top: 1px solid var(--ink); align-items: start; }
        .deck .standfirst { font: 400 clamp(22px, 2.4vw, 28px)/1.35 'Fraunces', Georgia, serif; margin: 0; }
        .deck .standfirst #tagline { color: var(--accent); font-style: italic; transition: opacity .22s; }
        .deck .standfirst #tagline.swap { opacity: 0; }
        .deck p { margin: 0; color: var(--muted); }
        .deck img { width: 100%; aspect-ratio: 3/4; object-fit: cover; filter: grayscale(1) contrast(1.05); transition: filter .5s; }
        .deck img:hover { filter: none; }
        .caption { margin-top: 8px; }
        .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
        .btn { display: inline-block; padding: 11px 20px; border-radius: 999px; border: 1px solid var(--ink); text-decoration: none; font: 600 15px 'Inter', sans-serif; background: none; color: var(--ink); cursor: pointer; }
        .btn.primary { background: var(--ink); color: var(--bg); }
        .btn:hover { background: var(--accent); border-color: var(--accent); color: #fff; }

        /* Ticker */
        .ticker { border-block: 1px solid var(--ink); overflow: hidden; padding: 14px 0; background: var(--paper); }
        .ticker .track { display: flex; gap: 40px; width: max-content; animation: tick 40s linear infinite; font: 600 26px 'Fraunces', serif; white-space: nowrap; }
        .ticker .track span::after { content: '✳'; color: var(--accent); margin-left: 40px; font-size: 20px; }
        @keyframes tick { to { transform: translateX(-50%); } }

        /* Sections */
        section.block { padding: 80px 0; border-bottom: 1px solid var(--line); }
        .sec { display: grid; grid-template-columns: 260px 1fr; gap: 40px; }
        .sec-head { position: sticky; top: 24px; align-self: start; }
        .sec-head .num { font: 400 64px/1 'Fraunces', serif; color: var(--accent); }
        h2 { font: 600 clamp(30px, 3.4vw, 42px)/1.1 'Fraunces', serif; letter-spacing: -.02em; margin: 8px 0 0; }

        .row { display: grid; grid-template-columns: 180px 1fr; gap: 28px; padding: 26px 0; border-top: 1px solid var(--line); }
        .row:first-child { border-top: 0; padding-top: 0; }
        .row h3 { font: 600 26px/1.2 'Fraunces', serif; margin: 0; }
        .row .pos { margin: 2px 0 10px; color: var(--muted); font-style: italic; }
        .row ul { margin: 0; padding-left: 18px; }
        .row li { margin: 4px 0; }
        .row li::marker { color: var(--accent); }

        .feature { display: grid; grid-template-columns: 1.2fr 1fr; gap: 32px; align-items: center; padding: 28px 0; border-top: 1px solid var(--line); text-decoration: none; }
        .feature:first-child { border-top: 0; padding-top: 0; }
        .feature:nth-child(even) { grid-template-columns: 1fr 1.2fr; }
        .feature:nth-child(even) .media { order: 2; }
        .feature .media { overflow: hidden; border: 1px solid var(--line); }
        .feature img { width: 100%; aspect-ratio: 16/10; object-fit: cover; transition: transform .6s; }
        .feature:hover img { transform: scale(1.04); }
        .feature h3 { font: 600 32px/1.1 'Fraunces', serif; margin: 6px 0 10px; }
        .feature:hover h3 { color: var(--accent); }
        .feature p { margin: 0; color: var(--muted); display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }

        .skills { columns: 2; column-gap: 40px; }
        .skill { break-inside: avoid; display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px dotted var(--line); font-weight: 500; }
        .skill span { font: 400 14px 'JetBrains Mono', monospace; color: var(--muted); }

        .services { display: grid; grid-template-columns: 1fr 1fr; gap: 0 40px; counter-reset: svc; }
        .service { padding: 22px 0; border-top: 1px solid var(--line); }
        .service h3 { font: 600 24px 'Fraunces', serif; margin: 0 0 6px; }
        .service h3::before { counter-increment: svc; content: counter(svc, upper-roman) '. '; color: var(--accent); font-weight: 400; }
        .service p { margin: 0; color: var(--muted); }

        .contact-big { font: 400 clamp(36px, 6vw, 76px)/1 'Fraunces', serif; letter-spacing: -.03em; margin: 0 0 28px; }
        .contact-big a { text-decoration-thickness: 2px; text-underline-offset: 8px; }
        .contact-big a:hover { color: var(--accent); }
        .contact-form { display: grid; gap: 14px; max-width: 760px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        input, textarea { width: 100%; font: inherit; font-size: 17px; color: var(--ink); background: transparent; border: 0; border-bottom: 1px solid var(--ink); padding: 10px 2px; border-radius: 0; }
        input:focus, textarea:focus { outline: none; border-bottom: 2px solid var(--accent); }
        .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 3px; }
        .form-note { min-height: 1.5em; color: var(--muted); margin: 0; }
        footer .wrap { display: flex; justify-content: space-between; flex-wrap: wrap; gap: 16px; padding: 28px 0 48px; }
        footer a { margin-left: 14px; }

        .reveal { opacity: 0; transform: translateY(18px); transition: opacity .7s, transform .7s; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 960px) {
            .deck { grid-template-columns: 1fr 1fr; }
            .deck .portrait { grid-column: 1 / -1; max-width: 320px; }
            .sec { grid-template-columns: 1fr; gap: 24px; }
            .sec-head { position: static; }
        }
        @media (max-width: 720px) {
            .menu-btn { display: inline-block; }
            .mast ul { display: none; position: absolute; top: 58px; left: 0; right: 0; flex-direction: column; gap: 0; padding: 8px 16px 16px; background: var(--bg); border-bottom: 1px solid var(--ink); z-index: 10; }
            .mast ul.open { display: flex; }
            .mast ul a { display: block; padding: 10px 0; }
            .deck, .row, .feature, .feature:nth-child(even), .services, .two { grid-template-columns: 1fr; }
            .feature:nth-child(even) .media { order: 0; }
            .skills { columns: 1; }
        }
        @media (prefers-reduced-motion: reduce) { .ticker .track { animation: none; } html { scroll-behavior: auto; } }
    </style>
</head>
<body>
    <header class="mast">
        <div class="wrap">
            <span class="date mono">{{ now()->format('l, j F Y') }}</span>
            <ul id="menu">
                @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">Experience</a></li>@endif
                @if ($visibility['projects'] && count($projects))<li><a href="#work">Work</a></li>@endif
                @if ($visibility['services'] && count($services))<li><a href="#services">Services</a></li>@endif
                @if ($blogUrl)<li><a href="{{ $blogUrl }}">Journal ↗</a></li>@endif
                @if ($visibility['contact'])<li><a href="#contact">Contact</a></li>@endif
            </ul>
            <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false">Menu</button>
            <button class="icon-btn" type="button" id="theme-btn" aria-label="Toggle colour theme">◐</button>
        </div>
    </header>

    <main id="top">
        <section class="hero">
            <div class="wrap">
                <div class="kicker mono"><span>Portfolio · Vol. {{ date('Y') }}</span><span>{{ $about->address }} · UTC+2</span><span>Open to remote contract work</span></div>
                @php $parts = explode(' ', $about->name, 2); @endphp
                <h1>{{ $parts[0] }}@if (isset($parts[1]))<br><em>{{ $parts[1] }}</em>@endif</h1>
                <div class="deck">
                    <div>
                        @if (count($taglines))<p class="standfirst">A <span id="tagline">{{ $taglines[0] }}</span> who turns messy business problems into dependable software.</p>@endif
                        <div class="actions">
                            @if ($visibility['contact'])<a class="btn primary" href="#contact">Work with me</a>@endif
                            @if ($visibility['cv'] && $about->cv)<a class="btn" href="{{ media_url($about->cv) }}" download>CV</a>@endif
                            @foreach ($socials as $social)<a class="btn" href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }}</a>@endforeach
                        </div>
                    </div>
                    <p>{{ $about->description }}@if ($years) Over {{ $years }} years across {{ count($experiences) }} companies in telecoms, financial services and SaaS. @endif</p>
                    @if ($about->avatar)
                        <figure class="portrait" style="margin:0">
                            <img src="{{ media_url($about->avatar) }}" alt="Portrait of {{ $about->name }}" width="300" height="400">
                            <figcaption class="caption mono">{{ $about->name }}, {{ $about->address }}</figcaption>
                        </figure>
                    @endif
                </div>
            </div>
        </section>

        @if ($visibility['skills'] && count($skills))
            <div class="ticker" aria-hidden="true">
                <div class="track">
                    @for ($r = 0; $r < 2; $r++)@foreach ($skills as $skill)<span>{{ ucwords(strtolower($skill->name)) }}</span>@endforeach @endfor
                </div>
            </div>
        @endif

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <div class="wrap sec">
                    <div class="sec-head"><div class="num">{{ str_pad(++$section, 2, '0', STR_PAD_LEFT) }}</div><h2>Experience</h2></div>
                    <div>
                        @foreach (collect($experiences)->sortByDesc('id') as $experience)
                            <article class="row" data-reveal>
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
                <div class="wrap sec">
                    <div class="sec-head"><div class="num">{{ str_pad(++$section, 2, '0', STR_PAD_LEFT) }}</div><h2>Selected work</h2></div>
                    <div>
                        @foreach ($projects as $project)
                            @php $categories = $project->categories ? (array) safe_json_decode($project->categories) : []; @endphp
                            <a class="feature" data-reveal @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
                                @if ($project->thumbnail)<div class="media"><img src="{{ media_url($project->thumbnail) }}" alt="" loading="lazy" width="640" height="400"></div>@endif
                                <div>
                                    <span class="mono">{{ implode(' · ', $categories) }}</span>
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
                <div class="wrap sec">
                    <div class="sec-head"><div class="num">{{ str_pad(++$section, 2, '0', STR_PAD_LEFT) }}</div><h2>Services</h2></div>
                    <div class="services">
                        @foreach ($services as $service)
                            <div class="service" data-reveal><h3>{{ $service->title }}</h3>@if ($service->details)<p>{{ strip_tags($service->details) }}</p>@endif</div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['skills'] && count($skills))
            <section class="block" id="skills">
                <div class="wrap sec">
                    <div class="sec-head"><div class="num">{{ str_pad(++$section, 2, '0', STR_PAD_LEFT) }}</div><h2>Toolkit</h2></div>
                    <div class="skills">
                        @foreach ($skills as $skill)<div class="skill">{{ ucwords(strtolower($skill->name)) }}<span>{{ $skill->proficiency }}/100</span></div>@endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['contact'])
            <section class="block" id="contact">
                <div class="wrap sec">
                    <div class="sec-head"><div class="num">{{ str_pad(++$section, 2, '0', STR_PAD_LEFT) }}</div><h2>Contact</h2></div>
                    <div>
                        @if ($about->email)<p class="contact-big">Write to <a href="mailto:{{ $about->email }}">{{ $about->email }}</a></p>@endif
                        @include('frontend.theme.partials.contact-form')
                    </div>
                </div>
            </section>
        @endif
    </main>

    <footer>
        <div class="wrap mono">
            <span>© {{ date('Y') }} {{ $about->name }}</span>
            <span>@if ($blogUrl)<a href="{{ $blogUrl }}">Journal</a>@endif @foreach ($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }}</a>@endforeach</span>
        </div>
    </footer>

    @include('frontend.theme.partials.scripts')
</body>
</html>
