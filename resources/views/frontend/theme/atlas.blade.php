{{-- Atlas theme (Orion family): live bento hero (spotlight, seconds ring, count-up, filling meters, company ticker), tabbed pill nav, bento sections. --}}
@php
    extract(\App\Helpers\ThemeContext::make($about, $experiences));
    $visibility = $portfolioConfig['visibility'];
    $fonts = 'family=Manrope:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500&family=Bricolage+Grotesque:opsz,wght@12..96,600;12..96,800';
    $topSkills = collect($skills)->sortByDesc('proficiency')->take(5);
    $latest = collect($experiences)->sortByDesc('id')->first();
    $companies = collect($experiences)->sortByDesc('id')->pluck('company')->filter()->unique()->values();
    $education = $education ?? [];
    $logoUrl = \App\Helpers\Utils::getLogo();
    $hasLogo = $logoUrl && !\Illuminate\Support\Str::contains($logoUrl, 'logo/default.png');
    // 'home' is the overview; every nav item is its own page: /projects, /experience, /education, /services, /contact.
    $page = $page ?? 'home';
    $onHome = $page === 'home';
    // The home page is the hero bento only; each section lives on its own page.
    $shows = fn (string $section) => $page === $section;
    $home = url('/');
    $projectsUrl = route('frontend.projects');
    $pageUrl = fn (string $section) => route('frontend.' . $section);
    $here = fn (string $section) => $page === $section ? ' aria-current="page"' : '';
    $projectCategories = collect($projects)->map(function ($project) {
        $categories = $project->categories ? (array) safe_json_decode($project->categories) : [];
        return collect($categories)->map(fn ($c) => trim((string) $c))->filter()->unique()->values()->all();
    });
    $categoryFilters = $projectCategories->flatten()->countBy()->sortDesc()->keys();
    $pageTitle = ['projects' => 'Projects', 'experience' => 'Experience', 'education' => 'Education', 'services' => 'Services', 'contact' => 'Contact'][$page] ?? null;
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

        .bento { display: grid; grid-template-columns: repeat(4, 1fr); grid-auto-rows: auto; gap: 10px; padding: 24px 0 0; }
        .bento > .tile { min-height: 150px; }
        .bento > .shipped { min-height: 0; }
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
        .now { grid-column: span 1; background: var(--accent-soft); display: flex; flex-direction: column; justify-content: space-between; }
        .now.solo { grid-column: 1 / -1; }
        /* Worked at: a thin full-width band (same as the blog's "Writes about") */
        .shipped { grid-column: 1 / -1; display: flex; flex-direction: row; align-items: center; gap: 24px; min-height: 0; padding: 18px 24px; }
        .shipped > .mono { flex: none; }
        .shipped .track { flex: 1; min-width: 0; overflow: hidden; mask-image: linear-gradient(90deg, transparent, #000 8%, #000 92%, transparent); }
        .ticker { display: flex; gap: 28px; width: max-content; white-space: nowrap; animation: tick 30s linear infinite; font-weight: 800; font-size: 20px; letter-spacing: -.02em; }
        .ticker span::after { content: '·'; margin-left: 28px; color: var(--muted); }
        .shipped:hover .ticker { animation-play-state: paused; }
        @keyframes tick { to { transform: translateX(-50%); } }
        .now .mono { color: var(--muted); }
        .now h3 { font-size: 22px; line-height: 1.2; margin: 12px 0 2px; letter-spacing: -.02em; }

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
        .work[hidden] { display: none; }
        .filters { display: flex; flex-wrap: wrap; gap: 8px; margin: -4px 0 16px; }
        .chip { display: inline-flex; align-items: center; gap: 8px; padding: 8px 14px; border-radius: 999px; border: 1px solid var(--line); background: var(--card); color: var(--muted); font: 700 14px 'Manrope', sans-serif; cursor: pointer; }
        .chip span { font: 500 11px 'JetBrains Mono', monospace; padding: 1px 7px; border-radius: 999px; background: var(--card-2); }
        .chip:hover { color: var(--text); }
        .chip[aria-pressed="true"] { background: var(--accent); border-color: var(--accent); color: var(--accent-ink); }
        .chip[aria-pressed="true"] span { background: color-mix(in srgb, var(--accent-ink) 22%, transparent); }
        .chip:focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }
        section.page { padding-top: 40px; }
        section.page h1 { font-size: clamp(36px, 5vw, 60px); letter-spacing: -.05em; margin: 6px 0 0; line-height: 1; font-weight: 800; }
        .nav a[aria-current="page"] { color: var(--text); }
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
        .contact .cta h2, .contact .cta h1 { font-size: clamp(34px, 4vw, 52px); line-height: 1.05; margin: 8px 0 12px; }
        .contact .cta a { color: inherit; }
        .contact-form { display: grid; gap: 10px; }
        .contact-form .label { display: block; margin-bottom: 4px; }
        .two { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        input, textarea { width: 100%; font: 500 16px 'Manrope', sans-serif; color: var(--text); background: var(--card-2); border: 1px solid var(--line); border-radius: 14px; padding: 12px 14px; }
        input:focus, textarea:focus, .btn:focus-visible, .icon-btn:focus-visible { outline: 2px solid var(--text); outline-offset: 2px; }
        .form-note { min-height: 1.5em; color: var(--muted); margin: 0; }

        .reveal { opacity: 0; transform: translateY(16px) scale(.98); transition: opacity .6s, transform .6s; }
        .reveal.in { opacity: 1; transform: none; }

        /* Phones and tablets: the nav moves to the bottom tab bar (<es-tabbar>), so the actions keep to the right */
        @media (max-width: 1024px) { .top-actions { margin-left: auto; } }
        @media (max-width: 1000px) {
            .menu-btn { display: grid; }
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
            .intro, .stack, .now, .bento-list > * { grid-column: auto !important; grid-row: auto !important; }
            .shipped { flex-direction: column; align-items: flex-start; } .shipped .track { width: 100%; }
            .photo { grid-row: auto; aspect-ratio: 4/5; }
        }
        @media (prefers-reduced-motion: reduce) { .dot, .ticker { animation: none; } .stack .meter i { transition: none; } .tile { transition: none; } html { scroll-behavior: auto; } }
    </style>
    {{-- Atlas 2 refresh layer (trial): remove this line to go back to plain Atlas --}}
    <link rel="stylesheet" href="{{ asset('themes/atlas/atlas-refresh.css') }}?v={{ filemtime(public_path('themes/atlas/atlas-refresh.css')) }}">
    <script src="{{ asset('assets/es-apps.js') }}?v={{ filemtime(public_path('assets/es-apps.js')) }}" defer></script>
    <script src="{{ asset('assets/es-account.js') }}?v={{ filemtime(public_path('assets/es-account.js')) }}" defer></script>
</head>
<body>
    <header class="masthead">
        <div class="wrap mast-row">
            <a class="brand" href="{{ $home }}" aria-label="{{ $about->name }}, home">
                <span class="logo">@if ($hasLogo)<img src="{{ $logoUrl }}" alt="">@else{{ collect(explode(' ', $about->name))->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}@endif</span>
                <span class="brand-text"><b>{{ $about->name }}</b><small class="mono">Portfolio</small></span>
            </a>
            <ul class="nav" id="menu">
                @if ($visibility['experiences'] && count($experiences))<li><a href="{{ $pageUrl('experience') }}"{!! $here('experience') !!}>Experience</a></li>@endif
                @if ($visibility['education'] && count($education))<li><a href="{{ $pageUrl('education') }}"{!! $here('education') !!}>Education</a></li>@endif
                @if ($visibility['projects'] && count($projects))<li><a href="{{ $projectsUrl }}"{!! $here('projects') !!}>Projects</a></li>@endif
                @if ($visibility['services'] && count($services))<li><a href="{{ $pageUrl('services') }}"{!! $here('services') !!}>Services</a></li>@endif
                @if ($visibility['contact'])<li><a href="{{ $pageUrl('contact') }}"{!! $here('contact') !!}>Contact</a></li>@endif
            </ul>
            <div class="top-actions">
                {{-- The ES app launcher and Sign in, shared by every ES app; es-account.js swaps in the signed-in admin --}}
                @if ($coffeeUrl)<a class="btn coffee" href="{{ $coffeeUrl }}" target="_blank" rel="noopener" aria-label="Buy me a coffee"><svg class="coffee-icon" viewBox="0 0 24 24" width="16" height="16" aria-hidden="true" fill="currentColor"><path d="M20.216 6.415l-.132-.666c-.119-.598-.388-1.163-1.001-1.379-.197-.069-.42-.098-.57-.241-.152-.143-.196-.366-.231-.572-.065-.378-.125-.756-.192-1.133-.057-.325-.102-.69-.25-.987-.195-.4-.597-.634-.996-.788a5.723 5.723 0 00-.626-.194c-1-.263-2.05-.36-3.077-.416a25.834 25.834 0 00-3.7.062c-.915.083-1.88.184-2.75.5-.318.116-.646.256-.888.501-.297.302-.393.77-.177 1.146.154.267.415.456.692.58.36.162.737.284 1.123.366 1.075.238 2.189.331 3.287.37 1.218.05 2.437.01 3.65-.118.299-.033.598-.073.896-.119.352-.054.578-.513.474-.834-.124-.383-.457-.531-.834-.473-.466.074-.96.108-1.382.146-1.177.08-2.358.082-3.536.006a22.228 22.228 0 01-1.157-.107c-.086-.01-.18-.025-.258-.036-.243-.036-.484-.08-.724-.13-.111-.027-.111-.185 0-.212h.005c.277-.06.557-.108.838-.147h.002c.131-.009.263-.032.394-.048a25.076 25.076 0 013.426-.12c.674.019 1.347.067 2.017.144l.228.031c.267.04.533.088.798.145.392.085.895.113 1.07.542.055.137.08.288.111.431l.319 1.484a.237.237 0 01-.199.284h-.003c-.037.006-.075.01-.112.015a36.704 36.704 0 01-4.743.295 37.059 37.059 0 01-4.699-.304c-.14-.017-.293-.042-.417-.06-.326-.048-.649-.108-.973-.161-.393-.065-.768-.032-1.123.161-.29.16-.527.404-.675.701-.154.316-.199.66-.267 1-.069.34-.176.707-.135 1.056.087.753.613 1.365 1.37 1.502a39.69 39.69 0 0011.343.376.483.483 0 01.535.53l-.071.697-1.018 9.907c-.041.41-.047.832-.125 1.237-.122.637-.553 1.028-1.182 1.171-.577.131-1.165.2-1.756.205-.656.004-1.31-.025-1.966-.022-.699.004-1.556-.06-2.095-.58-.475-.458-.54-1.174-.605-1.793l-.731-7.013-.322-3.094c-.037-.351-.286-.695-.678-.678-.336.015-.718.3-.678.679l.228 2.185.949 9.112c.147 1.344 1.174 2.068 2.446 2.272.742.12 1.503.144 2.257.156.966.016 1.942.053 2.892-.122 1.408-.258 2.465-1.198 2.616-2.657.34-3.332.683-6.663 1.024-9.995l.215-2.087a.484.484 0 01.39-.426c.402-.078.787-.212 1.074-.518.455-.488.546-1.124.385-1.766zm-1.478.772c-.145.137-.363.201-.578.233-2.416.359-4.866.54-7.308.46-1.748-.06-3.477-.254-5.207-.498-.17-.024-.353-.055-.47-.18-.22-.236-.111-.71-.054-.995.052-.26.152-.609.463-.646.484-.057 1.046.148 1.526.22.577.088 1.156.159 1.737.212 2.48.226 5.002.19 7.472-.14.45-.06.899-.13 1.345-.21.399-.072.84-.206 1.08.206.166.281.188.657.162.974a.544.544 0 01-.169.364zm-6.159 3.9c-.862.37-1.84.788-3.109.788a5.884 5.884 0 01-1.569-.217l.877 9.004c.065.78.717 1.38 1.5 1.38 0 0 1.243.065 1.658.065.447 0 1.786-.065 1.786-.065.783 0 1.434-.6 1.499-1.38l.94-9.95a3.996 3.996 0 00-1.322-.238c-.826 0-1.491.284-2.26.613z"/></svg><span class="hide-sm">Buy me a coffee</span></a>@endif
                <es-apps current="portfolio" sign-in-url="{{ url('/admin') }}" dashboard-url="{{ url('/admin/dashboard') }}"></es-apps>
                <button class="icon-btn menu-btn" type="button" id="menu-btn" aria-label="Open menu" aria-expanded="false" aria-controls="menu">☰</button>
            </div>
        </div>
    </header>
    {{-- Phones and tablets: the app-style bottom tab bar every ES app shares (es-apps.js); it replaces the ☰ menu --}}
    <es-tabbar hide=".menu-btn, #menu">
        <a href="{{ $home }}" data-icon="home"{!! $page === 'home' ? ' aria-current="page"' : '' !!}>Home</a>
        @if ($visibility['projects'] && count($projects))<a href="{{ $projectsUrl }}" data-icon="projects"{!! $here('projects') !!}>Projects</a>@endif
        @if ($visibility['experiences'] && count($experiences))<a href="{{ $pageUrl('experience') }}" data-icon="experience"{!! $here('experience') !!}>Experience</a>@endif
        @if ($visibility['contact'])<a href="{{ $pageUrl('contact') }}" data-icon="contact"{!! $here('contact') !!}>Contact</a>@endif
        @if ($visibility['education'] && count($education))<a href="{{ $pageUrl('education') }}" data-icon="education"{!! $here('education') !!}>Education</a>@endif
        @if ($visibility['services'] && count($services))<a href="{{ $pageUrl('services') }}" data-icon="services"{!! $here('services') !!}>Services</a>@endif
    </es-tabbar>

    <main id="top" class="wrap">
        @if ($onHome)
        <div class="bento">
            <div class="tile intro">
                <div>
                    <span class="mono">Hello, I'm</span>
                    <h1>{{ $about->name }}</h1>
                    @if (count($taglines))<p class="role">A <b id="tagline">{{ $taglines[0] }}</b> based in {{ $about->address }}.</p>@endif
                </div>
                <div class="actions">
                    @if ($visibility['contact'])<a class="btn primary" href="{{ $pageUrl('contact') }}">Hire me →</a>@endif
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

        @endif

        @if ($shows('experience') && $visibility['experiences'] && count($experiences))
            <section class="block page" id="experience">
                <div class="head"><div><span class="mono">Experience</span><h1>Where I've worked</h1></div></div>
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

        @if ($shows('education') && $visibility['education'] && count($education))
            <section class="block page" id="education">
                <div class="head"><div><span class="mono">Education</span><h1>Where I studied</h1></div></div>
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

        @if ($shows('projects') && $visibility['projects'] && count($projects))
            <section class="block page" id="work">
                <div class="head">
                    <div><span class="mono">{{ count($projects) }} projects</span><h1>All projects</h1></div>
                </div>
                @if ($categoryFilters->count() > 1)
                    <div class="filters" role="group" aria-label="Filter projects by category">
                        <button class="chip" type="button" data-filter="" aria-pressed="true">All <span>{{ count($projects) }}</span></button>
                        @foreach ($categoryFilters as $category)
                            <button class="chip" type="button" data-filter="{{ $category }}" aria-pressed="false">{{ $category }} <span>{{ $projectCategories->filter(fn ($c) => in_array($category, $c, true))->count() }}</span></button>
                        @endforeach
                    </div>
                @endif
                <div class="bento-list works" id="works">
                    @foreach ($projects as $project)
                        @php $categories = $projectCategories[$loop->index]; @endphp
                        <a class="tile work" data-reveal data-categories="{{ json_encode($categories) }}" @if ($project->link) href="{{ $project->link }}" target="_blank" rel="noopener" @endif>
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

        @if ($shows('services') && $visibility['services'] && count($services))
            <section class="block page" id="services">
                <div class="head"><div><span class="mono">Services</span><h1>How I can help</h1></div></div>
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

        @if ($shows('contact') && $visibility['contact'])
            <section class="block page" id="contact">
                <div class="contact">
                    <div class="tile cta" data-reveal>
                        <span class="mono" style="color:inherit;opacity:.65">Contact</span>
                        <h1>Got a project or a role?</h1>
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
        {{-- Same footer in every ES app: brand and one line, three short columns, then a thin bottom bar (atlas-refresh.css) --}}
        <footer class="site-foot">
            <div class="wrap foot">
                <div class="foot-about">
                    <a class="brand foot-brand" href="{{ $home }}">
                        <span class="logo">@if ($hasLogo)<img src="{{ $logoUrl }}" alt="">@else{{ collect(explode(' ', $about->name))->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}@endif</span>
                        <span class="brand-text"><b>{{ $about->name }}</b><small class="mono">Portfolio</small></span>
                    </a>
                    <p>Full-time professional software engineer, building cool projects in my spare time.</p>
                </div>
                <nav class="foot-cols" aria-label="Footer">
                    <div class="foot-col">
                        <h2>Portfolio</h2>
                        <ul>
                            @if ($visibility['experiences'] && count($experiences))<li><a href="{{ $pageUrl('experience') }}">Experience</a></li>@endif
                            @if ($visibility['projects'] && count($projects))<li><a href="{{ $projectsUrl }}">Projects</a></li>@endif
                            @if ($visibility['services'] && count($services))<li><a href="{{ $pageUrl('services') }}">Services</a></li>@endif
                            @if ($visibility['contact'])<li><a href="{{ $pageUrl('contact') }}">Contact</a></li>@endif
                        </ul>
                    </div>
                    <div class="foot-col">
                        <h2>ES apps</h2>
                        <ul>
                            @if ($blogUrl)<li><a href="{{ $blogUrl }}">Blog</a></li>@endif
                            @if ($developersUrl)<li><a href="{{ $developersUrl }}">Developers</a></li>@endif
                            @if ($storeUrl)<li><a href="{{ $storeUrl }}">Store</a></li>@endif
                        </ul>
                    </div>
                    <div class="foot-col">
                        <h2>Connect</h2>
                        {{-- Icons, not words: the name is in aria-label and the hover title; a social without an icon keeps its label --}}
                        <ul class="connect">
                            @foreach ($socials as $social)
                                @php($label = \App\Helpers\ThemeContext::socialLabel($social->title))
                                @php($iconKey = \App\Helpers\ConnectIcons::key($social->title))
                                <li><a href="{{ $social->link }}" target="_blank" rel="noopener" aria-label="{{ $label }} (opens in a new tab)" title="{{ $label }}" @class(['text' => !$iconKey])>{!! $iconKey ? \App\Helpers\ConnectIcons::svg($iconKey) : e($label) !!}</a></li>
                            @endforeach
                            @if ($coffeeUrl)<li><a href="{{ $coffeeUrl }}" target="_blank" rel="noopener" aria-label="Buy me a coffee (opens in a new tab)" title="Buy me a coffee">{!! \App\Helpers\ConnectIcons::svg('coffee') !!}</a></li>@endif
                        </ul>
                    </div>
                </nav>
                <div class="foot-bottom mono">
                    <span>© {{ date('Y') }} {{ $about->name }}</span>
                    <span>Made in Johannesburg</span>
                </div>
            </div>
        </footer>
    @endif

    @include('frontend.theme.partials.scripts')
    <script>
        // Project category filter. Cards are re-appended rather than hidden so the bento :first-child/:last-child layout stays intact.
        (function () {
            var list = document.getElementById('works');
            if (!list) return;
            var cards = Array.prototype.slice.call(list.children);
            var chips = document.querySelectorAll('.filters .chip');
            function apply(filter, initial) {
                var shown = cards.filter(function (card) {
                    return !filter || JSON.parse(card.getAttribute('data-categories') || '[]').indexOf(filter) !== -1;
                });
                cards.forEach(function (card) { card.hidden = shown.indexOf(card) === -1; if (card.parentNode) list.removeChild(card); });
                // After a click, show newly revealed cards at once; the scroll reveal only runs for the first paint.
                shown.forEach(function (card) { if (!initial) card.classList.add('in'); list.appendChild(card); });
                chips.forEach(function (chip) { chip.setAttribute('aria-pressed', String(chip.getAttribute('data-filter') === filter)); });
                var query = filter ? '?category=' + encodeURIComponent(filter) : '';
                if (history.replaceState) history.replaceState(null, '', location.pathname + query + location.hash);
            }
            chips.forEach(function (chip) { chip.addEventListener('click', function () { apply(chip.getAttribute('data-filter')); }); });
            var start = new URLSearchParams(location.search).get('category') || '';
            var known = Array.prototype.some.call(chips, function (chip) { return chip.getAttribute('data-filter') === start; });
            apply(known ? start : '', true);
        })();
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
