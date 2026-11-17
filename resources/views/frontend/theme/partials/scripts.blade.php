{{--
    Shared behaviour for the Orion family: mobile menu (#menu-btn / #menu),
    rotating tagline (#tagline), reveal-on-scroll ([data-reveal]) and the contact form. Expects $taglines.
--}}
<script>
    (function () {
        var reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;

        var menu = document.getElementById('menu');
        var menuBtn = document.getElementById('menu-btn');
        if (menu && menuBtn) {
            menuBtn.addEventListener('click', function () {
                var open = menu.classList.toggle('open');
                menuBtn.setAttribute('aria-expanded', open);
            });
            menu.addEventListener('click', function (e) {
                if (e.target.tagName === 'A') { menu.classList.remove('open'); menuBtn.setAttribute('aria-expanded', false); }
            });
        }

        var taglines = @json(array_values($taglines));
        var tagline = document.getElementById('tagline');
        if (tagline && taglines.length > 1 && !reduceMotion) {
            var i = 0;
            setInterval(function () {
                i = (i + 1) % taglines.length;
                tagline.classList.add('swap');
                setTimeout(function () { tagline.textContent = taglines[i]; tagline.classList.remove('swap'); }, 220);
            }, 2800);
        }

        var revealed = document.querySelectorAll('[data-reveal]');
        if ('IntersectionObserver' in window && !reduceMotion) {
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) { entry.target.classList.add('in'); io.unobserve(entry.target); }
                });
            }, { rootMargin: '0px 0px -8% 0px' });
            revealed.forEach(function (el) { el.classList.add('reveal'); io.observe(el); });
        }

        // Toast in the same shape as the admin's notifications (title + description, bottom right,
        // closes after 4.5 s), so a visitor gets the same success or failure message the admin would.
        var toast = function (type, text) {
            if (!document.getElementById('es-toast-style')) {
                var st = document.createElement('style');
                st.id = 'es-toast-style';
                st.textContent = '.es-toasts{position:fixed;right:24px;bottom:24px;z-index:1000;display:grid;gap:12px;max-width:min(384px,calc(100vw - 32px))}'
                    + '.es-toast{display:grid;grid-template-columns:auto 1fr auto;gap:12px;align-items:start;padding:16px 18px;border-radius:14px;background:#181d29;border:1px solid #232a38;color:#e8edf6;box-shadow:0 12px 32px rgba(0,0,0,.45);font:500 14px/1.5 Manrope,system-ui,sans-serif;animation:es-toast-in .25s ease-out}'
                    + '.es-toast b{display:block;font-size:15px;margin-bottom:2px}.es-toast .i{width:22px;height:22px;border-radius:50%;display:grid;place-items:center;font-weight:800;font-size:13px;color:#fff}'
                    + '.es-toast.success .i{background:#22c55e}.es-toast.error .i{background:#ef4444}.es-toast button{background:none;border:0;color:#8f9ab0;font-size:18px;line-height:1;cursor:pointer;padding:0}'
                    + '@keyframes es-toast-in{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:none}}@media (prefers-reduced-motion:reduce){.es-toast{animation:none}}';
                document.head.appendChild(st);
            }
            var box = document.querySelector('.es-toasts');
            if (!box) { box = document.createElement('div'); box.className = 'es-toasts'; box.setAttribute('aria-live', 'polite'); document.body.appendChild(box); }
            var el = document.createElement('div');
            el.className = 'es-toast ' + type;
            el.setAttribute('role', type === 'error' ? 'alert' : 'status');
            var icon = document.createElement('span'); icon.className = 'i'; icon.textContent = type === 'error' ? '!' : '✓';
            var body = document.createElement('div'); var title = document.createElement('b');
            title.textContent = type === 'error' ? 'Error' : 'Success';
            body.appendChild(title); body.appendChild(document.createTextNode(text));
            var close = document.createElement('button'); close.type = 'button'; close.setAttribute('aria-label', 'Close'); close.textContent = '×';
            close.addEventListener('click', function () { el.remove(); });
            el.appendChild(icon); el.appendChild(body); el.appendChild(close); box.appendChild(el);
            setTimeout(function () { el.remove(); }, 4500);
        };

        var form = document.getElementById('contact-form');
        if (form) {
            var note = document.getElementById('form-note');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!form.checkValidity()) { toast('error', 'Please fill in every field with a valid email.'); return; }
                var button = form.querySelector('button');
                button.disabled = true;
                note.textContent = 'Sending…';
                fetch(@json(route('contact-me')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: new FormData(form)
                })
                    .then(function (r) {
                        if (r.status === 429) return { status: 429, message: 'Too many messages from this connection. Please try again later.' };
                        return r.json();
                    })
                    .then(function (res) {
                        var ok = res.status === 200;
                        toast(ok ? 'success' : 'error', res.message || (ok ? 'Message sent. Thank you!' : 'Something went wrong.'));
                        if (ok) form.reset();
                    })
                    .catch(function () { toast('error', 'Something went wrong. Please try again in a minute.'); })
                    .finally(function () { button.disabled = false; note.textContent = ''; });
            });
        }
    })();
</script>

@if (!empty($portfolioConfig['script']['footer']))
    <script>
        {!! $portfolioConfig['script']['footer'] !!}
    </script>
@endif
@include('common.pixelTracking')
