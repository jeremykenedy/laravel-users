<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Console;

use Illuminate\Console\Command;
use Throwable;

class PublishCommand extends Command
{
    protected $signature = 'laravelusers:publish';

    protected $description = 'Publish Laravel Users configuration, views and translations';

    public function __construct()
    {
        parent::__construct();
        $this->setAliases(['laravel-users:publish']);
    }

    public function handle(): int
    {
        try {
            $this->info('Publishing Laravel Users package files. Existing host files will be preserved.');
            $result = ConsolePrompts::spin(
                $this,
                fn () => $this->call('vendor:publish', ['--tag' => 'laravelusers', '--no-interaction' => true]),
                'Publishing configuration, views and translations...',
                $this->input->isInteractive()
            );

            if ($result !== self::SUCCESS) {
                $this->error('Package files could not be published.');

                return self::FAILURE;
            }

            ConsolePrompts::table($this, ['Package files', 'Action'], [
                ['Configuration', 'Published when missing'],
                ['Views', 'Published when missing'],
                ['Translations', 'Published when missing'],
            ], $this->input->isInteractive());

            ConsolePrompts::outro($this, 'Laravel Users files are ready.', $this->input->isInteractive());

            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Laravel Users files could not be published. Review the application log for details.');

            return self::FAILURE;
        }
    }
}
