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
        window.edfolioSso = { enabled: @json(\App\Services\Sso\LogtoClient::fromConfig()->enabled()), loginUrl: @json(route('sso.login')), logoutUrl: @json(route('sso.logout')) };
        window.edfolioStoreUrl = @json(config('custom.store_url'));
    </script>
    @vite('resources/js/client/admin/roots/app.js')
@endsection