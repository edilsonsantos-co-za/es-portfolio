<!DOCTYPE html>
<html lang="en">
    <head>
    @include('admin.layouts.partials._header')
    </head>
    <body id="body">
        @include('common.preloader2')
        @yield('body-content')
        <footer class="text-center text-muted py-2">
            <small>Release {{ $appReleaseTag }}</small>
        </footer>
        @yield('scripts')
    </body>
</html>
