<?php

namespace User\Application\Services;

use App\Facades\DataTable\DataTableFacade;
use App\Models\Hall;
use Illuminate\Http\Request;
use User\Domain\Repositories\HallRepositoryInterface;

class HomePageService
{
    /**
     * Radius (in kilometres) within which halls are offered to the user.
     */
    private const int SEARCH_RADIUS_KM = 10;

    public function __construct(private readonly HallRepositoryInterface $hallRepository) {}

    public function hallsInArea(Request $request): array
    {
        $query = Hall::query()
            ->nearby((float) $request->query('lat'), (float) $request->query('lng'), self::SEARCH_RADIUS_KM)
            ->orderBy('distance');

        return DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
        );
    }

    /**
     * The hall's services, priced for this hall.
     */
    public function hallServices(Hall $hall): array
    {
        return $this->hallRepository->find($hall->id)->services();
    }

    /**
     * Staff in the hall who provide at least one of the requested services.
     *
     * @param  list<int>  $serviceIds
     * @return list<array{id: int, first_name: string, last_name: string, avatar: ?string}>
     */
    public function hallStaff(Hall $hall, array $serviceIds): array
    {
        return array_map(
            fn (array $member) => [
                'id' => $member['id'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'avatar' => $member['avatar'],
            ],
            $this->hallRepository->find($hall->id)->staffOffering($serviceIds),
        );
    }
}
