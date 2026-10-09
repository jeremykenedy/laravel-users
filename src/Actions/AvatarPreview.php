<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Actions;

use Illuminate\Database\Eloquent\Model;
use jeremykenedy\laravelusers\Support\Avatar;

class AvatarPreview
{
    public const SAMPLES = [
        'profile'      => ['name' => 'Jordan Ellis', 'initials' => 'JE', 'background' => '264e36'],
        'edit'         => ['name' => 'Casey Morgan', 'initials' => 'CM', 'background' => '705000'],
        'profile_dark' => ['name' => 'Taylor Reed', 'initials' => 'TR', 'background' => '2458b7'],
        'edit_dark'    => ['name' => 'Avery Parker', 'initials' => 'AP', 'background' => '704077'],
    ];

    public function __construct(private readonly Avatar $avatar)
    {
    }

    public function handle(string $source): array
    {
        $avatars = [];
        foreach (self::SAMPLES as $kind => $sample) {
            $user = new class() extends Model {
                protected $fillable = ['id', 'name', 'email'];

                public $incrementing = false;

                protected $keyType = 'string';
            };
            $user->fill(['id' => $kind, 'name' => $sample['name'], 'email' => $kind.'@example.invalid']);
            $user->setAttribute(config('laravelusers.avatar.attribute', 'avatar'), route('users.settings.avatar-preview.image', ['sample' => $kind], false));
            $avatars[$kind] = ['name' => $sample['name'], 'avatar' => $this->avatar->forUser($user, $source)];
        }

        return $avatars;
    }

    public function image(string $sample): array
    {
        return self::SAMPLES[$sample] + ['size' => 128, 'color' => 'ffffff'];
    }
}
