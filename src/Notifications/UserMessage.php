<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use jeremykenedy\laravelusers\Support\EmailContent;

class UserMessage extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $name, public readonly array $contents, public readonly array $accountLinks = [])
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $data = array_merge(EmailContent::viewData($this->name, $this->contents), [
            'accountLinks'   => $this->accountLinks,
            'accountMinutes' => !empty($this->contents['account_never_expire']) ? 0 : (isset($this->contents['account_duration']) ? (int) $this->contents['account_duration'] * ['minutes' => 1, 'hours' => 60, 'days' => 1440][$this->contents['account_unit']] : null),
        ]);
        $data += ['retentionUntil' => $this->contents['retentionUntil'] ?? null, 'linkExpiry' => $this->contents['linkExpiry'] ?? null, 'showLinkExpiry' => $this->contents['showLinkExpiry'] ?? true];
        if (($this->contents['action'] ?? null) === 'goodbye') {
            return (new MailMessage())->subject($this->contents['subject'])->markdown('laravelusers::emails.goodbye-user', $data);
        }
        if ($this->accountLinks || !empty($this->contents['deleted'])) {
            return (new MailMessage())->subject($this->contents['subject'])->markdown('laravelusers::emails.deleted-user', $data);
        }

        return (new MailMessage())->subject($this->contents['subject'])->markdown(($this->contents['action'] ?? null) === 'goodbye' ? 'laravelusers::emails.goodbye-user' : 'laravelusers::emails.message', $data);
    }
}
