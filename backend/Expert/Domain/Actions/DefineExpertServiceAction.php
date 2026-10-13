<?php

namespace Expert\Domain\Actions;

use App\Models\Expert;
use App\Models\ExpertService;
use Expert\Domain\DTOs\DefineExpertServiceDto;
use Expert\Domain\Exceptions\ExpertNotAssignedToHallException;
use Expert\Domain\Exceptions\HallDoesNotOfferServiceException;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Let an expert set her own price and duration for one or more services she
 * offers at a hall she is assigned to, in a single call.
 */
readonly class DefineExpertServiceAction
{
    public function __construct(
        private ExpertHallRepositoryInterface $expertHallRepository,
    ) {}

    /**
     * @return array<int, ExpertService>
     *
     * @throws ExpertNotAssignedToHallException when the expert has no active membership at the hall
     * @throws HallDoesNotOfferServiceException when the hall's own catalog does not include one of the services
     * @throws Throwable
     */
    public function execute(Expert $expert, int $hallId, DefineExpertServiceDto $dto): array
    {
        $expertHall = $this->expertHallRepository->findActiveMembership($expert, $hallId);

        throw_if($expertHall === null, ExpertNotAssignedToHallException::forHall($hallId));

        return DB::transaction(fn () => array_map(
            function (array $service) use ($expertHall, $hallId) {
                throw_unless(
                    $this->expertHallRepository->hallOffersService($hallId, $service['service_id']),
                    HallDoesNotOfferServiceException::forService($service['service_id']),
                );

                return $this->expertHallRepository->defineService(
                    $expertHall,
                    $service['service_id'],
                    $service['price'],
                    $service['duration'],
                );
            },
            $dto->services,
        ));
    }
}
