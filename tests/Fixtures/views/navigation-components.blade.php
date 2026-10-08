<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Navigation components</title>
<style>body{font:16px system-ui;margin:24px}nav{display:flex;align-items:center;justify-content:flex-end;gap:16px}html[data-lu-theme="dark"]{background:#111827;color:#edf2fa}</style></head>
<body><nav aria-label="Application navigation"><x-laravelusers::user-menu /><x-laravelusers::theme-toggle id="dashboard-theme" /><x-laravelusers::theme-toggle id="secondary-theme" /></nav><h1>Application dashboard</h1><button type="button">Outside menu</button>@stack('laravelusers-components-scripts')</body></html>
