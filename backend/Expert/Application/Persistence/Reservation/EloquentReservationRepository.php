<?php

namespace Expert\Application\Persistence\Reservation;

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\Hall as HallModel;
use App\Models\HallService;
use App\Models\Reservation as ReservationModel;
use App\Models\User;
use App\Services\DiscountService;
use DateTimeImmutable;
use DateTimeInterface;
use Expert\Domain\Entities\Hall;
use Expert\Domain\Entities\Reservation;
use Expert\Domain\Repositories\Reservation\ReservationRepositoryInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class EloquentReservationRepository implements ReservationRepositoryInterface
{
    public function __construct(private readonly DiscountService $discountService) {}

    /**
     * @return array{id: int, name: string}
     */
    public function findOrCreateCustomer(string $phoneNumber, string $firstName, string $lastName): array
    {
        $user = User::query()->firstOrCreate(
            ['phone_number' => $phoneNumber],
            ['first_name' => $firstName, 'last_name' => $lastName],
        );

        return ['id' => $user->id, 'name' => $user->first_name.' '.$user->last_name];
    }

    public function expertName(int $expertId): string
    {
        return Expert::query()->findOrFail($expertId)->full_name;
    }

    public function findHall(int $hallId): Hall
    {
        $hall = HallModel::query()->findOrFail($hallId);

        $offered = HallService::query()
            ->where('hall_id', $hallId)
            ->with('service')
            ->get()
            ->mapWithKeys(fn (HallService $hallService) => [
                $hallService->service_id => [
                    'name' => $hallService->service->sub_cat_name,
                    'price' => (int) $hallService->price,
                    'duration' => $hallService->duration === null ? null : (int) $hallService->duration,
                ],
            ])
            ->all();

        return new Hall($hall->id, $hall->name, $offered);
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
