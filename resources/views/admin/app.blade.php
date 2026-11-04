@extends('admin.layouts.master')

@section('styles')
    <style>
        :root {
            --z-accent-color: {{$settings['accentColor']}};
        }
    </style>
@endsection

@section('body-content')
    <div id="react-root"></div>
@endsection

@section('scripts')
    <script>
        const settings = @json($settings);
        window.esSso = { enabled: @json(\App\Services\Sso\LogtoClient::fromConfig()->enabled()), loginUrl: @json(route('sso.login')), logoutUrl: @json(route('sso.logout')) };
        window.esStoreUrl = @json(config('custom.store_url'));
        window.esJournalUrl = @json(config('custom.journal_url'));
        window.esThemes = @json(app(\App\Services\InstalledThemes::class)->all());
    </script>
    <script src="{{ asset('assets/es-apps.js') }}?v={{ filemtime(public_path('assets/es-apps.js')) }}" defer></script>
    @vite('resources/js/client/admin/roots/app.js')
@endsection