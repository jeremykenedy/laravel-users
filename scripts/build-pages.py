"""Stage public documentation from the checked-out release tag for GitHub Pages."""

import json
import os
from pathlib import Path
import re
import shutil
from urllib.parse import quote

root = Path(__file__).resolve().parents[1]
output = root / '.pages-source'
output.mkdir(exist_ok=True)
tag = os.environ['RELEASE_TAG']
repository = os.environ.get('GITHUB_REPOSITORY', 'jeremykenedy/laravel-users')
published = {root / 'readme.md', root / 'CHANGELOG.md', *root.glob('docs/**/*.md')}


def destination(source):
    return Path('index.md') if source.name == 'readme.md' else source.relative_to(root)


def rewrite_link(match, source):
    href = match.group(2)
    if re.match(r'^[a-zA-Z][a-zA-Z0-9+.-]*:', href) or href.startswith(('#', '//')):
        return match.group(0)
    path, separator, anchor = href.partition('#')
    target = (source.parent / path).resolve()
    if target in published:
        relative = os.path.relpath(destination(target).with_suffix('.html'), destination(source).parent)
        href = relative + (separator + anchor if separator else '')
    elif target.is_relative_to(root) and not target.relative_to(root).parts[0] in ('art', 'docs'):
        href = f'https://github.com/{repository}/blob/{quote(tag, safe="")}/{target.relative_to(root).as_posix()}' + (separator + anchor if separator else '')
    return match.group(1) + href + match.group(3)


for source in sorted(published):
    target = output / destination(source)
    target.parent.mkdir(parents=True, exist_ok=True)
    body = re.sub(r'(\]\()([^\s)]+)(\))', lambda match: rewrite_link(match, source), source.read_text())
    target.write_text('---\nlayout: default\n---\n\n{% raw %}\n' + body + '\n{% endraw %}\n')

for directory in ('art', 'docs/images'):
    if (root / directory).is_dir():
        shutil.copytree(root / directory, output / directory, dirs_exist_ok=True)

shutil.copy2(root / 'LICENSE', output / 'LICENSE')

config = {
    'theme': 'jekyll-theme-cayman',
    'title': 'Laravel Users',
    'description': f'User management for Laravel with Bootstrap 4 and Bootstrap 5. Documentation for {tag}.',
    'baseurl': os.environ.get('PAGES_BASE_PATH', '/laravel-users'),
    'url': 'https://jeremykenedy.github.io',
}
(output / '_config.yml').write_text(json.dumps(config, indent=2) + '\n')
print(f'Staged {len(published)} documentation pages for {tag}.')
