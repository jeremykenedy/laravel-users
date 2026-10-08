@if(config('laravelusers.localizeDates', false))
<script>
(function () {
    const root = document.getElementById('laravelusers');
    if (!root) return;
    let formatter;
    try {
        formatter = new Intl.DateTimeFormat(document.documentElement.lang || undefined, {
            dateStyle: @json(config('laravelusers.dateStyle', 'short')),
            timeStyle: @json(config('laravelusers.timeStyle', 'short')),
            timeZone: @json(config('laravelusers.displayTimezone')) || undefined
        });
    } catch (error) {
        formatter = new Intl.DateTimeFormat(undefined, { dateStyle: 'short', timeStyle: 'short' });
    }
    function format() {
        root.querySelectorAll('[data-lu-time], [data-lu-date]').forEach(function (element) {
            let value = element.getAttribute('datetime') || element.dataset.luDate;
            if (!value) return;
            if (/^\d{4}-\d\d-\d\d[ T]\d\d:\d\d:\d\d$/.test(value)) value = value.replace(' ', 'T') + 'Z';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return;
            const time = element.tagName === 'TIME' ? element : document.createElement('time');
            time.className = 'lu-date';
            time.dateTime = date.toISOString();
            time.textContent = formatter.format(date);
            time.title = date.toISOString() + ' (' + formatter.resolvedOptions().timeZone + ')';
            element.dataset.luValue = date.toISOString();
            if (time !== element) element.replaceChildren(time);
        });
    }
    format();
    root.addEventListener('lu:rows', format);
})();
</script>
@endif
