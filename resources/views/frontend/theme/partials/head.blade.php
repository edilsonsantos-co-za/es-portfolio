{{-- Shared <head> content for the Orion family of themes. Expects $fonts (Google Fonts query). --}}
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
<link href="https://fonts.googleapis.com/css2?{{ $fonts }}&display=swap" rel="stylesheet">

{{-- Applies the saved colour theme before first paint; shared with the blog, store and admins. --}}
<script src="{{ asset('theme-mode.js') }}?v={{ filemtime(public_path('theme-mode.js')) }}"></script>
