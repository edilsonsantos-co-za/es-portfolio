<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<script src="{{ asset('theme-mode.js') }}?v={{ filemtime(public_path('theme-mode.js')) }}"></script>

<!-- Favicon -->
<link rel="shortcut icon" id="favicon" type="image/x-icon" href="{{\Utils::getFavicon()}}">
<title>{{Config::get('app.name')}}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@500;600&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="{{ asset('assets/common/lib/fontawesome/css/all.min.css') }}" rel="stylesheet" />
<link href="{{ asset('assets/common/lib/mdi-icon/css/materialdesignicons.min.css') }}" rel="stylesheet" />
@yield('styles')