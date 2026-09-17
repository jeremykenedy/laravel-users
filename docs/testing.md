# Testing

## PHP suite

`composer check` runs Pint and PHPUnit. The package uses Orchestra Testbench and SQLite, with a test user model and a small role API fixture. Tests exercise HTTP routes rather than only calling controller methods. The role fixture verifies the package's existing model contract without requiring an optional package.

CI tests these combinations:

| Laravel | PHP | Testbench |
| --- | --- | --- |
| 8 | 8.1 | 6 |
| 9 | 8.1 | 7 |
| 10 | 8.1 | 8 |
| 11 | 8.2 | 9 |
| 12 | 8.2, 8.3, 8.4 | 10 |
| 13 | 8.3, 8.4, 8.5 | 11 |

Laravel 13 also has a lowest-dependency job. Historical Laravel jobs explicitly allow Composer to resolve dependencies affected by upstream advisories so backward compatibility remains testable. The current dependency job runs `composer audit` without that exception. Package users do not inherit CI-only Composer flags.

The quality job exports coverage for inspection. Coverage counts supplement behavioral assertions; they do not establish compatibility with untested host customizations.

## Browser suite

```sh
npm ci
npx playwright install chromium firefox webkit
npm run test:browser
```

Playwright starts a local PHP server on `127.0.0.1:19847`. The fixture uses an isolated SQLite database and sessions under ignored `tests/browser/runtime`. It creates sample accounts for tests only. No application database is used. Do not serve the fixture publicly.

The suite exercises all three frontends in Chromium, Firefox, and WebKit. It checks search with literal hostile-looking names, theme persistence, system preference changes, mobile overflow, and modern account creation, validation, editing, and deletion. Axe checks the modern form in light and dark mode. The fixture runs real CSRF middleware and submits real forms.

The legacy Bootstrap 4 browser tests load its existing external CDN assets. Modern Bootstrap 5 loads its configured CSS CDN. Tailwind loads its bundled stylesheet. Browser artifacts are uploaded on CI failure.

## Asset builds

`npm run build` compiles prefixed Tailwind utilities into the bundled Blade stylesheet. Commit the generated stylesheet with source changes. CI rebuilds it and fails if the generated result differs from the committed version. No build runs during Composer installation in consuming applications.

## Manual checks

Review list, create, edit, and detail views on desktop and mobile. Check published overrides, custom parent layouts, host authentication and authorization, role models, translations, asset loading, and any custom JavaScript before upgrading a consuming application.
