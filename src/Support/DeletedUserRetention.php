<?php

declare(strict_types=1);

namespace jeremykenedy\laravelusers\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use RuntimeException;

class DeletedUserRetention
{
    public static function cutoff(): Carbon
    {
        $date = self::shift(Carbon::now('UTC'), false);

        return in_array(config('laravelusers.cleanup.unit', 'days'), ['months', 'years'], true) ? $date->endOfMonth() : $date;
    }

    public static function expiresAt(Model $user): Carbon
    {
        return self::shift(Carbon::parse($user->getRawOriginal($user->getDeletedAtColumn()), 'UTC'), true);
    }

    private static function shift(Carbon $date, bool $forward): Carbon
    {
        $amount = filter_var(config('laravelusers.cleanup.amount', 180), FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 10000]]);
        $unit = config('laravelusers.cleanup.unit', 'days');
        if ($amount === false || !in_array($unit, ['immediately', 'minutes', 'hours', 'days', 'months', 'years'], true) || ($unit !== 'immediately' && $amount < 1)) {
            throw new RuntimeException('Invalid deleted-user cleanup retention settings. No users were deleted.');
        }
        $amount *= $forward ? 1 : -1;

        return match ($unit) {
            'immediately' => $date, 'minutes' => $date->addMinutes($amount), 'hours' => $date->addHours($amount),
            'days'        => $date->addDays($amount), 'months' => $date->addMonthsNoOverflow($amount), 'years' => $date->addYearsNoOverflow($amount),
        };
    }
}
