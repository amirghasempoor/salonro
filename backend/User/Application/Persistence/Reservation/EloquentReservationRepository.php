<?php

namespace User\Application\Persistence\Reservation;

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\Reservation as ReservationModel;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Shared\Services\DiscountService;
use User\Domain\Entities\Reservation;
use User\Domain\Repositories\Reservation\ReservationRepositoryInterface;

class EloquentReservationRepository implements ReservationRepositoryInterface
{
    public function __construct(private readonly DiscountService $discountService) {}

    public function expertName(int $expertId): string
    {
        return Expert::query()->findOrFail($expertId)->full_name;
    }

    /**
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function workingHours(int $expertId, int $hallId): array
    {
        return Expert::query()->findOrFail($expertId)
            ->workingHoursAtHall($hallId)
            ->get(['day', 'from', 'to'])
            ->map(fn ($window) => [
                'day' => $window->day,
                'from' => $window->from,
                'to' => $window->to,
            ])
            ->all();
    }

    /**
     * @return array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>
     */
    public function busySlotsOn(DateTimeInterface $day, int $expertId, int $userId): array
    {
        $dayStart = Carbon::instance($day)->startOfDay();
        $dayEnd = $dayStart->copy()->addDay();

        return ReservationModel::query()
            ->where(fn ($query) => $query->where('expert_id', $expertId)->orWhere('user_id', $userId))
            ->where('state_id', '!=', ReservationStates::Cancel->value)
            ->where('start_time', '<', $dayEnd)
            ->where('finish_time', '>', $dayStart)
            ->get(['id', 'start_time', 'finish_time'])
            ->map(fn (ReservationModel $reservation) => [
                'id' => $reservation->id,
                'start' => $reservation->start_time,
                'finish' => $reservation->finish_time,
            ])
            ->all();
    }

    public function find(int $reservationId): Reservation
    {
        $model = ReservationModel::query()->findOrFail($reservationId);

        $lines = DB::table('reservation_services')
            ->where('reservation_id', $reservationId)
            ->get()
            ->mapWithKeys(fn ($row) => [
                $row->service_id => [
                    'service_name' => $row->service_name,
                    'price' => (int) $row->price,
                    'duration' => $row->duration === null ? null : (int) $row->duration,
                ],
            ])
            ->all();

        return new Reservation(
            id: $model->id,
            userId: $model->user_id,
            userName: $model->user_name,
            expertId: $model->expert_id,
            expertName: $model->expert_name,
            hallId: $model->hall_id,
            hallName: $model->hall_name,
            start: DateTimeImmutable::createFromInterface($model->start_time),
            finish: DateTimeImmutable::createFromInterface($model->finish_time),
            lines: $lines,
            baseTotal: array_sum(array_column($lines, 'price')),
        );
    }

    public function save(Reservation $reservation): Reservation
    {
        if ($reservation->id === null) {
            $model = ReservationModel::query()->create([
                'user_id' => $reservation->userId,
                'user_name' => $reservation->userName,
                'expert_id' => $reservation->expertId,
                'expert_name' => $reservation->expertName,
                'hall_id' => $reservation->hallId,
                'hall_name' => $reservation->hallName,
                'state_id' => ReservationStates::Reserve->value,
                'state_name' => ReservationStates::Reserve->label(),
                'start_time' => $reservation->start,
                'finish_time' => $reservation->finish,
                'total_price' => $reservation->baseTotal,
            ]);

            $model->services()->attach($reservation->lines);

            return $reservation->withId($model->id);
        }

        $model = ReservationModel::query()->findOrFail($reservation->id);

        $model->update([
            'expert_id' => $reservation->expertId,
            'expert_name' => $reservation->expertName,
            'hall_id' => $reservation->hallId,
            'hall_name' => $reservation->hallName,
            'start_time' => $reservation->start,
            'finish_time' => $reservation->finish,
            'total_price' => $reservation->baseTotal,
        ]);

        $model->services()->sync($reservation->lines);

        return $reservation;
    }

    public function applyBestDiscount(Reservation $reservation): void
    {
        $best = $this->discountService->resolveBest(
            $reservation->hallId,
            $reservation->userId,
            Carbon::instance($reservation->start),
            $reservation->baseTotal,
        );

        if ($best !== null) {
            $this->discountService->apply(
                ReservationModel::query()->findOrFail($reservation->id),
                $best['discount'],
                $reservation->baseTotal,
            );
        }
    }

    public function recomputeDiscount(Reservation $reservation): void
    {
        $this->discountService->recomputeExisting(
            ReservationModel::query()->findOrFail($reservation->id),
            $reservation->hallId,
            $reservation->baseTotal,
        );
    }
}
