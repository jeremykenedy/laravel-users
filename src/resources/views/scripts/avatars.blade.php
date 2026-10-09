<script>
(function () {
    const root = document;
    if (root.documentElement.dataset.luAvatarsReady) return;
    root.documentElement.dataset.luAvatarsReady = 'true';
    function fallback(image) { image.hidden = true; }
    root.addEventListener('error', function (event) {
        if (event.target.matches('.lu-avatar img')) fallback(event.target);
    }, true);
    root.querySelectorAll('.lu-avatar img').forEach(function (image) {
        if (image.complete && image.naturalWidth === 0) fallback(image);
    });
})();
</script>
