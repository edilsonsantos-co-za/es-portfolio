{{--
    Orion theme: a single-page, framework-free portfolio layout.
    Uses only the data FrontendController already passes; no Vite build is required.
--}}
@php
    $taglines = $about->taglines ? (array) safe_json_decode($about->taglines) : [];
    $socials = $about->social_links ? (array) safe_json_decode($about->social_links) : [];
    $blogUrl = config('custom.blog_url');
    $visibility = $portfolioConfig['visibility'];
    $socialLabel = fn ($title) => ['linkedin' => 'LinkedIn', 'github' => 'GitHub', 'youtube' => 'YouTube', 'tiktok' => 'TikTok'][strtolower($title)] ?? ucfirst($title);

    // Years in industry, from the earliest four-digit year in the experience periods
    $years = null;
    if (!empty($experiences)) {
        preg_match_all('/\b(19|20)\d{2}\b/', collect($experiences)->pluck('period')->implode(' '), $matches);
        if (!empty($matches[0])) {
            $years = (int) date('Y') - (int) min($matches[0]);
        }
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    @include('common.googleAnalytics')
    @if (!empty($portfolioConfig['script']['header']))
        <script>
            {!! $portfolioConfig['script']['header'] !!}
        </script>
    @endif

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $portfolioConfig['seo']['title'] ?: $about->name }}</title>
    <meta name="description" content="{{ $portfolioConfig['seo']['description'] }}">
    <meta name="author" content="{{ $portfolioConfig['seo']['author'] }}">
    <meta property="og:title" content="{{ $portfolioConfig['seo']['title'] }}">
    <meta property="og:description" content="{{ $portfolioConfig['seo']['description'] }}">
    <meta property="og:image" content="{{ media_url($portfolioConfig['seo']['image']) }}">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="shortcut icon" type="image/x-icon" href="{{ Utils::getFavicon() }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter+Tight:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">

    <script>
        // Apply the saved theme before first paint
        try {
            var saved = localStorage.getItem('orion-theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        } catch (e) {}
    </script>

    <style>
        :root {
            --bg: #f6f5f1;
            --surface: #ffffff;
            --surface-2: #efede6;
            --text: #16181d;
            --muted: #5d6270;
            --line: #e1ded5;
            --accent: #c2410c;
            --accent-soft: rgba(194, 65, 12, .1);
            --ok: #15803d;
            --radius: 14px;
            --sans: 'Inter Tight', system-ui, -apple-system, 'Segoe UI', sans-serif;
            --mono: 'JetBrains Mono', ui-monospace, SFMono-Regular, Menlo, monospace;
        }
        @media (prefers-color-scheme: dark) {
            :root:not([data-theme="light"]) {
                --bg: #0d0f12;
                --surface: #15181d;
                --surface-2: #1c2027;
                --text: #eceae4;
                --muted: #9aa0ab;
                --line: #262a32;
                --accent: #fb923c;
                --accent-soft: rgba(251, 146, 60, .12);
                --ok: #4ade80;
            }
        }
        :root[data-theme="dark"] {
            --bg: #0d0f12;
            --surface: #15181d;
            --surface-2: #1c2027;
            --text: #eceae4;
            --muted: #9aa0ab;
            --line: #262a32;
            --accent: #fb923c;
            --accent-soft: rgba(251, 146, 60, .12);
            --ok: #4ade80;
        }

        *, *::before, *::after { box-sizing: border-box; }
        html { scroll-behavior: smooth; scroll-padding-top: 80px; }
        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font: 400 17px/1.6 var(--sans);
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; }
        img { max-width: 100%; display: block; }
        .wrap { width: min(1120px, 100% - 32px); margin-inline: auto; }
        .mono { font-family: var(--mono); font-size: 13px; letter-spacing: .02em; }
        .eyebrow { font-family: var(--mono); font-size: 12px; text-transform: uppercase; letter-spacing: .12em; color: var(--accent); }

        /* Nav */
        .nav {
            position: sticky; top: 0; z-index: 20;
            background: color-mix(in srgb, var(--bg) 86%, transparent);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--line);
        }
        .nav .wrap { display: flex; align-items: center; gap: 24px; height: 64px; }
        .brand { font-weight: 700; text-decoration: none; letter-spacing: -.01em; }
        .brand span { color: var(--accent); }
        .nav ul { list-style: none; display: flex; gap: 22px; margin: 0 0 0 auto; padding: 0; }
        .nav ul a { text-decoration: none; color: var(--muted); font-size: 15px; }
        .nav ul a:hover { color: var(--text); }
        .icon-btn {
            border: 1px solid var(--line); background: var(--surface); color: var(--text);
            width: 36px; height: 36px; border-radius: 10px; cursor: pointer; display: grid; place-items: center;
        }
        .menu-btn { display: none; margin-left: auto; }

        /* Hero */
        .hero { padding: 88px 0 64px; }
        .hero .grid { display: grid; grid-template-columns: 1.5fr 1fr; gap: 56px; align-items: center; }
        .status {
            display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px;
            border: 1px solid var(--line); border-radius: 999px; background: var(--surface);
        }
        .status::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: var(--ok); box-shadow: 0 0 0 4px color-mix(in srgb, var(--ok) 20%, transparent); }
        h1 { font-size: clamp(44px, 7vw, 76px); line-height: 1.02; letter-spacing: -.035em; margin: 22px 0 14px; font-weight: 700; }
        .role { font-size: clamp(20px, 2.6vw, 26px); color: var(--muted); margin: 0 0 18px; min-height: 1.4em; }
        .role b { color: var(--text); font-weight: 600; }
        .lead { color: var(--muted); max-width: 56ch; margin: 0 0 30px; }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; }
        .btn {
            display: inline-flex; align-items: center; gap: 8px; padding: 12px 20px; border-radius: 10px;
            font-weight: 600; font-size: 15px; text-decoration: none; border: 1px solid var(--line); background: var(--surface);
            cursor: pointer; color: var(--text);
        }
        .btn.primary { background: var(--text); color: var(--bg); border-color: var(--text); }
        .btn:hover { transform: translateY(-1px); }
        .portrait { position: relative; }
        .portrait img { width: 100%; aspect-ratio: 4/5; object-fit: cover; border-radius: 22px; border: 1px solid var(--line); }
        .portrait .tag {
            position: absolute; left: -18px; bottom: 28px; background: var(--surface); border: 1px solid var(--line);
            border-radius: 12px; padding: 12px 14px; box-shadow: 0 10px 30px rgba(0,0,0,.08);
        }
        .stats { display: grid; grid-template-columns: repeat(3, 1fr); margin-top: 56px; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); }
        .stats div { padding: 20px 24px; }
        .stats div + div { border-left: 1px solid var(--line); }
        .stats strong { display: block; font-size: 30px; letter-spacing: -.02em; }
        .stats span { color: var(--muted); font-size: 14px; }

        /* Sections */
        section.block { padding: 72px 0; border-top: 1px solid var(--line); }
        .head { display: flex; align-items: baseline; justify-content: space-between; gap: 16px; margin-bottom: 36px; }
        h2 { font-size: clamp(30px, 4vw, 42px); letter-spacing: -.03em; margin: 8px 0 0; line-height: 1.1; }

        .timeline { display: grid; gap: 14px; }
        .job {
            display: grid; grid-template-columns: 200px 1fr; gap: 28px; padding: 26px 28px;
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
        }
        .job h3 { margin: 0; font-size: 21px; letter-spacing: -.01em; }
        .job .pos { color: var(--muted); margin: 2px 0 10px; }
        .job ul { margin: 0; padding-left: 18px; color: var(--muted); }
        .job li { margin: 4px 0; }
        .job li::marker { color: var(--accent); }

        .skills { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
        .skill { padding: 16px 18px; background: var(--surface); border: 1px solid var(--line); border-radius: 12px; }
        .skill .row { display: flex; justify-content: space-between; font-weight: 600; font-size: 15px; }
        .skill .row span { color: var(--muted); font-weight: 400; }
        .meter { height: 4px; border-radius: 4px; background: var(--surface-2); margin-top: 10px; overflow: hidden; }
        .meter i { display: block; height: 100%; background: var(--accent); border-radius: 4px; }

        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .card {
            background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius);
            padding: 26px; display: flex; flex-direction: column; gap: 10px;
        }
        .card h3 { margin: 0; font-size: 20px; letter-spacing: -.01em; }
        .card p { margin: 0; color: var(--muted); }
        .num { color: var(--accent); }

        .project { padding: 0; overflow: hidden; text-decoration: none; transition: border-color .15s; }
        .project:hover { border-color: var(--accent); }
        .project img { width: 100%; aspect-ratio: 16/10; object-fit: cover; border-bottom: 1px solid var(--line); background: var(--surface-2); }
        .project .body { padding: 20px 22px 24px; display: flex; flex-direction: column; gap: 8px; }
        .project p { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .chips { display: flex; flex-wrap: wrap; gap: 6px; }
        .chip { font-family: var(--mono); font-size: 11px; padding: 3px 8px; border-radius: 6px; background: var(--accent-soft); color: var(--accent); }

        .edu { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; }

        /* Contact */
        .contact { display: grid; grid-template-columns: 1fr 1.3fr; gap: 40px; }
        .contact dl { margin: 0; display: grid; gap: 18px; }
        .contact dt { color: var(--muted); }
        .contact dd { margin: 2px 0 0; font-size: 18px; }
        form { display: grid; gap: 12px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
        input, textarea {
            width: 100%; font: inherit; font-size: 16px; color: var(--text); background: var(--surface);
            border: 1px solid var(--line); border-radius: 10px; padding: 12px 14px;
        }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
        .form-note { min-height: 1.5em; font-size: 15px; color: var(--muted); }

        footer { border-top: 1px solid var(--line); padding: 28px 0 40px; color: var(--muted); }
        footer .wrap { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; }

        @media (max-width: 860px) {
            .hero { padding-top: 48px; }
            .hero .grid, .contact { grid-template-columns: 1fr; }
            .portrait { max-width: 360px; }
            .portrait .tag { left: 12px; }
            .job { grid-template-columns: 1fr; gap: 6px; }
            .menu-btn { display: grid; }
            .nav ul {
                display: none; position: absolute; top: 64px; left: 0; right: 0; flex-direction: column; gap: 0;
                background: var(--bg); border-bottom: 1px solid var(--line); padding: 8px 16px 16px;
            }
            .nav ul.open { display: flex; }
            .nav ul a { display: block; padding: 10px 0; font-size: 17px; }
        }
        @media (max-width: 560px) {
            .stats { grid-template-columns: 1fr; }
            .stats div + div { border-left: 0; border-top: 1px solid var(--line); }
            .two { grid-template-columns: 1fr; }
        }
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            .btn:hover { transform: none; }
        }
    </style>
</head>
<body>
    <header class="nav">
        <div class="wrap">
            <a class="brand" href="#top">{{ $about->name }}<span>.</span></a>
            <button class="icon-btn menu-btn" type="button" aria-label="Open menu" aria-expanded="false" id="menu-btn">☰</button>
            <ul id="menu">
                @if ($visibility['experiences'] && count($experiences))<li><a href="#experience">Experience</a></li>@endif
                @if ($visibility['projects'] && count($projects))<li><a href="#work">Work</a></li>@endif
                @if ($visibility['skills'] && count($skills))<li><a href="#skills">Skills</a></li>@endif
                @if ($visibility['services'] && count($services))<li><a href="#services">Services</a></li>@endif
                @if ($blogUrl)<li><a href="{{ $blogUrl }}">Blog ↗</a></li>@endif
                @if ($visibility['contact'])<li><a href="#contact">Contact</a></li>@endif
            </ul>
            <button class="icon-btn" type="button" id="theme-btn" aria-label="Toggle colour theme">◐</button>
        </div>
    </header>

    <main id="top">
        <section class="hero">
            <div class="wrap">
                <div class="grid">
                    <div>
                        <span class="status mono">Open to remote contract work · {{ $about->address }}</span>
                        <h1>{{ $about->name }}</h1>
                        @if (count($taglines))
                            <p class="role">I'm a <b id="tagline">{{ $taglines[0] }}</b></p>
                        @endif
                        <p class="lead">{{ $about->description }}</p>
                        <div class="actions">
                            @if ($visibility['contact'])<a class="btn primary" href="#contact">Get in touch</a>@endif
                            @if ($visibility['cv'] && $about->cv)<a class="btn" href="{{ media_url($about->cv) }}" download>Download CV</a>@endif
                            @foreach ($socials as $social)
                                <a class="btn" href="{{ $social->link }}" target="_blank" rel="noopener">{{ $socialLabel($social->title) }} ↗</a>
                            @endforeach
                        </div>
                    </div>
                    @if ($about->avatar)
                        <div class="portrait">
                            <img src="{{ media_url($about->avatar) }}" alt="Portrait of {{ $about->name }}" width="480" height="600">
                            @if (count($taglines) > 1)
                                <div class="tag mono">{{ implode(' · ', array_slice($taglines, 1)) }}</div>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="stats">
                    @if ($years)<div><strong>{{ $years }}+ years</strong><span>building software in production</span></div>@endif
                    <div><strong>{{ count($experiences) }} companies</strong><span>across telecoms, financial services and SaaS</span></div>
                    <div><strong>UTC+2</strong><span>overlaps the full UK and EU working day</span></div>
                </div>
            </div>
        </section>

        @if ($visibility['experiences'] && count($experiences))
            <section class="block" id="experience">
                <div class="wrap">
                    <div class="head"><div><span class="eyebrow">Experience</span><h2>Where I've worked</h2></div></div>
                    <div class="timeline">
                        @foreach ($experiences as $experience)
                            <article class="job">
                                <div class="mono" style="color:var(--muted)">{{ $experience->period }}</div>
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
                    <div class="head"><div><span class="eyebrow">Work</span><h2>Selected projects</h2></div></div>
                    <div class="cards">
                        @foreach ($projects as $project)
                            @php $categories = $project->categories ? (array) safe_json_decode($project->categories) : []; @endphp
                            <a class="card project" @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
                                @if ($project->thumbnail)
                                    <img src="{{ media_url($project->thumbnail) }}" alt="" loading="lazy" width="640" height="400">
                                @endif
                                <div class="body">
                                    @if (count($categories))
                                        <div class="chips">@foreach ($categories as $category)<span class="chip">{{ $category }}</span>@endforeach</div>
                                    @endif
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
                    <div class="head"><div><span class="eyebrow">Skills</span><h2>What I work with</h2></div></div>
                    <div class="skills">
                        @foreach ($skills as $skill)
                            <div class="skill">
                                <div class="row">{{ $skill->name }}<span class="mono">{{ $skill->proficiency }}%</span></div>
                                <div class="meter" role="meter" aria-label="{{ $skill->name }}" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $skill->proficiency }}"><i style="width: {{ (int) $skill->proficiency }}%"></i></div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['services'] && count($services))
            <section class="block" id="services">
                <div class="wrap">
                    <div class="head"><div><span class="eyebrow">Services</span><h2>How I can help</h2></div></div>
                    <div class="cards">
                        @foreach ($services as $service)
                            <div class="card">
                                <span class="mono num">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <h3>{{ $service->title }}</h3>
                                @if ($service->details)<p>{{ strip_tags($service->details) }}</p>@endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif

        @if ($visibility['education'] && count($education))
            <section class="block" id="education">
                <div class="wrap">
                    <div class="head"><div><span class="eyebrow">Education</span><h2>Background</h2></div></div>
                    @foreach ($education as $value)
                        <div class="card edu">
                            <div><h3>{{ $value->degree }}</h3><p>{{ $value->institution }}@if ($value->department) · {{ $value->department }}@endif</p></div>
                            <span class="mono" style="color:var(--muted)">{{ $value->period }}</span>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        @if ($visibility['contact'])
            <section class="block" id="contact">
                <div class="wrap contact">
                    <div>
                        <span class="eyebrow">Contact</span>
                        <h2>Let's work together</h2>
                        <p class="lead" style="margin-top:14px">Contract, part-time or full-time remote roles. I usually reply within a day.</p>
                        <dl>
                            @if ($about->email)<div><dt class="mono">Email</dt><dd><a href="mailto:{{ $about->email }}">{{ $about->email }}</a></dd></div>@endif
                            @if ($about->address)<div><dt class="mono">Based in</dt><dd>{{ $about->address }}</dd></div>@endif
                        </dl>
                    </div>
                    <form id="contact-form" novalidate>
                        <div class="two">
                            <label><span class="mono">Name</span><input name="name" autocomplete="name" required></label>
                            <label><span class="mono">Email</span><input name="email" type="email" autocomplete="email" required></label>
                        </div>
                        <label><span class="mono">Subject</span><input name="subject" required></label>
                        <label><span class="mono">Message</span><textarea name="body" rows="6" required></textarea></label>
                        <div><button class="btn primary" type="submit">Send message</button></div>
                        <p class="form-note" id="form-note" role="status"></p>
                    </form>
                </div>
            </section>
        @endif
    </main>

    <footer>
        <div class="wrap">
            <span>© {{ date('Y') }} {{ $about->name }}</span>
            <span class="mono">
                @if ($blogUrl)<a href="{{ $blogUrl }}">Blog</a> · @endif
                @foreach ($socials as $social)<a href="{{ $social->link }}" target="_blank" rel="noopener">{{ $socialLabel($social->title) }}</a>@if (!$loop->last) · @endif @endforeach
            </span>
        </div>
    </footer>

    <script>
        (function () {
            var root = document.documentElement;

            document.getElementById('theme-btn').addEventListener('click', function () {
                var dark = root.getAttribute('data-theme') === 'dark' ||
                    (!root.getAttribute('data-theme') && matchMedia('(prefers-color-scheme: dark)').matches);
                var next = dark ? 'light' : 'dark';
                root.setAttribute('data-theme', next);
                try { localStorage.setItem('orion-theme', next); } catch (e) {}
            });

            var menu = document.getElementById('menu');
            var menuBtn = document.getElementById('menu-btn');
            menuBtn.addEventListener('click', function () {
                var open = menu.classList.toggle('open');
                menuBtn.setAttribute('aria-expanded', open);
            });
            menu.addEventListener('click', function (e) {
                if (e.target.tagName === 'A') { menu.classList.remove('open'); menuBtn.setAttribute('aria-expanded', false); }
            });

            // Rotate taglines
            var taglines = @json(array_values($taglines));
            var tagline = document.getElementById('tagline');
            if (tagline && taglines.length > 1 && !matchMedia('(prefers-reduced-motion: reduce)').matches) {
                var i = 0;
                setInterval(function () { i = (i + 1) % taglines.length; tagline.textContent = taglines[i]; }, 2600);
            }

            var form = document.getElementById('contact-form');
            if (form) {
                var note = document.getElementById('form-note');
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    if (!form.checkValidity()) { note.textContent = 'Please fill in every field with a valid email.'; return; }
                    var button = form.querySelector('button');
                    button.disabled = true;
                    note.textContent = 'Sending…';
                    fetch(@json(route('contact-me')), {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                        body: new FormData(form)
                    })
                        .then(function (r) { return r.json(); })
                        .then(function (res) {
                            note.textContent = res.message || (res.status === 200 ? 'Message sent. Thank you!' : 'Something went wrong.');
                            if (res.status === 200) form.reset();
                        })
                        .catch(function () { note.textContent = 'Something went wrong. Please email me instead.'; })
                        .finally(function () { button.disabled = false; });
                });
            }
        })();
    </script>

    @if (!empty($portfolioConfig['script']['footer']))
        <script>
            {!! $portfolioConfig['script']['footer'] !!}
        </script>
    @endif
    @include('common.pixelTracking')
</body>
</html>
