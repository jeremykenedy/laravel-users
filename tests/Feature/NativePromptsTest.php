<?php

namespace jeremykenedy\laravelusers\Test\Feature;

use jeremykenedy\laravelusers\Console\ConsolePrompts;
use jeremykenedy\laravelusers\Console\InstallCommand;
use jeremykenedy\laravelusers\Test\TestCase;
use Laravel\Prompts\Key;
use Laravel\Prompts\Prompt;
use ReflectionProperty;
use RuntimeException;

class NativePromptsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Prompt::class) || !method_exists(Prompt::class, 'fake')) {
            $this->markTestSkipped('This Laravel version uses the console compatibility fallback.');
        }
        $this->resetPromptState();
        $this->app->instance('env', 'local');
    }

    protected function tearDown(): void
    {
        if (class_exists(Prompt::class)) {
            $this->resetPromptState();
            Prompt::interactive(false);
        }
        $this->app->instance('env', 'testing');
        parent::tearDown();
    }

    private function resetPromptState(): void
    {
        if (method_exists(Prompt::class, 'flushState')) {
            Prompt::flushState();

            return;
        }
        foreach (['shouldFallback' => false, 'fallbacks' => []] as $name => $value) {
            (new ReflectionProperty(Prompt::class, $name))->setValue(null, $value);
        }
    }

    public function test_native_selections_and_suggestions_preserve_current_defaults(): void
    {
        Prompt::fake([Key::ENTER, Key::ENTER, Key::ENTER, Key::ENTER]);
        $command = new InstallCommand();
        $command->setLaravel($this->app);
        $this->assertSame('dark', ConsolePrompts::select($command, 'Color theme', ['light' => 'light', 'dark' => 'dark'], 'dark', true));
        $this->assertSame('bootstrap5', ConsolePrompts::search($command, 'CSS framework', ['bootstrap4', 'bootstrap5', 'tailwind'], 'bootstrap5', true));
        $this->assertSame('role:admin', ConsolePrompts::text($command, 'Role middleware', 'role:admin', true));
        $this->assertTrue(ConsolePrompts::confirm($command, 'Use these settings?', true, true));
        Prompt::assertOutputContains('CSS framework');
    }

    public function test_native_rendering_and_spinners_report_the_result(): void
    {
        Prompt::fake();
        $command = new InstallCommand();
        $command->setLaravel($this->app);
        ConsolePrompts::intro($command, 'Laravel Users', 'Configure the package.');
        ConsolePrompts::note($command, 'Existing views are preserved.', true);
        ConsolePrompts::table($command, ['Asset', 'Result'], [['users.js', 'Published']], true);
        $this->assertSame(42, ConsolePrompts::spin($command, fn () => 42, 'Publishing assets...', true));
        ConsolePrompts::outro($command, 'Setup completed.', true);
        Prompt::assertOutputContains('Existing views are preserved.');
        Prompt::assertOutputContains('users.js');
        Prompt::assertOutputContains('Setup completed.');
    }

    public function test_native_spinners_propagate_failures_to_the_command_handler(): void
    {
        Prompt::fake();
        $command = new InstallCommand();
        $command->setLaravel($this->app);
        $this->expectException(RuntimeException::class);
        ConsolePrompts::spin($command, function () {
            throw new RuntimeException('Publication failed.');
        }, 'Publishing assets...', true);
    }
}
