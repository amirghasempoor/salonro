<?php

namespace Shared\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The windows of one working-hours submission must make sense together: each
 * time is a real clock time, each window ends after it starts, and windows on
 * the same day never overlap. The per-field shape (day, from, to present) is
 * left to the request's own rules.
 */
class WorkingHoursWindows implements ValidationRule
{
    private const string TIME = '/^([01]?\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_array($value)) {
            return;
        }

        $byDay = [];

        foreach ($value as $window) {
            if (! is_array($window) || ! is_string($window['from'] ?? null) || ! is_string($window['to'] ?? null)) {
                return;
            }

            if (! preg_match(self::TIME, $window['from']) || ! preg_match(self::TIME, $window['to'])) {
                $fail('Each working hour must be a valid time like 09:00.');

                return;
            }

            $from = $this->seconds($window['from']);
            $to = $this->seconds($window['to']);

            if ($from >= $to) {
                $fail('Each working hour must end after it starts.');

                return;
            }

            $byDay[$window['day'] ?? ''][] = [$from, $to];
        }

        foreach ($byDay as $windows) {
            usort($windows, fn (array $a, array $b) => $a[0] <=> $b[0]);

            for ($i = 1; $i < count($windows); $i++) {
                if ($windows[$i][0] < $windows[$i - 1][1]) {
                    $fail('Working hours on the same day must not overlap.');

                    return;
                }
            }
        }
    }

    private function seconds(string $time): int
    {
        [$h, $m, $s] = array_map('intval', explode(':', $time) + [0, 0, 0]);

        return $h * 3600 + $m * 60 + $s;
    }
}
