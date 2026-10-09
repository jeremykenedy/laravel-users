const { readFileSync, writeFileSync, mkdirSync } = require('node:fs');
const postcss = require('postcss');
const { buildSync, transformSync } = require('esbuild');

const frameworks = ['materialize', 'bulma', 'foundation'];
const sourceDirectory = 'src/resources/css/frameworks';
const assetDirectory = 'src/resources/assets';
const versions = JSON.parse(readFileSync(`${sourceDirectory}/versions.json`, 'utf8'));
const aliases = {
    materialize: { btn: '.lu-button', 'card-content': '.lu-pad' },
    bulma: { button: '.lu-button', input: '.lu-input:not(select), .lu-email-input:not(select):not(textarea), .lu-column-filter', textarea: 'textarea.lu-email-input' },
    foundation: { button: '.lu-button' },
};

function licenseNotices(framework) {
    const notices = new Set(versions[framework].notices.map(file => readFileSync(`src/resources/licenses/${file}`, 'utf8').trim()));
    return Array.from(notices, notice => `/*!\n${notice.replace(/\*\//g, '* /')}\n*/\n`).join('');
}

function scopedStyles(framework) {
    const scope = `:where(#laravelusers[data-lu-css="${framework}"])`;
    const root = postcss.parse(readFileSync(`${sourceDirectory}/${framework}.css`, 'utf8'));
    const animations = new Map();

    root.walkAtRules(rule => {
        if (rule.name === 'charset' || (rule.name === 'media' && rule.params.includes('prefers-color-scheme'))) {
            rule.remove();
        } else if (rule.name.endsWith('keyframes')) {
            const name = `lu-${framework}-${rule.params}`;
            animations.set(rule.params, name);
            rule.params = name;
        }
    });

    root.walkRules(rule => {
        if (rule.parent.type === 'atrule' && rule.parent.name.endsWith('keyframes')) return;

        rule.selectors = rule.selectors.map(selector => {
            for (const [name, alias] of Object.entries(aliases[framework])) {
                selector = selector.replace(new RegExp(`\\.${name}(?![\\w-])`, 'g'), `:is(.${name}, ${alias})`);
            }
            const theme = selector.match(/^(?:\[data-theme=(light|dark)\]|\.theme-(light|dark))$/);
            if (theme) return `${scope}[data-lu-theme="${theme[1] || theme[2]}"]`;
            if (/^(?:html|body|:root)(?=[\s.:\[#]|$)/.test(selector)) {
                return selector.replace(/^(?:html|body|:root)/, scope);
            }

            return `${scope} ${selector}`;
        });
    });

    root.walkDecls(declaration => {
        if (!declaration.prop.includes('animation')) return;
        for (const [name, scopedName] of animations) {
            declaration.value = declaration.value.replace(new RegExp(`\\b${name}\\b`, 'g'), scopedName);
        }
    });

    return root.toString();
}

mkdirSync(assetDirectory, { recursive: true });
for (const framework of frameworks) {
    const css = scopedStyles(framework) + '\n' + readFileSync(`${sourceDirectory}/${framework}-adapter.css`, 'utf8') + '\n' + readFileSync(`${sourceDirectory}/controls.css`, 'utf8');
    const result = transformSync(css, { loader: 'css', minify: true, legalComments: 'inline' });
    writeFileSync(`${assetDirectory}/${framework}.css`, licenseNotices(framework) + result.code);
}

const typography = postcss.parse(readFileSync('node_modules/@material/web/typography/md-typescale-styles.css', 'utf8'));
typography.walkRules(rule => {
    rule.selectors = rule.selectors.map(selector => `:where(#laravelusers[data-lu-css="material3"]) ${selector}`);
});
const materialCss = typography.toString() + '\n' + readFileSync(`${sourceDirectory}/material3-adapter.css`, 'utf8') + '\n' + readFileSync(`${sourceDirectory}/controls.css`, 'utf8');
writeFileSync(`${assetDirectory}/material3.css`, licenseNotices('material3') + transformSync(materialCss, { loader: 'css', minify: true, legalComments: 'inline' }).code);

buildSync({
    entryPoints: ['src/resources/js/material3.js'],
    outfile: `${assetDirectory}/material3.js`,
    bundle: true,
    minify: true,
    format: 'esm',
    target: ['es2020'],
    banner: { js: licenseNotices('material3') },
    legalComments: 'inline',
});
