<?php

namespace App\Http\Controllers\Universal;

use phpDocumentor\Reflection\Types\Collection;

class UniversalService
{
    private $universalQuery;
    public function __construct(UniversalQuery $universalQuery)
    {
        $this->universalQuery = $universalQuery;
    }
    public function getGlobalSearchResults($data)
    {
        $formattedCollection = collect();
        $players = $this->universalQuery->getUsersSearchResultsQuery($data, $type = 'PLAYER');
        $clubs = $this->universalQuery->getUsersSearchResultsQuery($data, $type = 'CLUB_OWNER');
        $teams = $this->universalQuery->getTeamsSearchResultsQuery($data);
        $tournaments = $this->universalQuery->getTournamentsSearchResultsQuery($data);
        return $formattedCollection
            ->merge($players)
            ->merge($clubs)
            ->merge($teams)
            ->merge($tournaments);
    }
    public function getRoundName($item)
    {

        if ($item >= 16) {
            return "ROUND OF " . $item;
        } else {
            if ($item == 8) return "QUARTER-FINAL";
            if ($item == 4) return "SEMI-FINAL";
            if ($item == 2) return "FINAL";
            if ($item == 1) return "WINNER";
        }
    }

    public function getRoundInfo($key)
    {
        $data = [
            'ROUND OF 10' => 10,
            'ROUND OF 6' => 6,
            'QUARTER-FINAL' => 8,
            'SEMI-FINAL' => 4,
            'FINAL' => 2,
        ];

        return $data[$key];
    }

    public function getFullFormOfBattingStyle($key)
    {
        $data = [
            'RH' => 'Right Hand',
            'LH' => 'Left Hand',
        ];

        return $data[$key];
    }
}
