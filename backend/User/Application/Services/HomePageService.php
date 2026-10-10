<?php

namespace User\Application\Services;

use App\Models\Hall;
use Illuminate\Http\Request;
use Shared\Facades\DataTable\DataTableFacade;
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
}
