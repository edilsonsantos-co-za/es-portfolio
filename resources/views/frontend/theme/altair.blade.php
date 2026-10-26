{{-- Altair theme (Orion family): developer terminal hero, file-tree sidebar navigation, monospace accents. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $themeKey = 'altair-theme';
    $fonts = 'family=IBM+Plex+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;700';
    $topSkills = collect($skills)->sortByDesc('proficiency')->take(6)->pluck('name')->map(fn ($n) => strtolower(str_replace([' ', '/'], ['-', ''], $n)))->implode('  ');
    $handle = strtolower(explode(' ', $about->name)[0] ?? 'dev');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('frontend.theme.partials.head')
    <style>
        :root {
            --bg: #fbf8ef; --panel: #f2eddd; --term: #fffdf6; --text: #24292f; --muted: #6b6f76; --line: #e2dccb;
            --green: #2f7d32; --amber: #b45309; --blue: #1d4ed8; --pink: #be185d;
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0a0d0b; --panel: #0f1411; --term: #0c100d; --text: #d7e3da; --muted: #7d8a82; --line: #1d2620;
                --green: #4ade80; --amber: #fbbf24; --blue: #60a5fa; --pink: #f472b6;
            }
        }
        :root[data-theme="dark"] {
            --bg: #0a0d0b; --panel: #0f1411; --term: #0c100d; --text: #d7e3da; --muted: #7d8a82; --line: #1d2620;
            --green: #4ade80; --amber: #fbbf24; --blue: #60a5fa; --pink: #f472b6;
        }
        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 24px; }
        body { margin: 0; background: var(--bg); color: var(--text); font: 400 17px/1.65 'IBM Plex Sans', system-ui, sans-serif; -webkit-font-smoothing: antialiased; }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .mono, .label, code { font-family: 'JetBrains Mono', monospace; }
        .label { font-size: 13px; color: var(--muted); }
        .g { color: var(--green); } .a { color: var(--amber); } .b { color: var(--blue); } .p { color: var(--pink); } .m { color: var(--muted); }

        /* Layout: sidebar + content */
        .shell { display: grid; grid-template-columns: 260px 1fr; min-height: 100vh; }
        aside { position: sticky; top: 0; height: 100vh; padding: 28px 22px; border-right: 1px solid var(--line); background: var(--panel); display: flex; flex-direction: column; gap: 24px; font-family: 'JetBrains Mono', monospace; font-size: 14px; }
        .brand { text-decoration: none; font-weight: 700; }
        .brand::before { content: '~/'; color: var(--green); }
        .tree { list-style: none; margin: 0; padding: 0; }
        .tree li a { display: block; padding: 5px 8px; border-radius: 6px; text-decoration: none; color: var(--muted); }
        .tree li a::before { content: '├─ '; color: var(--line); }
        .tree li:last-child a::before { content: '└─ '; }
        .tree li a:hover, .tree li a.active { color: var(--text); background: color-mix(in srgb, var(--green) 10%, transparent); }
        .tree li a.active::after { content: ' ●'; color: var(--green); }
        aside .foot { margin-top: auto; display: flex; gap: 8px; align-items: center; }
        .icon-btn { font: inherit; border: 1px solid var(--line); background: var(--term); color: var(--text); padding: 6px 10px; border-radius: 6px; cursor: pointer; }
        .menu-btn { display: none; }
        main { padding: 0 clamp(16px, 5vw, 72px); max-width: 1100px; }

        /* Terminal hero */
        .hero { min-height: min(92vh, 900px); display: flex; flex-direction: column; justify-content: center; padding: 48px 0; }
        .term { border: 1px solid var(--line); border-radius: 12px; background: var(--term); box-shadow: 0 30px 80px rgba(0,0,0,.18); overflow: hidden; }
        .term-bar { display: flex; align-items: center; gap: 8px; padding: 12px 16px; border-bottom: 1px solid var(--line); background: var(--panel); }
        .term-bar i { width: 12px; height: 12px; border-radius: 50%; background: #ff5f57; }
        .term-bar i:nth-child(2) { background: #febc2e; } .term-bar i:nth-child(3) { background: #28c840; }
        .term-bar span { margin-left: auto; margin-right: auto; font: 13px 'JetBrains Mono', monospace; color: var(--muted); }
        .term-body { padding: 22px 24px 26px; font: 15px/1.75 'JetBrains Mono', monospace; min-height: 380px; }
        .term-body .line { white-space: pre-wrap; word-break: break-word; }
        .term-body h1 { font: 700 clamp(30px, 5vw, 52px)/1.1 'JetBrains Mono', monospace; margin: 4px 0 6px; letter-spacing: -.02em; color: var(--text); }
        .cursor { display: inline-block; width: 9px; height: 1.1em; background: var(--green); vertical-align: text-bottom; animation: blink 1s steps(1) infinite; }
        @keyframes blink { 50% { opacity: 0; } }
        .hero-actions { display: flex; gap: 12px; flex-wrap: wrap; margin-top: 24px; }
        .btn { display: inline-flex; gap: 8px; align-items: center; padding: 11px 18px; border-radius: 8px; border: 1px solid var(--line); background: var(--term); color: var(--text);
            text-decoration: none; font: 500 14px 'JetBrains Mono', monospace; cursor: pointer; }
        .btn.primary { background: var(--green); border-color: var(--green); color: var(--bg); font-weight: 700; }
        .btn:hover { border-color: var(--green); }

        section.block { padding: 72px 0; border-top: 1px dashed var(--line); }
        h2 { font: 700 clamp(26px, 3.6vw, 38px)/1.15 'JetBrains Mono', monospace; letter-spacing: -.02em; margin: 6px 0 32px; }
        h2::before { content: '# '; color: var(--green); }

        .log { display: grid; gap: 0; border-left: 2px solid var(--line); }
        .entry { position: relative; padding: 0 0 34px 28px; }
        .entry::before { content: ''; position: absolute; left: -7px; top: 6px; width: 12px; height: 12px; border-radius: 3px; background: var(--bg); border: 2px solid var(--green); }
        .entry .hash { font: 13px 'JetBrains Mono', monospace; color: var(--amber); }
        .entry h3 { margin: 2px 0 0; font-size: 21px; }
        .entry .pos { margin: 0 0 8px; color: var(--muted); }
        .entry ul { margin: 0; padding-left: 0; list-style: none; color: var(--muted); }
        .entry li { padding-left: 22px; position: relative; margin: 4px 0; }
        .entry li::before { content: '+'; position: absolute; left: 4px; color: var(--green); font-family: 'JetBrains Mono', monospace; }

        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 16px; }
        .file { border: 1px solid var(--line); border-radius: 10px; background: var(--term); overflow: hidden; text-decoration: none; display: flex; flex-direction: column; transition: border-color .2s, transform .2s; }
        .file:hover { border-color: var(--green); transform: translateY(-3px); }
        .file .tab { font: 12px 'JetBrains Mono', monospace; padding: 8px 14px; border-bottom: 1px solid var(--line); color: var(--muted); background: var(--panel); }
        .file img { width: 100%; aspect-ratio: 16/10; object-fit: cover; border-bottom: 1px solid var(--line); filter: saturate(.85); }
        .file .body { padding: 18px 18px 22px; }
        .file h3 { margin: 0 0 6px; font-size: 19px; }
        .file p { margin: 0; color: var(--muted); font-size: 15px; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        .bars { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 10px 32px; font: 14px 'JetBrains Mono', monospace; }
        .bar { display: grid; grid-template-columns: 140px 1fr 44px; gap: 12px; align-items: center; }
        .bar .track { color: var(--line); letter-spacing: -1px; overflow: hidden; white-space: nowrap; }
        .bar .track b { color: var(--green); font-weight: 400; }

        .svc { border: 1px solid var(--line); border-radius: 10px; padding: 22px; background: var(--term); }
        .svc .label { color: var(--amber); }
        .svc h3 { margin: 6px 0 8px; font-size: 19px; }
        .svc p { margin: 0; color: var(--muted); font-size: 15px; }

        .contact { display: grid; grid-template-columns: 1fr 1.3fr; gap: 36px; }
        .contact-form { display: grid; gap: 12px; }
        .contact-form .label::before { content: '$ '; color: var(--green); }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        input, textarea { width: 100%; font: 15px 'JetBrains Mono', monospace; color: var(--text); background: var(--term); border: 1px solid var(--line); border-radius: 8px; padding: 11px 13px; margin-top: 4px; }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--green); outline-offset: 2px; }
        .form-note { min-height: 1.5em; color: var(--muted); font-family: 'JetBrains Mono', monospace; font-size: 14px; margin: 0; }
        footer { padding: 32px 0 48px; border-top: 1px dashed var(--line); color: var(--muted); font: 13px 'JetBrains Mono', monospace; }

        .reveal { opacity: 0; transform: translateY(16px); transition: opacity .6s, transform .6s; }
        .reveal.in { opacity: 1; transform: none; }

        @media (max-width: 900px) {
            .shell { grid-template-columns: 1fr; }
            aside { position: sticky; top: 0; z-index: 20; height: auto; flex-direction: row; align-items: center; padding: 12px 16px; border-right: 0; border-bottom: 1px solid var(--line); }
            aside .foot { margin: 0 0 0 auto; }
            .menu-btn { display: inline-block; }
            .tree { display: none; position: absolute; top: 100%; left: 0; right: 0; background: var(--panel); border-bottom: 1px solid var(--line); padding: 8px 16px 14px; }
            .tree.open { display: block; }
            .hero { min-height: auto; }
            .contact, .two { grid-template-columns: 1fr; }
            .bar { grid-template-columns: 110px 1fr 40px; }
        }
        @media (prefers-reduced-motion: reduce) { .cursor { animation: none; } html { scroll-behavior: auto; } }
    </style>
