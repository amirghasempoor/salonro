<?php

namespace User\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * An expert's recurring weekly working hours at a hall, used to compute the
 * concrete free slots left over in a period once their existing bookings
 * (at any hall — a double-booked expert is unavailable everywhere) are
 * subtracted from those working hours.
 */
final readonly class StaffAvailability
{
    /**
     * @param  list<array{day: string, from: string, to: string}>  $workingHours  weekly recurring windows
     */
    public function __construct(private array $workingHours) {}

    /**
     * @param  list<array{start: DateTimeInterface, finish: DateTimeInterface}>  $busySlots  the expert's existing bookings that may fall inside the period
     * @return list<array{start: DateTimeImmutable, finish: DateTimeImmutable}>
     */
    public function freeSlots(DateTimeInterface $from, DateTimeInterface $to, array $busySlots): array
    {
        $freeSlots = [];

        foreach ($this->workingWindowsBetween($from, $to) as $window) {
            array_push($freeSlots, ...$this->subtractBusySlots($window['start'], $window['finish'], $busySlots));
        }

        return $freeSlots;
    }

    /**
     * Expand the recurring weekly schedule into concrete windows for every
     * day the period touches, clipped to $from/$to.
     *
     * @return list<array{start: DateTimeImmutable, finish: DateTimeImmutable}>
     */
    private function workingWindowsBetween(DateTimeInterface $from, DateTimeInterface $to): array
    {
        $from = DateTimeImmutable::createFromInterface($from);
        $to = DateTimeImmutable::createFromInterface($to);

        if ($to <= $from) {
            return [];
        }

        $windows = [];
        $day = $from->setTime(0, 0);
        $lastDay = $to->setTime(0, 0);

        while ($day <= $lastDay) {
            $dayName = strtolower($day->format('D'));

            foreach ($this->workingHours as $workingHour) {
                if (strtolower($workingHour['day']) !== $dayName) {
                    continue;
                }

                $start = $this->laterOf($this->atTimeOfDay($day, $workingHour['from']), $from);
                $finish = $this->earlierOf($this->atTimeOfDay($day, $workingHour['to']), $to);

                if ($finish > $start) {
                    $windows[] = ['start' => $start, 'finish' => $finish];
                }
            }

            $day = $day->modify('+1 day');
        }

        return $windows;
    }

    /**
     * The gaps left in one working window once the busy slots overlapping it
     * are cut out.
     *
     * @param  list<array{start: DateTimeInterface, finish: DateTimeInterface}>  $busySlots
     * @return list<array{start: DateTimeImmutable, finish: DateTimeImmutable}>
     */
    private function subtractBusySlots(DateTimeImmutable $windowStart, DateTimeImmutable $windowFinish, array $busySlots): array
    {
        $overlapping = [];

        foreach ($busySlots as $busy) {
            $busyStart = DateTimeImmutable::createFromInterface($busy['start']);
            $busyFinish = DateTimeImmutable::createFromInterface($busy['finish']);

            if ($busyStart < $windowFinish && $busyFinish > $windowStart) {
                $overlapping[] = [
                    'start' => $this->laterOf($busyStart, $windowStart),
                    'finish' => $this->earlierOf($busyFinish, $windowFinish),
                ];
            }
        }

        usort($overlapping, fn (array $a, array $b) => $a['start'] <=> $b['start']);

        $freeSlots = [];
        $cursor = $windowStart;

        foreach ($overlapping as $busy) {
            if ($busy['start'] > $cursor) {
                $freeSlots[] = ['start' => $cursor, 'finish' => $busy['start']];
            }

            if ($busy['finish'] > $cursor) {
                $cursor = $busy['finish'];
            }
        }

        if ($cursor < $windowFinish) {
            $freeSlots[] = ['start' => $cursor, 'finish' => $windowFinish];
        }

        return $freeSlots;
    }

    private function laterOf(DateTimeImmutable $a, DateTimeImmutable $b): DateTimeImmutable
    {
        return $a > $b ? $a : $b;
    }

    private function earlierOf(DateTimeImmutable $a, DateTimeImmutable $b): DateTimeImmutable
    {
        return $a < $b ? $a : $b;
    }

    /**
     * @param  string  $time  'H:i' or 'H:i:s', hour may be unpadded
     */
    private function atTimeOfDay(DateTimeImmutable $day, string $time): DateTimeImmutable
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time) + [0, 0, 0]);

        return $day->setTime($hours, $minutes, $seconds);
    }
}
