<div class="head"><div><span class="mono">Education &amp; growth</span><h1>Learning, then building</h1></div></div>
<p class="education-intro">Formal study gave me a foundation. Continued learning helps me connect the decisions behind code, databases, APIs and infrastructure to the projects I build. My aim is to use AI thoughtfully: to question, understand and verify its suggestions, not outsource my understanding.</p>

@if (count($education))
    <h2>Academic foundation</h2>
    <ol class="education-timeline" aria-label="Academic education">
        @foreach (collect($education)->sortBy('period') as $study)
            <li class="tile job">
                @if ($study->period)<span class="pill">{{ $study->period }}</span>@endif
                <h3>{{ $study->institution }}</h3>
                <p class="pos">{{ $study->degree }}@if ($study->department) · {{ $study->department }}@endif</p>
                @if ($study->cgpa)<p>Grade: {{ $study->cgpa }}</p>@endif
                @if ($study->thesis)<p>{{ $study->thesis }}</p>@endif
            </li>
        @endforeach
    </ol>
@endif

@if (count($certificates ?? []))
    <h2>Courses completed</h2>
    <p class="education-note">Certificates of course completion, not professional certification exams. Expand any entry to view the certificate here.</p>
    <ol class="education-timeline" aria-label="Completed courses, oldest first">
        @foreach ($certificates as $certificate)
            <li class="tile job">
                <div class="education-meta"><time class="pill" datetime="{{ $certificate->completed_on->format('Y-m-d') }}">{{ $certificate->completed_on->format('j M Y') }}</time><span class="mono">Course completed</span></div>
                <h3>{{ $certificate->title }}</h3>
                <p class="pos">{{ $certificate->issuer }}</p>
                @if ($certificate->summary)<p>{{ $certificate->summary }}</p>@endif
                @if ($certificate->image_url)
                <details class="certificate-viewer">
                    <summary>View certificate<span class="sr-only">: {{ $certificate->title }}</span></summary>
                    <figure>
                        <img src="{{ $certificate->image_url }}" alt="{{ $certificate->issuer }} certificate confirming Edilson Santos completed {{ $certificate->title }} on {{ $certificate->completed_on->format('j F Y') }}" @if ($certificate->image_width) width="{{ $certificate->image_width }}" height="{{ $certificate->image_height }}"@endif loading="lazy" decoding="async">
                        <figcaption>{{ $certificate->title }} &mdash; {{ $certificate->issuer }}</figcaption>
                    </figure>
                </details>
                @endif
                @if ($certificate->verification_url)
                    <a class="certificate-verify" href="{{ $certificate->verification_url }}" target="_blank" rel="noopener noreferrer">Verify with {{ $certificate->issuer }} <span class="sr-only">(opens in a new tab)</span></a>
                @endif
            </li>
        @endforeach
    </ol>
@endif

@if (count($plans ?? []))
    <h2>Where I want to grow next</h2>
    <p class="education-note">Planned goals, not qualifications already earned or programmes currently enrolled in. No exam or study dates are confirmed.</p>
    <ul class="education-plans" aria-label="Planned learning goals">
        @foreach ($plans as $plan)
            <li class="tile job">
                <span class="pill">Planned</span>
                <h3>{{ $plan->title }}</h3>
                <p class="pos">{{ $plan->issuer }}</p>
                @if ($plan->summary)<p>{{ $plan->summary }}</p>@endif
            </li>
        @endforeach
    </ul>
@endif
