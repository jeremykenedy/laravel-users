<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\AnonymousNotifiable;
use jeremykenedy\laravelusers\Notifications\UserMessage;
use jeremykenedy\laravelusers\Support\AccountLinks;
use jeremykenedy\laravelusers\Support\DeletedUserRetention;
use jeremykenedy\laravelusers\Support\EmailContent;

class SendGoodbye
{
    public function __construct(private readonly Dispatcher $notifications, private readonly AccountLinks $links)
    {
    }

    public function handle(Model $user, array $data): void
    {
        if (!config('laravelusers.emails.enabled', false) || !config('laravelusers.emails.goodbye', false)
            || (!config('laravelusers.emails.goodbye_auto_send', false) && empty($data['send_goodbye']))) {
            return;
        }
        $defaults = EmailContent::defaults('goodbye');
        foreach (['use_greeting' => true, 'greeting' => 'Hi', 'include_name' => true, 'use_signoff' => true, 'signoff' => 'Thanks', 'signoff_name' => ''] as $key => $value) {
            $defaults[$key] = config('laravelusers.emails.'.$key, $value);
        }
        $contents = array_replace($defaults, $data['goodbye'] ?? [], ['action' => 'goodbye']);
        $actions = [];
        if (in_array(SoftDeletes::class, class_uses_recursive($user), true) && $user->trashed()) {
            foreach (['restore', 'force_delete'] as $action) {
                if (config('laravelusers.emails.goodbye_'.$action, false)) {
                    $actions[] = $action;
                }
            }
            if (config('laravelusers.cleanup.enabled', false) && config('laravelusers.emails.goodbye_retention', false)) {
                $contents['retentionUntil'] = DeletedUserRetention::expiresAt($user)->toIso8601String();
            }
            if ($actions) {
                $mode = config('laravelusers.emails.goodbye_expiry_mode', 'custom');
                $minutes = $mode === 'never' ? 0 : (int) config('laravelusers.emails.goodbye_duration', 60) * (['minutes' => 1, 'hours' => 60, 'days' => 1440][config('laravelusers.emails.goodbye_unit', 'minutes')] ?? 1);
                $urls = $mode === 'cleanup' ? $this->links->issueForCleanup($user, $actions) : $this->links->issue($user, $actions, $minutes);
                $contents['linkExpiry'] = $mode === 'cleanup' ? DeletedUserRetention::expiresAt($user)->toIso8601String() : ($minutes === 0 ? null : now()->addMinutes($minutes)->toIso8601String());
            }
        }
        $contents['showLinkExpiry'] = (bool) config('laravelusers.emails.goodbye_show_expiry', true);
        $this->notifications->send((new AnonymousNotifiable())->route('mail', $user->email), new UserMessage($user->name, $contents, $urls ?? []));
    }
}
