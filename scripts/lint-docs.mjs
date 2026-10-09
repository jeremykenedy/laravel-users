import { globSync } from 'node:fs';
import { lint, readConfig } from 'markdownlint/promise';

const files = globSync(['readme.md', 'CHANGELOG.md', 'docs/**/*.md']).sort();
const results = await lint({ files, config: await readConfig('.markdownlint.json') });
let issues = 0;

for (const [file, errors] of Object.entries(results)) {
    for (const error of errors) {
        const detail = error.errorDetail ? `: ${error.errorDetail}` : '';
        console.error(`${file}:${error.lineNumber} ${error.ruleNames[0]} ${error.ruleDescription}${detail}`);
        issues++;
    }
}

console.log(`${files.length} files checked; ${issues} issues.`);
process.exitCode = issues ? 1 : 0;
