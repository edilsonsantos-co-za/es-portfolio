<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $name }} — CV</title>
<style>
    @page { margin: 18mm 16mm 16mm; }
    * { box-sizing: border-box; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9.6pt; line-height: 1.45; color: #1d2433; }
    h1 { font-size: 22pt; margin: 0; letter-spacing: -0.5pt; color: #0b1220; }
    .headline { font-size: 11pt; color: #2f6fe4; margin: 2pt 0 6pt; font-weight: bold; }
    .contact { color: #4b5568; font-size: 8.8pt; }
    .contact span { margin-right: 10pt; white-space: nowrap; }
    h2 { font-size: 10pt; text-transform: uppercase; letter-spacing: 1.2pt; color: #2f6fe4; border-bottom: 1px solid #d6dce8; padding-bottom: 3pt; margin: 16pt 0 8pt; }
    .entry { margin-bottom: 9pt; page-break-inside: avoid; }
    .row { width: 100%; }
    .row td { padding: 0; vertical-align: top; }
    .title { font-weight: bold; color: #0b1220; }
    .meta { color: #4b5568; }
    .when { text-align: right; color: #4b5568; white-space: nowrap; width: 30%; }
    ul { margin: 3pt 0 0 0; padding-left: 13pt; }
    li { margin-bottom: 1.5pt; }
    p { margin: 3pt 0 0; }
    .skills span { display: inline-block; border: 1px solid #d6dce8; border-radius: 3pt; padding: 1pt 5pt; margin: 0 3pt 4pt 0; }
    .footer { position: fixed; bottom: -8mm; left: 0; right: 0; font-size: 7.5pt; color: #8a93a6; text-align: center; }
</style>
</head>
<body>
    <div class="footer">Generated {{ $generated }} from {{ preg_replace('#^https?://#', '', $website) }}</div>

    <h1>{{ $name }}</h1>
    @if ($headline)<div class="headline">{{ $headline }}</div>@endif
    <div class="contact">
        @if ($address)<span>{{ $address }}</span>@endif
        @if ($email)<span>{{ $email }}</span>@endif
        <span>{{ preg_replace('#^https?://#', '', $website) }}</span>
        @foreach ($links as $link)<span>{{ $link['url'] }}</span>@endforeach
    </div>

    @if ($summary)
        <h2>Profile</h2>
        <div>{!! $summary !!}</div>
    @endif

    @if (count($experiences))
        <h2>Experience</h2>
        @foreach ($experiences as $e)
            <div class="entry">
                <table class="row"><tr>
                    <td><span class="title">{{ $e['position'] }}</span> <span class="meta">· {{ $e['company'] }}</span></td>
                    <td class="when">{{ $e['period'] }}</td>
                </tr></table>
                @if ($e['details']){!! $e['details'] !!}@endif
            </div>
        @endforeach
    @endif

    @if (count($skills))
        <h2>Skills</h2>
        <div class="skills">@foreach ($skills as $skill)<span>{{ $skill }}</span>@endforeach</div>
    @endif

    @if (count($education))
        <h2>Education</h2>
        @foreach ($education as $e)
            <div class="entry">
                <table class="row"><tr>
                    <td><span class="title">{{ $e['degree'] }}</span> <span class="meta">· {{ $e['institution'] }}</span></td>
                    <td class="when">{{ $e['period'] }}</td>
                </tr></table>
                @if ($e['department'] || $e['grade'])<p class="meta">{{ collect([$e['department'], $e['grade'] ? 'Grade: ' . $e['grade'] : null])->filter()->implode(' · ') }}</p>@endif
            </div>
        @endforeach
    @endif

    @if (count($courses))
        <h2>Courses completed</h2>
        @foreach ($courses as $c)
            <table class="row" style="margin-bottom:3pt"><tr>
                <td><span class="title">{{ $c['title'] }}</span> <span class="meta">· {{ $c['issuer'] }}</span></td>
                <td class="when">{{ $c['date'] }}</td>
            </tr></table>
        @endforeach
    @endif

    @if (count($projects))
        <h2>Selected projects</h2>
        @foreach ($projects as $p)
            <div class="entry">
                <span class="title">{{ $p['title'] }}</span>@if ($p['link']) <span class="meta">· {{ $p['link'] }}</span>@endif
                @if ($p['summary'])<p>{{ $p['summary'] }}</p>@endif
            </div>
        @endforeach
    @endif
</body>
</html>
