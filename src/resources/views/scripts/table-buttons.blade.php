<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    function labels() {
        root.querySelectorAll('[data-lu-table] .btn').forEach(function (button) {
            const label = button.textContent.trim().replace(/\s+/g, ' ');
            if (label) button.setAttribute('aria-label', label);
            if (label && @json((bool) config('laravelusers.tooltipsEnabled', true)) && !button.title && !button.hasAttribute('data-original-title')) button.title = label;
        });
    }
    labels();
    root.addEventListener('lu:rows', labels);
})();
</script>
