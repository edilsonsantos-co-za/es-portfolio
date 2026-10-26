{{-- Light/dark toggle shared by the Orion family (and mirrored in the blog themes): moon in light mode, sun in dark mode. --}}
<button class="icon-btn theme-toggle" type="button" id="theme-btn" aria-label="Toggle dark mode" title="Toggle dark mode">
    <svg class="i-moon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 14.3A8.5 8.5 0 1 1 9.7 3.5a6.8 6.8 0 0 0 10.8 10.8z"/></svg>
    <svg class="i-sun" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
</button>
<style>
    .theme-toggle svg { display: block; }
    .theme-toggle .i-sun, :root[data-theme="dark"] .theme-toggle .i-moon { display: none; }
    :root[data-theme="dark"] .theme-toggle .i-sun { display: block; }
    @media (prefers-color-scheme: dark) {
        :root:not([data-theme="light"]) .theme-toggle .i-moon { display: none; }
        :root:not([data-theme="light"]) .theme-toggle .i-sun { display: block; }
    }
</style>
