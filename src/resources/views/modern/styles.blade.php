@if(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'bootstrap5' && config('laravelusers.enableBootstrapCssCdn'))
    <link rel="stylesheet" href="{{ config('laravelusers.bootstrap5CssCdn') }}">
@elseif(\jeremykenedy\laravelusers\Support\Frontend::framework() === 'tailwind')
    <style>@include('laravelusers::modern.tailwind')</style>
@endif
<style>
    #laravelusers { --lu-bg: #fff; --lu-text: #172033; --lu-muted: #526077; --lu-border: #d4dbe5; --lu-accent: #2456c2; --lu-soft: #f4f6fa; color: var(--lu-text); font: 16px/1.6 system-ui, sans-serif; }
    #laravelusers[data-lu-theme="dark"] { --lu-bg: #1d2738; --lu-text: #edf2fa; --lu-muted: #b4c2d6; --lu-border: #42516a; --lu-accent: #94b9ff; --lu-soft: #111827; color-scheme: dark; }
    #laravelusers *, #laravelusers *::before, #laravelusers *::after { box-sizing: border-box; }
    #laravelusers { max-width: 1160px; margin: auto; padding: 28px 24px 64px; }
    #laravelusers h1 { font-size: clamp(1.6rem, 4vw, 2.2rem); line-height: 1.25; letter-spacing: -.035em; margin: 0 0 8px; font-weight: 700; }
    #laravelusers h2 { font-size: 1.25rem; margin: 0; }
    #laravelusers p { margin: 0 0 16px; }
    #laravelusers a { color: var(--lu-accent); text-decoration: none; }
    #laravelusers a:hover { text-decoration: underline; }
    #laravelusers .lu-toolbar, #laravelusers .lu-heading, #laravelusers .lu-actions { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
    #laravelusers .lu-toolbar, #laravelusers .lu-heading { justify-content: space-between; margin-bottom: 28px; }
    #laravelusers .lu-toolbar { border-bottom: 1px solid var(--lu-border); padding-bottom: 20px; font-size: .875rem; }
    #laravelusers .lu-brand { color: var(--lu-muted); font-weight: 600; }
    #laravelusers .lu-muted { color: var(--lu-muted); }
    #laravelusers .lu-panel { background: var(--lu-bg); border: 1px solid var(--lu-border); border-radius: 12px; overflow: hidden; box-shadow: 0 3px 12px #00000006; }
    #laravelusers .lu-pad { padding: 24px; }
    #laravelusers .lu-button { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-height: 42px; padding: 8px 16px; border: 1px solid transparent; border-radius: 7px; background: #2456c2; color: #fff; font: inherit; font-size: .875rem; font-weight: 600; cursor: pointer; text-decoration: none; }
    #laravelusers .lu-secondary { color: var(--lu-text); background: var(--lu-bg); border-color: var(--lu-border); }
    #laravelusers .lu-danger { color: #fff; background: #b42332; }
    #laravelusers :focus-visible { outline: 3px solid var(--lu-accent); outline-offset: 3px; }
    #laravelusers .lu-scroll { overflow-x: auto; }
    #laravelusers table { width: 100%; border-collapse: collapse; color: var(--lu-text); margin: 0; }
    #laravelusers th, #laravelusers td { padding: 16px 20px; text-align: start; border-bottom: 1px solid var(--lu-border); background: var(--lu-bg); color: var(--lu-text); }
    #laravelusers th { font-size: .8rem; font-weight: 600; background: var(--lu-soft); color: var(--lu-muted); }
    #laravelusers caption { text-align: start; padding: 16px 20px; color: var(--lu-muted); caption-side: bottom; }
    #laravelusers .lu-input { display: block; width: 100%; min-height: 44px; padding: 10px 12px; border: 1px solid var(--lu-border); border-radius: 7px; background: var(--lu-bg); color: var(--lu-text); font: inherit; }
    #laravelusers .lu-input[aria-invalid="true"] { border-color: #b42332; }
    #laravelusers label { display: block; margin-bottom: 6px; font-size: .875rem; font-weight: 600; }
    #laravelusers .lu-field { margin-bottom: 20px; }
    #laravelusers .lu-form { max-width: 720px; }
    #laravelusers .lu-search { display: flex; align-items: end; gap: 12px; flex-wrap: wrap; padding: 20px; }
    #laravelusers .lu-search-field { flex: 1; min-width: 180px; }
    #laravelusers .lu-alert { padding: 16px 20px; margin-bottom: 24px; background: var(--lu-bg); border: 1px solid var(--lu-border); border-inline-start: 4px solid var(--lu-accent); border-radius: 7px; }
    #laravelusers .lu-error { border-inline-start-color: #b42332; }
    #laravelusers .lu-field-error { color: #b42332; font-size: .875rem; }
    #laravelusers[data-lu-theme="dark"] .lu-field-error { color: #ffb4bc; }
    #laravelusers dl { margin: 0; }
    #laravelusers .lu-detail { display: grid; grid-template-columns: minmax(110px, 1fr) 3fr; gap: 16px; padding: 16px 0; border-bottom: 1px solid var(--lu-border); }
    #laravelusers dt { color: var(--lu-muted); font-weight: 500; }
    #laravelusers dd { margin: 0; overflow-wrap: anywhere; }
    #laravelusers .lu-pagination { padding: 20px; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
    #laravelusers [hidden] { display: none !important; }
    @media (max-width: 640px) { #laravelusers { padding: 20px 14px; } #laravelusers .lu-pad { padding: 20px; } #laravelusers .lu-toolbar { align-items: start; } #laravelusers .lu-pagination { flex-wrap: wrap; } }
</style>
