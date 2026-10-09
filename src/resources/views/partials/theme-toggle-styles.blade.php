<style>
    button.lu-theme-toggle { display: grid; place-items: center; flex-shrink: 0; width: 42px; height: 42px; padding: 0; background: #fff; color: #64748b; border: 1px solid #dce3ed; border-radius: .6rem; cursor: pointer; }
    button.lu-theme-toggle:hover { background: #edf2f9; color: #1d4ed8; }
    button.lu-theme-toggle:disabled { cursor: not-allowed; }
    button.lu-theme-toggle:focus-visible { outline: 3px solid #60a5fa; outline-offset: 3px; }
    :where([data-lu-theme="dark"]) button.lu-theme-toggle { background: #1b2536; color: #a5b3c7; border-color: #364359; }
    :where([data-lu-theme="dark"]) button.lu-theme-toggle:hover { background: #253248; color: #93c5fd; }
    .lu-theme-toggle svg { pointer-events: none; }
    .lu-theme-toggle svg[hidden] { display: none; }
</style>
