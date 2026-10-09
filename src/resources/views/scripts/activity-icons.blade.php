@if(config('laravelusers.iconsEnabled', true) && config('laravelusers.activity.login', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    function icon(field, value) {
        const text = value.toLowerCase();
        const choices = field === 'browser' ? ['edge', 'firefox', 'chrome', 'safari'] : ['android', 'windows', 'linux'];
        if (field === 'os' && /mac|ios|iphone|ipad/.test(text)) return 'apple';
        return choices.find(name => text.includes(name)) || ({ browser: 'browser', ip_address: 'network', os: 'device', device: 'device' }[field]);
    }
    function apply() {
        root.querySelectorAll('[data-lu-login-field]').forEach(function (item) {
            if (item.querySelector('svg')) return;
            const name = icon(item.dataset.luLoginField, item.textContent);
            const template = document.querySelector('[data-lu-icon-template="' + name + '"]');
            if (template) item.prepend(template.content.cloneNode(true));
        });
        root.querySelectorAll('[data-lu-activity-icon]').forEach(function (label) {
            const name = icon(label.dataset.luActivityIcon, label.nextElementSibling.textContent);
            const template = document.querySelector('[data-lu-icon-template="' + name + '"]');
            if (template) label.querySelector('svg').replaceWith(template.content.cloneNode(true));
        });
    }
    apply();
    root.addEventListener('lu:rows', apply);
})();
</script>
@endif
