{{-- Visit pixel. Plain JS: the Atlas theme does not load jQuery. --}}
<script>
    (function () {
        var key = 'pixel-tracking';
        var trackingId = null;
        try { trackingId = localStorage.getItem(key); } catch (e) {}
        if (!trackingId) {
            trackingId = (window.crypto && crypto.randomUUID) ? crypto.randomUUID().replace(/-/g, '') : @json(\Str::random(30));
            try { localStorage.setItem(key, trackingId); } catch (e) {}
        }
        new Image().src = @json(route('pixel-tracker')) + '?event=page_visit&tracking_id=' + encodeURIComponent(trackingId) + '&t=' + Date.now();
    })();
</script>
