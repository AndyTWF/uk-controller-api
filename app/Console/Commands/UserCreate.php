<?php

namespace App\Console\Commands;

use App\Allocator\Stand\Finder\AirfieldStandFinder;
use App\Allocator\Stand\Prioritiser\PotentialStandPrioritiser;
use App\Allocator\Stand\Rule\AirlineStandRule;
use App\Allocator\Stand\Sorter\StandSorter;
use App\Models\User\User;
use App\Models\Vatsim\NetworkAircraft;
use App\Services\UserService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Command to create a new user and generate their personal access token.
 *
 * Class UserCreate
 * @package App\Console\Commands
 */
class UserCreate extends Command
{
    const INVALID_CID_MESSAGE = 'Invalid VATSIM CID provided.';

    protected $signature = 'user:create {vatsim_cid}';

    protected $description = 'Create a user and generate a personal access token';

    /**
     * Handles the command
     * @param UserService $userService Service to do the user work.
     * @throws \App\Exceptions\UserAlreadyExistsException
     */
    public function handle(UserService $userService)
    {
        $generator = new PotentialStandPrioritiser(
            app()->make(AirfieldStandFinder::class),
            app()->make(StandSorter::class),
            new AirlineStandRule()
        );
        $selector = app()->make(\App\Allocator\Stand\Selector\StandSelector::class);
        $stand = $selector->selectStand(NetworkAircraft::find('BAW123'), new AirlineStandRule(), $generator);
        dd($stand->id);

        // Invalid VATSIM CID
        if (!ctype_digit($this->argument('vatsim_cid')) || $this->argument('vatsim_cid') < 800000) {
            throw new InvalidArgumentException(self::INVALID_CID_MESSAGE);
        }

        $userCid = $this->argument('vatsim_cid');
        $userConfig = json_encode(
            $userService->createUser($userCid),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        Storage::disk('local')->put('access/api-settings-' . $userCid . '.txt', $userConfig);
        $this->info('User ' . $userCid . ' successfully created');
    }
}
