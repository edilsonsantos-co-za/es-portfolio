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

        var form = document.getElementById('contact-form');
        if (form) {
            var note = document.getElementById('form-note');
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                if (!form.checkValidity()) { note.textContent = 'Please fill in every field with a valid email.'; return; }
                var button = form.querySelector('button');
                button.disabled = true;
                note.textContent = 'Sending…';
                fetch(@json(route('contact-me')), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' },
                    body: new FormData(form)
                })
                    .then(function (r) { return r.json(); })
                    .then(function (res) {
                        note.textContent = res.message || (res.status === 200 ? 'Message sent. Thank you!' : 'Something went wrong.');
                        if (res.status === 200) form.reset();
                    })
                    .catch(function () { note.textContent = 'Something went wrong. Please email me instead.'; })
                    .finally(function () { button.disabled = false; });
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
