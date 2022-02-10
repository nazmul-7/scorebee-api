<?php

namespace App\Http\Controllers\Filter;

use App\Models\Fixture;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FilterQuery
{



    // =========================================== Player-filtering-start =============================

    public function playerFilteringFromFixture($data){
        $id = $data['player_id'] ?? null;
        $status = $data['status'];
        return Fixture::where('match_date', '!=', '1111-11-11')
        ->when($status === "years", function($q) use($id){
            $q->select(DB::raw('YEAR(match_date) year'))
            ->whereHas('playingElevens', function (Builder $query) use($id) {
                $query->where('player_id', $id);
            })
            ->orderByDesc('year')
            ->distinct('year');
        })
        ->when($status === "overs", function($q) use($id){
            $q->select('match_overs')->where('match_overs', '!=', 0)
            ->whereHas('playingElevens', function (Builder $query) use($id) {
                $query->where('player_id', $id);
            })
            ->orderByDesc('match_overs')
            ->groupBy('match_overs');
        })
        ->when($status === "innings", function($q) use($id){
            $q->select('match_type')->whereNotNull('match_type')
            ->whereHas('playingElevens', function (Builder $query) use($id) {
                $query->where('player_id', $id);
            })
            ->groupBy('match_type');
        })
        ->when($status === "balls", function($q) use($id){
            $q->select('ball_type')->whereNotNull('ball_type')
            ->whereHas('playingElevens', function (Builder $query) use($id) {
                $query->where('player_id', $id);
            })
            ->orderByDesc('ball_type')
            ->groupBy('ball_type');
        })
        ->get();
        // ->paginate(20);
    }

    public function playerFilteringFromTournament($data){
        $id = $data['player_id'] ?? null;
        $status = $data['status'];

        return Tournament::when($status === "tournaments", function($q) use($id){
                $q->select('id', 'tournament_name');
                $q->whereHas('fixtures', function (Builder $q) use($id) {
                    $q->where('match_date', '!=', '1111-11-11');
                    $q->whereHas('playingElevens', function (Builder $q2) use($id) {
                        $q2->where('player_id', $id);
                    });
                });
                $q->orderBy('tournament_name');
            })->when($status === "category", function($q) use($id){
                $q->select('tournament_category');
                $q->whereHas('fixtures', function (Builder $q) use($id) {
                    $q->where('match_date', '!=', '1111-11-11');
                    $q->whereHas('playingElevens', function (Builder $q2) use($id) {
                        $q2->where('player_id', $id);
                    });
                });
                $q->groupBy('tournament_category');
                $q->orderBy('tournament_category');
            })

        ->get();
        // ->paginate(20);
    }


    public function playerFilteringFromTeam($data){
        $id = $data['player_id'] ?? null;
        $status = $data['status'];

        return Team::when($status === "teams", function($q) use($id){
                $q->select('id','team_name');
                $q->where('id', '=', function ($q2) use($id) {
                    $q2->select('team_id')->from('playing_elevens')
                    ->where('player_id', $id)->limit(1);
                });
            })

        ->paginate(20);
    }



    // =========================================== Player-filtering-end =============================



    // =========================================== LeaderBoard-filter-start =============================
    public function tournamentYears($data){
        return Tournament::select(DB::raw('YEAR(start_date) year'))
        ->orderByDesc('year')->groupBy('year')->paginate(20);
    }
    // =========================================== LeaderBoard-filter-end ===============================


    // =========================================== LeaderBoard & myMatches -filter-start =============================
    public function filteringFromFixture($data){
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;
        $uid = $data['uid'] ?? null;
        $uType = $data['user_type'] ?? null;
        // return $uType;
        return Fixture::when($type === "leaderboard", function($q){
            $q->where('match_date', '!=', '1111-11-11');
        })
        ->when($type === "my_match" && $uType, function($q) use($uid, $uType){
            $q->where('match_date', '!=', '1111-11-11');
            $q->when($uType === "ORGANIZER", function($q2) use($uid){
                $q2->whereHas('tournament', function (Builder $q) use($uid) {
                    $q->where('organizer_id', $uid);
                });
            });
            $q->when($uType === "CLUB_OWNER", function($q2) use($uid){
                $q2->whereIn('home_team_id', function($query) use($uid){
                    $query->select('id')->from('teams')
                    ->where('owner_id', $uid);
                });
                $q2->orWhereIn('away_team_id', function($query) use($uid){
                    $query->select('id')->from('teams')
                    ->where('owner_id', $uid);
                });
            });
            $q->when($uType === "PLAYER", function($q2) use($uid){
                $q2->whereHas('playingElevens', function (Builder $q3) use($uid) {
                    $q3->where('player_id', $uid);
                });
            });
        })
        ->when($status === "years", function($q){
            $q->select(DB::raw('DISTINCT YEAR(match_date) year'))
            ->orderByDesc('year');
        })
        ->when($status === "overs", function($q){
            $q->select('match_overs')->where('match_overs', '!=', 0)
            ->orderByDesc('match_overs')
            ->groupBy('match_overs');
        })
        ->when($status === "innings", function($q){
            $q->select('match_type')
            ->whereNotNull('match_type')
            ->groupBy('match_type');
        })
        ->when($status === "balls", function($q){
            $q->select('ball_type')
            ->whereNotNull('ball_type')
            ->orderByDesc('ball_type')
            ->groupBy('ball_type');
        })->get();
        // ->paginate(20);
    }

    public function filteringFromTournaments($data){
        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;
        $uid = $data['uid'] ?? null;
        $uType = $data['user_type'] ?? null;

        return Tournament::when($type === "leaderboard",function($q){
                    $q->whereHas('fixtures', function (Builder $q) {
                        $q->where('match_date', '!=', '1111-11-11');
                    });
                })
                ->when($type === "my_match" && $uType, function($q) use($uid, $uType){
                    $q->when($uType === "ORGANIZER", function($q2) use($uid){
                        $q2->where('organizer_id', $uid);
                    });
                    $q->when($uType === "PLAYER", function($q2) use($uid){
                        $q2->whereHas('fixtures', function (Builder $q) use($uid) {
                            $q->whereHas('playingElevens', function (Builder $q2) use($uid) {
                                $q2->where('player_id', $uid);
                            });
                        });
                    });
                    $q->when($uType === "CLUB_OWNER", function($q2) use($uid){
                        $q2->whereHas('tournament_team', function (Builder $q) use($uid) {
                            $q->where('status', '=', 'ACCEPTED');
                            $q->whereHas('team', function (Builder $q2) use($uid) {
                                $q2->where('owner_id', $uid);
                            });
                        });
                    });
                })
                ->when($status === "tournaments", function($q){
                    $q->select('id', 'tournament_name');
                    $q->orderBy('tournament_name');
                })
                ->when($status === "category", function($q){
                    $q->select('tournament_category');
                    $q->distinct('tournament_category');
                    $q->orderBy('tournament_category');
                })

              ->paginate(20);
    }

    public function filteringFromTeams($data){

        $status = $data['status'] ?? null;
        $type = $data['type'] ?? null;
        $uid = $data['uid'] ?? null;
        $uType = $data['user_type'] ?? null;

        return Team::when($status === "teams", function($q){
                $q->select('id','team_name');
            })
            ->when($type === "my_match" && $uType, function($q) use($uid, $uType){
                $q->when($uType === "ORGANIZER", function($q2) use($uid){
                    $q2->whereHas('TournamentTeam', function (Builder $q2) use($uid) {
                        $q2->where('tournament_owner_id', $uid);
                    });
                });
                $q->when($uType === "PLAYER", function($q2) use($uid){
                    $q2->whereIn('id', function($q) use($uid){
                        $q->select('team_id')->from('playing_elevens')
                        ->where('is_played', 1)->where('player_id', $uid);
                    });
                });
                $q->when($uType === "CLUB_OWNER", function($q2) use($uid){
                    $q2->where('owner_id', $uid);
                    $q2->whereHas('tournament_team', function (Builder $q) {
                        $q->where('status', '=', 'ACCEPTED');
                    });
                });
            })
            ->paginate(20);
    }

    // =========================================== LeaderBoard-filter-end ===============================


}
