<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use Illuminate\Support\Facades\Blade;
use jeremykenedy\laravelusers\Test\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class IconTest extends TestCase
{
    public function test_every_icon_used_by_a_view_has_a_matching_glyph(): void
    {
        $viewRoot = dirname(__DIR__, 2).'/src/resources/views';
        $iconView = file_get_contents($viewRoot.'/partials/icon.blade.php');
        preg_match_all("/@case\\('([^']+)'\\)/", $iconView, $caseMatches);
        $defined = $caseMatches[1];
        $used = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewRoot)) as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            preg_match_all("/partials\\.icon',\\s*\\['name'\\s*=>\\s*'([^']+)'/", $contents, $includeMatches);
            array_push($used, ...$includeMatches[1]);

            if (str_contains($file->getPathname(), '/partials/icon-templates.blade.php')) {
                preg_match('/@foreach\(\[(.*?)\] as \$icon\)/s', $contents, $templateMatch);
                preg_match_all("/'([^']+)'/", $templateMatch[1] ?? '', $names);
                array_push($used, ...$names[1]);
            }
        }

        $this->assertSame([], array_values(array_diff(array_unique($used), $defined)));

        foreach ($defined as $name) {
            $svg = Blade::render("@include('laravelusers::partials.icon', ['name' => '$name'])");
            $this->assertStringContainsString('<svg', $svg, $name);
            $this->assertMatchesRegularExpression('/<(?:path|circle|rect)\\b/', $svg, $name);
        }
    }
}