</head>
<body>
<div class="shell">
    <aside>
        <a class="brand" href="#top">{{ $handle }}</a>
        <ul class="tree" id="menu">
            <li><a href="#top" class="active">readme.md</a></li>
            @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">experience.log</a></li>@endif
            @if ($visibility['projects'] && count($projects))<li><a href="#work">projects/</a></li>@endif
            @if ($visibility['skills'] && count($skills))<li><a href="#skills">stack.json</a></li>@endif
            @if ($visibility['services'] && count($services))<li><a href="#services">services.yaml</a></li>@endif
            @if ($blogUrl)<li><a href="{{ $blogUrl }}">blog ↗</a></li>@endif
            @if ($visibility['contact'])<li><a href="#contact">contact.sh</a></li>@endif
        </ul>
        <div class="foot">
            <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false">ls</button>
            @include('frontend.theme.partials.theme-toggle')
        </div>
    </aside>

    <main id="top">
        <section class="hero">
            <div class="term" role="img" aria-label="Terminal introducing {{ $about->name }}">
                <div class="term-bar" aria-hidden="true"><i></i><i></i><i></i><span>{{ $handle }}@portfolio: ~</span></div>
                <div class="term-body" id="term">
                    <div class="line"><span class="g">➜</span> <span class="b">~</span> whoami</div>
                    <h1>{{ $about->name }}</h1>
                    @if (count($taglines))<div class="line m">// <span id="tagline">{{ $taglines[0] }}</span></div>@endif
                    <div class="line">&nbsp;</div>
                    <div class="line"><span class="g">➜</span> <span class="b">~</span> cat about.txt</div>
                    <div class="line">{{ $about->description }}</div>
                    <div class="line">&nbsp;</div>
                    <div class="line"><span class="g">➜</span> <span class="b">~</span> ls stack/</div>
                    <div class="line a">{{ $topSkills }}</div>
                    <div class="line">&nbsp;</div>
                    <div class="line"><span class="g">➜</span> <span class="b">~</span> status --availability</div>
                    <div class="line"><span class="g">●</span> open to remote contract work · {{ $about->address }} · UTC+2 @if ($years)· {{ $years }}+ years in production @endif</div>
                    <div class="line"><span class="g">➜</span> <span class="b">~</span> <span class="cursor"></span></div>
                </div>
            </div>
            <div class="hero-actions">
                @if ($visibility['contact'])<a class="btn primary" href="#contact">./contact.sh</a>@endif
                @if ($visibility['cv'] && $about->cv)<a class="btn" href="{{ media_url($about->cv) }}" download>cv.pdf</a>@endif
                @foreach ($socials as $social)
                    <a class="btn" href="{{ $social->link }}" target="_blank" rel="noopener">{{ \App\Helpers\ThemeContext::socialLabel($social->title) }} ↗</a>
                @endforeach
            </div>
        </section>

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <span class="label">$ git log --career</span>
                <h2>Experience</h2>
                <div class="log">
                    @foreach (collect($experiences)->sortByDesc('id') as $experience)
                        <article class="entry" data-reveal>
                            <span class="hash">{{ substr(md5($experience->company), 0, 7) }} · {{ $experience->period }}</span>
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
                <span class="label">$ ls -la projects/</span>
                <h2>Projects</h2>
                <div class="grid">
                    @foreach ($projects as $project)
                        @php $categories = $project->categories ? (array) safe_json_decode($project->categories) : []; @endphp
                        <a class="file" data-reveal @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
                            <div class="tab">{{ \Illuminate\Support\Str::slug($project->title) }}/ @if (count($categories))<span class="g">[{{ implode(', ', $categories) }}]</span>@endif</div>
                            @if ($project->thumbnail)<img src="{{ media_url($project->thumbnail) }}" alt="" loading="lazy" width="640" height="400">@endif
                            <div class="body">
                                <h3>{{ $project->title }}</h3>
                                @if ($project->details)<p>{{ strip_tags($project->details) }}</p>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['skills'] && count($skills))
            <section class="block" id="skills">
                <span class="label">$ cat stack.json | jq</span>
                <h2>Stack</h2>
                <div class="bars" data-reveal>
                    @foreach ($skills as $skill)
                        @php $filled = (int) round($skill->proficiency / 5); @endphp
                        <div class="bar">
                            <span>{{ strtolower($skill->name) }}</span>
                            <span class="track" aria-hidden="true"><b>{{ str_repeat('█', $filled) }}</b>{{ str_repeat('░', 20 - $filled) }}</span>
                            <span class="a">{{ $skill->proficiency }}%</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['services'] && count($services))
            <section class="block" id="services">
                <span class="label">$ cat services.yaml</span>
                <h2>Services</h2>
                <div class="grid">
                    @foreach ($services as $service)
                        <div class="svc" data-reveal>
                            <span class="label">- id: {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                            <h3>{{ $service->title }}</h3>
                            @if ($service->details)<p>{{ strip_tags($service->details) }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['contact'])
            <section class="block" id="contact">
                <span class="label">$ ./contact.sh</span>
                <h2>Contact</h2>
                <div class="contact">
                    <div>
                        <p class="m">Contract, part-time or full-time remote roles. I usually reply within a day.</p>
                        @if ($about->email)<p class="mono"><span class="g">mail</span> <a href="mailto:{{ $about->email }}">{{ $about->email }}</a></p>@endif
                        @if ($about->address)<p class="mono"><span class="g">tz</span> {{ $about->address }} (UTC+2)</p>@endif
                    </div>
                    @include('frontend.theme.partials.contact-form')
                </div>
            </section>
        @endif

        <footer>
            exit 0 · © {{ date('Y') }} {{ $about->name }}
            @if ($blogUrl) · <a href="{{ $blogUrl }}">blog</a>@endif
            @foreach ($socials as $social) · <a href="{{ $social->link }}" target="_blank" rel="noopener">{{ strtolower($social->title) }}</a>@endforeach
        </footer>
    </main>
</div>

@include('frontend.theme.partials.scripts')
<script>
    // Type the terminal lines in one by one on first load
    (function () {
        if (matchMedia('(prefers-reduced-motion: reduce)').matches) return;
        var lines = Array.prototype.slice.call(document.querySelectorAll('#term > *'));
        lines.forEach(function (l) { l.style.visibility = 'hidden'; });
        var i = 0;
        (function next() {
            if (i >= lines.length) return;
            lines[i].style.visibility = 'visible';
            var delay = lines[i].textContent.indexOf('➜') === 0 ? 420 : 140;
            i++;
            setTimeout(next, delay);
        })();

        // Highlight the sidebar entry for the section in view
        var links = document.querySelectorAll('.tree a[href^="#"]');
        var io = new IntersectionObserver(function (entries) {
            entries.forEach(function (e) {
                if (!e.isIntersecting) return;
                links.forEach(function (a) { a.classList.toggle('active', a.getAttribute('href') === '#' + e.target.id); });
            });
        }, { rootMargin: '-45% 0px -50% 0px' });
        document.querySelectorAll('main > section[id], main#top').forEach(function (s) { io.observe(s); });
    })();
</script>
</body>
</html>
