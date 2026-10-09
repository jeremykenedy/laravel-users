@if(config('laravelusers.responsiveTable', false) || config('laravelusers.tableViewToggle', false))
@if(config('laravelusers.tableViewToggle', false))
<template id="lu-view-template">
    <div class="lu-view-toggle" role="group" aria-label="{{ __('laravelusers::ui.list_view') }}">
        <button type="button" class="lu-button lu-secondary" data-lu-view="table" aria-pressed="false" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.table_view') }}" @endif aria-label="{{ __('laravelusers::ui.table_view') }}">@include('laravelusers::partials.icon', ['name' => 'table']) {{ __('laravelusers::ui.table_view') }}</button>
        <button type="button" class="lu-button lu-secondary" data-lu-view="cards" aria-pressed="false" @if(config('laravelusers.tooltipsEnabled', true)) title="{{ __('laravelusers::ui.card_view') }}" @endif aria-label="{{ __('laravelusers::ui.card_view') }}">@include('laravelusers::partials.icon', ['name' => 'cards']) {{ __('laravelusers::ui.card_view') }}</button>
    </div>
</template>
@endif
<script>
(function () {
    const root = document.getElementById('laravelusers');
    const table = root && root.querySelector('[data-lu-table]');
    if (!table) return;
    const enabled = @json((bool) config('laravelusers.tableViewToggle', false));
    const responsive = @json((bool) config('laravelusers.responsiveTable', false));
    const mobile = matchMedia('(max-width: 640px)');
    const key = 'laravelusers.view.' + (table.dataset.luView || 'users');
    let saved;
    if (enabled) {
        try { saved = localStorage.getItem(key); } catch (error) {}
        const template = document.getElementById('lu-view-template');
        table.parentElement.before(template.content.cloneNode(true));
        template.remove();
    }
    function apply() {
        const view = ['table', 'cards'].includes(saved) ? saved : (responsive && mobile.matches ? 'cards' : 'table');
        root.dataset.luTableView = view;
        if (view === 'table') {
            const filters = root.querySelector('.lu-mobile-filters');
            if (filters) filters.open = false;
        }
        root.querySelectorAll('button[data-lu-view]').forEach(button => button.setAttribute('aria-pressed', button.dataset.luView === view ? 'true' : 'false'));
    }
    root.querySelectorAll('button[data-lu-view]').forEach(function (button) {
        button.addEventListener('click', function () {
            saved = button.dataset.luView;
            try { localStorage.setItem(key, saved); } catch (error) {}
            apply();
        });
    });
    mobile.addEventListener('change', apply);
    apply();
})();
</script>
@endif
