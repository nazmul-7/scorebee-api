<?php

namespace App\Http\Controllers\Player;

use App\Http\Controllers\Club\ClubQuery;
use App\Http\Controllers\Team\TeamQuery;
// use App\Http\Controllers\Notification\NotificationQuery;
use App\Http\Controllers\Notification\NotificationService;
use App\Http\Controllers\Universal\UniversalService;
use Intervention\Image\Facades\Image;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Log;

class PlayerService
{
    private $playerQuery;
    private $clubQuery;
    private $notificationQuery;
    private $universalService;

    public function __construct(PlayerQuery $playerQuery, ClubQuery $clubQuery,NotificationService $notificationQuery, UniversalService $universalService)
    {
        $this->playerQuery = $playerQuery;
        $this->clubQuery = $clubQuery;
        $this->notificationQuery = $notificationQuery;
        $this->universalService = $universalService;
    }

    public function forgotPassword($data){
        $email = $data['email'];
        $user = $this->playerQuery->singleUser('email', $email);
        $random_number = random_int(100000, 999999);
        if($user){
            $code = $random_number;
            $emailObj= [
                "fullName" => $user->first_name .' '. $user->last_name,
                "code" => $code,
                "avatar" => $user->profile_pic
            ];

            $ob =[
                "forgot_code" => $code,
            ];

            Mail::send('emails.forgotPassword', $emailObj, function ($msg) use($email)
            {
                $msg->subject('Please confirm your email address');
                $msg->from(env('MAIL_FROM_ADDRESS'), 'Scorebee');
                $msg->to($email);
            });

            $mail = $this->playerQuery->updateUser('id', $user->id, $ob);
            if($mail){
                return response()->json(['message' => 'Verification code sent successfully!'], 200);
            }
        }

    }

    public function verifyEmail($data){
        $user = $this->playerQuery->checkVerifyCode($data);
        if($user && $user->forgot_code == $data['verify_code']){
            return response()->json(['message' => 'Succeed! Enter to a new password.'], 200);
        }
        return response()->json(['message' => 'Invalid verification code!'], 401);
    }

    public function resetPassword($data){
        $user = $this->playerQuery->singleUser('email', $data['email']);
        if($user){
            $ob =[
                "password" => Hash::make($data['password']),
                "forgot_code" => 0,
            ];
            $changePass = $this->playerQuery->updateUser('id', $user->id, $ob);
            if($changePass){
                return response()->json(['message' => 'Password changed successfully!'], 200);
            }
        }
        return response()->json(['message' => 'Invalid Creadentials!'], 401);
    }



    public function updateUserInfo($data)
    {
        $uid = Auth::id();
        $check = $this->playerQuery->singlePlayerDetailsQuery($uid);
        $baseURL = env('APP_URL');
        if (isset($data['profile_pic']) && $data['profile_pic']) {

            $bannerPath = str_replace($baseURL, '', $check->profile_pic);
            if ($check->profile_pic && file_exists($bannerPath) && $check->profile_pic == 'avatar.png') {
                unlink(public_path($bannerPath));
            }
            $data['profile_pic'] = $this->uploadImage('profile_pic', $data['profile_pic']);
        }

        if (isset($data['cover']) && $data['cover']) {
            $bannerPath = str_replace($baseURL, '', $check->cover);
            if ($check->cover && file_exists($bannerPath)) {
                unlink(public_path($bannerPath));
            }
            $data['cover'] = $this->uploadImage('cover', $data['cover']);
        }

        if (isset($data['nid_pic']) && $data['nid_pic']) {
            $bannerPath = str_replace($baseURL, '', $check->nid_pic);
            if ($check->nid_pic && file_exists($bannerPath)) {
                unlink(public_path($bannerPath));
            }
            $data['nid_pic'] = $this->uploadImage('nid_pic', $data['nid_pic']);
        }

        return $this->playerQuery->updateUserInfoQuery($uid, $data);

    }

    public function uploadImage($imgName, $imgFile): string
    {
        $baseURL = env('APP_URL');
        $imgName = $imgName . '_' . hexdec(uniqid()) . '.webp';
        Image::make($imgFile)->save('uploads/' . $imgName);
        return $baseURL . 'uploads/' . $imgName;
    }

    public function playerBattingStats($id)
    {
        $batter = $this->playerQuery->playerBattingStatsQuery($id);

        $batter->be_out_in_limited = $batter['be_out_in_limited'];
        $batter->be_out_in_test = $batter['be_out_in_test'];
        $batter->limited_match_run = $batter['limited_match_run'];
        $batter->test_match_run = $batter['test_match_run'];
        $batter->faced_limited_ball = $batter['faced_limited_ball'];
        $batter->faced_test_ball = $batter['faced_test_ball'];

        $batter['avg_in_limited'] = $batter->be_out_in_limited ? round($batter->limited_match_run/$batter->be_out_in_limited, 2) : 0;

        $batter['avg_in_test'] = $batter->be_out_in_test ? round($batter->test_match_run/$batter->be_out_in_test, 2) : 0;

        $batter['strike_rate_in_limited'] = $batter->limited_match_run ? floor(($batter->limited_match_run / $batter->faced_limited_ball) * 100) : 0;
        $batter['strike_rate_in_test'] = $batter->test_match_run ? floor(($batter->test_match_run / $batter->faced_test_ball) * 100) : 0;
        unset($batter->be_out_in_limited, $batter->be_out_in_test);
        return $batter;
    }

    public function oversFormat($overs, $balls)
    {
        return $overs + ($balls ? floor($balls / 6) . '.' . ($balls % 6):0);
    }

    public function oversFormatForEconomy($overs, $balls)
    {
        $total_overs = $overs + ($balls ? floor($balls / 6) : 0);
        $balls = ($balls ? floor($balls) % 6 : 0);
        $balls = ($balls ? round( ($balls/6), 2) : 0);

        return $total_overs + $balls;
    }

    public function playerBowlingStats($data)
    {

        $bowler = $this->playerQuery->playerBowlingStatsQuery($data);

        $bowler["overs_bowled_in_test"] = $bowler["overs_bowled_in_test_only"] || $bowler["overs_extra_balls_in_test"] ? $this->oversFormat($bowler["overs_bowled_in_test_only"], floor($bowler["overs_extra_balls_in_test"])) : null;
        $bowler["overs_bowled_in_limited"] = $bowler["overs_bowled_in_limited_only"] || $bowler["overs_extra_balls_in_limited"] ? $this->oversFormat($bowler["overs_bowled_in_limited_only"], $bowler["overs_extra_balls_in_limited"]) : null;

        $bowler["economy_in_limited"] = $bowler["overs_bowled_in_limited_only"] || $bowler["overs_extra_balls_in_limited"] ? number_format($bowler["run_gave_in_limited_match"] / $this->oversFormatForEconomy($bowler["overs_bowled_in_limited_only"], $bowler["overs_extra_balls_in_limited"]), 2, ".", "") : null;

        $bowler["economy_in_test"] = $bowler["overs_bowled_in_test_only"] || $bowler["overs_extra_balls_in_test"] ? number_format($bowler["run_gave_in_test"] / $this->oversFormatForEconomy($bowler["overs_bowled_in_test_only"], $bowler["overs_extra_balls_in_test"]), 2, ".", "") : null;

        $bowler["strike_rate_in_limited"] = $bowler["total_deliveries_in_limited"] && $bowler["wicket_in_limited_match"] ? number_format($bowler["total_deliveries_in_limited"] / $bowler["wicket_in_limited_match"], 2, ".", "") : null;
        $bowler["strike_rate_in_test"] = $bowler["total_deliveries_in_test"] && $bowler["wicket_in_test"] ? number_format($bowler["total_deliveries_in_test"] / $bowler["wicket_in_test"], 2, ".", "") : null;
        $bowler["best_in_limited"] = $bowler["highest_wicket_in_limited"] ? $bowler["highest_wicket_in_limited"] . '/' . $bowler["highest_run_in_limited"] : null;
        $bowler["best_in_test_match"] = $bowler["highest_wicket_in_test"] ? $bowler["highest_wicket_in_test"] . '/' . $bowler["highest_run_in_test"] : null;

        unset($bowler["overs_bowled_in_test_only"]);
        unset($bowler["overs_extra_balls_in_test"]);
        unset($bowler["overs_bowled_in_limited_only"]);
        unset($bowler["overs_extra_balls_in_limited"]);
        unset($bowler["highest_wicket_in_limited"], $bowler["highest_run_in_limited"], $bowler["highest_wicket_in_test"], $bowler["highest_run_in_test"]);
        return $bowler;
    }

    public function awardInMatches($data){
        $player = $this->playerQuery->awardInMatches($data);
        // return $player;
        $arr = array();
        foreach($player as $p){
            $ob = [];
            if($p->bestPlayerBatting){
                $ob['batting'] =[
                    "runs" => $p->bestPlayerBatting->runs_achieved . '('.$p->bestPlayerBatting->balls_faced .')',
                    "fours" =>$p->bestPlayerBatting->fours . '(4s)',
                    "sixes" =>$p->bestPlayerBatting->sixes . '(6s)',
                    "strike_rate" => $p->bestPlayerBatting && $p->bestPlayerBatting->balls_faced ? round(($p->bestPlayerBatting->runs_achieved / $p->bestPlayerBatting->balls_faced) * 100, 2).' SR' :0,
                ];
            }

            $overs = floor($p->bestPlayerBowling->overs_bowled);
            $balls = ($p->bestPlayerBowling->overs_bowled - $overs)*10;

            if($p->bestPlayerBowling){
                $ob["bowling"] = [
                    "overs" => $p->bestPlayerBowling->overs_bowled .' (Ov)' ,
                    "maidens" => $p->bestPlayerBowling->maiden_overs .' (M)' ,
                    "runs" => $p->bestPlayerBowling->runs_gave .' (R)' ,
                    "wickets" => $p->bestPlayerBowling->wickets .' (W)' ,
                    "economy" => $overs || $balls ? round($p->bestPlayerBowling->runs_gave/$this->oversFormatForEconomy( $overs, $balls), 2) :0 ,
                ];
            }

            $ob['total_likes'] = $p->total_likes;
            $ob['home_team'] = $p->home_team;
            $ob['away_team'] = $p->away_team;

            $ob['tournament_id'] = $p->tournament;
            $ob['date'] = date("j M, Y", strtotime($p->match_date));
            array_push($arr, $ob);
        }
        return $arr;
    }

    public function awardInTournaments($data){
        $player = $this->playerQuery->awardInTournaments($data);
        // return $player;
        $arr = array();
        foreach($player as $p){
            $ob = [];
            $ob['batter']=[
                'innings' => $p->total_innings_as_batter .' Inn',
                'runs' => $p->total_runs_as_batter . ' R',
                'max' => $p->max_as_batter. ' HS',
                'fours' => $p->total_fours_as_batter. ' (4s)',
                'sixes' => $p->total_sixes_as_batter . ' (6s)',
                'strike_rate' => $p->total_balls_as_batter ? round(($p->total_runs_as_batter/ $p->total_balls_as_batter)*100, 2) . ' SR' :0,
                'avg' => $p->total_outs_as_batter ? round($p->total_runs_as_batter  / $p->total_outs_as_batter, 2) . ' AV': 0,
            ];

            $econOvers = $this->oversFormatForEconomy($p->total_overs_as_bowler, $p->total_overs_extras_as_bowler);
            $ob['bowler'] = [
                'innings' => $p->total_innings_as_bowler .' Inn',
                'overs' => $this->oversFormat($p->total_overs_as_bowler, $p->total_overs_extras_as_bowler) . ' Ov',
                'maindens' => $p->total_maidens_as_bowler. ' M',
                'HW' => $p->highest_as_bowler. ' HW',
                'wickets' => $p->total_wickets_as_bowler. ' W',
                'economy' => $p->total_overs_as_bowler || $p->total_overs_extras_as_bowler ? round($p->total_runs_as_bowler / $econOvers, 2). ' E' :0,
            ];

            if($p->bestPlayerBattings->count() > 0){
                $ob['team_name'] = $p->bestPlayerBattings[0]->team->team_name ;
                $ob['team_short_name'] = $p->bestPlayerBattings[0]->team->team_short_name ;
            }
            if($p->bestPlayerBowlings->count() > 0){
                $ob['team_name'] = $p->bestPlayerBowlings[0]->team->team_name ;
                $ob['team_short_name'] = $p->bestPlayerBattings[0]->team->team_short_name ;
            }
            $ob['total_likes'] = $p->total_likes;
            $ob['tournament_id'] = $p->id;
            $ob['tournament_name'] = $p->tournament_name;
            $ob['date'] = date("j M, Y", strtotime($p->start_date)).' to '.date("j M, Y", strtotime($p->end_date));

            array_push($arr, $ob);
        }
        return $arr;
    }

    public function awardsLike($data){
        $uid = Auth::id();
        $data['user_id'] = $uid;
        $fId = isset($data['fixture_id']) ?$data['fixture_id'] :0;
        $tId = isset($data['tournament_id']) ?$data['tournament_id'] :0;
        $check = $this->playerQuery->checkAward($data);

        if(isset($check->fixture_like) && !$check->fixture_like){
            return $this->playerQuery->awardsLike($data);
        }

       else if(isset($check->tournament_like) && !$check->tournament_like){
            return $this->playerQuery->awardsLike($data);
        }
        else{
            return $this->playerQuery->deleteAwardLike($fId, $tId, $uid);
        }

    }

    public function playerFieldingStats($id)
    {
        return $this->playerQuery->playerFieldingStatsQuery($id);
    }

    public function playerCaptainStats($data)
    {
        return $this->playerQuery->playerCaptainStatsQuery($data);
    }

    public function singlePlayerDetails($id)
    {
        $user = $this->playerQuery->singlePlayerDetailsQuery($id);
        if ($user && $user->social_accounts) {
            $user->social_accounts = json_decode($user->social_accounts);
        }

        return $user;
    }

    //    Club to player requests start
    public function getClubRequestsList($data)
    {
        $data['player_id'] = Auth::id();
        $data['status'] = 'PENDING';

        return $this->playerQuery->getClubRequestsListQuery($data);
    }

    public function sentClubRequest($data): bool
    {
        $playerId = Auth::id();
        $isValidPlayer = $this->clubQuery->checkValidPlayerQuery($playerId);
        $isValidClubOwner = $this->clubQuery->checkValidClubOwnerQuery($data['club_owner_id']);
        $isRequestExists = $this->clubQuery->checkRequestIsValidQuery($data['club_owner_id'], $playerId);

        if ($isValidClubOwner and $isValidPlayer and !$isRequestExists) {
            $this->clubQuery->sentPlayerRequestQuery(array_merge($data, [
                'player_id' => $playerId,
                'requested_by' => 'PLAYER',
                'status' => 'PENDING'
            ]));

            $ob=[
                'from' => $playerId,
                'to' =>$data['club_owner_id'],
                'msg'=>$isValidPlayer['first_name'].' '.$isValidPlayer['last_name'].' send a member request.',
                'type'=>'player_to_club_join_request',
                'club_id'=>$data['club_owner_id'],
            ];

            $this->notificationQuery->sendNotificationGlobalMethod($ob);
            return true;
        }
        return false;
    }

    public function acceptClubRequest($data): bool
    {
        $playerId = Auth::id();
        $isValidPlayer = $this->clubQuery->checkValidPlayerQuery($playerId);
        $isValidClubOwner = $this->clubQuery->checkValidClubOwnerQuery($data['club_owner_id']);
        $isRequestExists = $this->clubQuery->checkRequestIsValidQuery($data['club_owner_id'], $playerId, $status = 'PENDING', $requestedBy = 'CLUB');

        if ($isValidClubOwner && $isRequestExists) {
            $ob=[
                'from' => $playerId,
                'to' =>$data['club_owner_id'],
                'msg'=>$isValidPlayer['first_name'].' '.$isValidPlayer['last_name'].' has accepted your club join request.',
                'type'=>'player_to_club_join_accept',
                'club_id'=>$data['club_owner_id'],
            ];

            $this->notificationQuery->sendNotificationGlobalMethod($ob);
            return $this->clubQuery->updatePlayerRequestQuery($data['club_owner_id'], $playerId, $attributes = ['status' => 'ACCEPTED']);


        }

        return false;
    }

    public function removeClubRequest($data): bool
    {
        $playerId = Auth::id();
        $isValidPlayer = $this->clubQuery->checkValidPlayerQuery($playerId);
        $isClubOwner = $this->clubQuery->checkValidClubOwnerQuery($data['club_owner_id']);
        $isRequestExists = $this->clubQuery->checkRequestIsValidQuery($data['club_owner_id'], $playerId, $data['status']);

        if ($isClubOwner && $isRequestExists) {
            $ob=[
                'from' => $playerId,
                'to' =>$data['club_owner_id'],
                'club_id'=>$data['club_owner_id'],
                'msg' => $isValidPlayer['first_name'].' '.$isValidPlayer['last_name'].' has canceled your club join request.',
                'type' => 'player_to_club_join_cancel',
            ];

            if($data['status'] == 'ACCEPTED'){
                $ob['msg'] = $isValidPlayer['first_name'].' '.$isValidPlayer['last_name'].' has left your club.';
                $ob['type'] = 'player_to_club_leave_msg';
                $this->teamQuery->resetTeamCaptainOrWicketKeeper('owner_id', $data['club_owner_id'], 'captain_id', $playerId);
                $this->teamQuery->resetTeamCaptainOrWicketKeeper('owner_id', $data['club_owner_id'], 'wicket_keeper_id', $playerId);
            }

            $this->notificationQuery->sendNotificationGlobalMethod($ob);
            return $this->clubQuery->removePlayerRequestQuery($data['club_owner_id'], $playerId);
        }

        return false;
    }
    //    Club to player requests end

    //players-leaderboard-start
    public function highestBattingByRun($data)
    {
        $user = $this->playerQuery->highestBattingByRun($data);
        if ($user) {
            foreach ($user as $u) {
                $u->player_average = $u->player_average ? round($u->player_average, 2) : null;
                $u->strike_rate = $u->player_runs && $u->total_balls_faced ? round($u->player_runs / $u->total_balls_faced, 2) : null;
            }
        }
        return $user;
    }

    public function highestBowlingByRun($data)
    {
        $player = $this->playerQuery->highestBowlingByRun($data);

            foreach ($player as $p) {
                $p->overs_bowled = $this->oversFormat($p->overs_bowled, floor($p->overs_extra_balls));
                $p->bowler_economy = $this->oversFormatForEconomy($p->overs_bowled, floor($p->overs_extra_balls));
                unset($p->overs_extra_balls);
            }

        return $player;
    }

    public function highestFieldingByDismissal($data)
    {
        return $this->playerQuery->highestFieldingByDismissal($data);
    }
    //players-leaderboard-End


    //Player-batting-insights-end
    public function playerCurrentFormAndInnings($data)
    {
        $user = $this->playerQuery->playerCurrentForm($data);
        // return $user;
        $obj = [];
        $obj['total_runs'] = 0;
        $obj['LBW'] = 0;
        $obj['average'] = 0;
        $obj['total_fours'] = 0;
        $obj['total_sixes'] = 0;
        $obj['balls_faced'] = 0;

        $player = array();


        if ($user) {
            $wickets = collect();
            foreach ($user as $u) {
                $current = [];
                $current['date'] = $u->fixture && $u->fixture->match_date ? Carbon::parse($u->fixture->match_date)->format('d/m/y') : null;
                $current['teams'] = $u->fixture && $u->fixture->home_team && $u->fixture->away_team ? strtoupper($u->fixture->home_team->team_short_name) . ' vs ' . strtoupper($u->fixture->away_team->team_short_name) : null;
                $current['score'] = $u->fixture && $u->innings_batter ? $u->innings_batter->runs_achieved . '(' . $u->innings_batter->balls_faced . ')' : null;
                $current['out'] = $u->innings_batter ? ucfirst(str_replace("_", " ", $u->innings_batter->wicket_type)) : null;
                $current['total_overs'] = $u->innings_batter ? (string)$u->innings_batter->overs_faced : null;
                array_push($player, $current);
                if ($u->innings_batter) {
                    $obj['total_runs'] = $obj['total_runs'] + $u->innings_batter->runs_achieved;
                    $obj['total_fours'] = $obj['total_fours'] + $u->innings_batter->fours;
                    $obj['total_sixes'] = $obj['total_sixes'] + $u->innings_batter->sixes;
                    $obj['balls_faced'] = $obj['balls_faced'] + $u->innings_batter->balls_faced;
                    $wickets->push($u['innings_batter']['wicket_type']);
                }
            }

            $obj['max_wicket_type'] = '';
            $obj['max_wicket_value'] = 0;
            if($wickets->count() > 0){
                $wickets = $wickets->countBy()->sort();
                $wicketType = $wickets->keys()->last();

                if($wicketType == 'CAUGHT_BOWLED'){
                    $wicketType = 'Caught and Bowled';
                } else if($wicketType == 'LBW'){
                    $wicketType = 'LBW';
                } else {
                    $wicketType = ucwords(strtolower(str_replace('_', '', $wicketType)));
                }

                $obj['max_wicket_type'] = $wicketType;
                $obj['max_wicket_value'] = $wickets->values()->last();
            }
        }

        $obj['average'] = $obj['total_runs'] && $user->count() > 0 ? number_format($obj['total_runs'] / $user->count(), 2, ".", "") : null;
        $obj['strike_rate'] = $obj['total_runs'] && $obj['balls_faced'] ? round(($obj['total_runs'] / $obj['balls_faced']) * 100, 2) : null;
        unset($obj['balls_faced']);
        return ["current_form" => $player, "last_five_innings" => (object)$obj];
    }

    public function battingWagon($data){
        $id = $data['player_id'] ?? null ;
        $type = isset($data['status']) ? $data['status'] : '';
        $batter = $this->playerQuery->battingWagon($data);

        $batterTotals = $this->playerQuery->batterTotals($data);

        $count = $batter->groupBy('deep_position');
        $arr =array();
        foreach($count as $key => $c){
            $ob =[
                "postion" => $key,
                "percent" => $batterTotals ? floor(($c->count()/$batterTotals)*100) :0,
            ];
            array_push($arr, $ob);
        }
        $formatePosition = array();

                //Formating status
        foreach($batter as $b){
            $status = "";
            if($b->wicket_type && ($type == 'OUT' || $type == 'ALL')){
                $status = "OUT";
            }
            else if($b->boundary_type == "SIX"){
                $status = "SIX";
            }
            else if($b->boundary_type == "FOUR"){
                $status = "FOUR";
            }
            else if($b->runs == 1){
                $status = "ONE";
            }
            else if($b->runs == 2){
                $status = "TWO";
            }
            else if($b->runs == 3){
                $status = "THREE";
            }
            else if($b->ball_type == "LEGAL" && $b->runs == 0 && $b->extras == 0){
                $status = "DOT";
            }
            $ob = [
                "id" => $b->id,
                "shot_position" => $b->shot_position,
                "shot_x" => $b->shot_x,
                "shot_y" => $b->shot_y,
                "status" => $status
            ];

            array_push($formatePosition, $ob);
        }

        return ["percentage" =>$arr, "batter" => $formatePosition];
    }

    public function testingWagon($data){

        $batter = $this->playerQuery->testingWagon($data);
        // return $batter;
        $batterTotals = $this->playerQuery->batterTotals($data);

        $count = $batter->groupBy('position');
        $arr =array();
        foreach($count as $key => $c){
            $ob =[
                "postion" => $key,
                "percent" => $batterTotals ? floor(($c->count()/$batterTotals)*100) :0,
            ];
            array_push($arr, $ob);
        }
        return ["percentage" =>$arr, "batter" => $batter];
    }

    public function bowlingWagon($data){

        $id = $data['player_id'] ?? null;
        $bowler = $this->playerQuery->bowlingWagon($data);

        $bowlerTotals = $this->playerQuery->bowlingTotals($data);

        $count = $bowler->groupBy('deep_position');
        $arr =array();
        foreach($count as $key => $c){
            $ob =[
                "postion" => $key,
                "percent" => floor(($c->count()/$bowlerTotals)*100),
            ];
            array_push($arr, $ob);
        }
        $formatePosition = array();

        //Formating status
        foreach($bowler as $b){
            $status = "";
            if($b->wicket_type && $b->wicket_by == $id and ($data['status'] == 'OUT' || $data['status'] == 'ALL')){
                $status = "OUT";
            }
            else if($b->boundary_type == "SIX"){
                $status = "SIX";
            }
            else if($b->boundary_type == "FOUR"){
                $status = "FOUR";
            }
            else if($b->runs == 1){
                $status = "ONE";
            }
            else if($b->runs == 2){
                $status = "TWO";
            }
            else if($b->runs == 3){
                $status = "THREE";
            }
            else if($b->ball_type == "LEGAL" && $b->runs == 0 && $b->extras == 0){
                $status = "DOT";
            }
            $ob = [
                "shot_position" => $b->shot_position,
                "shot_x" => $b->shot_x,
                "shot_y" => $b->shot_y,
                "status" => $status
            ];

            array_push($formatePosition, $ob);
        }

        return ["percentage" =>$arr, "batter" => $formatePosition];
    }


    public function faceOffWagon($data)
    {
        $type = $data['status'] ?? null;
        $deliveries = $this->playerQuery->faceOffWagon($data);
        $playerTotals = $this->playerQuery->faceOffTotals($data);
        $count = $deliveries->groupBy('deep_position');
        $arr =array();
        foreach($count as $key => $c){
            $ob =[
                "postion" => $key,
                "percent" => floor(($c->count()/$playerTotals)*100),
            ];
            array_push($arr, $ob);
        }

        $formatePosition = array();

        foreach($deliveries as $b){
            $status = "";
            if($b->wicket_type && ($type == "OUT" || $type == "ALL")){
                $status = "OUT";
            }
            else if($b->boundary_type == "SIX"){
                $status = "SIX";
            }
            else if($b->boundary_type == "FOUR"){
                $status = "FOUR";
            }
            else if($b->runs == 1){
                $status = "ONE";
            }
            else if($b->runs == 2){
                $status = "TWO";
            }
            else if($b->runs == 3){
                $status = "THREE";
            }
            else if($b->ball_type == "LEGAL" && $b->runs == 0 && $b->extras == 0){
                $status = "DOT";
            }
            $ob = [
                "id" =>$b->id,
                "shot_position" => $b->shot_position,
                "shot_x" => $b->shot_x,
                "shot_y" => $b->shot_y,
                "status" => $status
            ];

            array_push($formatePosition, $ob);
        }

        return ["percentage" =>$arr, "batter" => $formatePosition];
    }

    public function playerCurrentBowlingFormAndInnings($data)
    {
        $user = $this->playerQuery->playerCurrentBowlingFormAndInnings($data);
        // return $user;
        $obj = [];
        $obj['total_wickets'] = 0;
        $obj['LBW'] = 0;
        $obj['average'] = 0;
        $obj['total_fours'] = 0;
        $obj['total_sixes'] = 0;
        $obj['RHB'] = 0;
        $obj['LHB'] = 0;

        $player = array();

        if ($user) {
            $wickets = collect();

            foreach ($user as $u) {
                $current = [];
                $current['date'] = ($u->fixture and $u->fixture->match_date) ? Carbon::parse($u->fixture->match_date)->format('d/m/y') : null;
                $current['teams'] = $u->fixture && $u->fixture->home_team && $u->fixture->away_team ?
                    strtoupper($u->fixture->home_team->team_short_name) . ' vs ' . strtoupper($u->fixture->away_team->team_short_name)
                    : 'null';

                $current['score'] = $u->innings_bowler ?
                    $u->innings_bowler->overs_bowled
                    . '-' . $u->innings_bowler->maiden_overs
                    . '-' . $u->innings_bowler->runs_gave
                    . '-' . $u->innings_bowler->wickets
                    : null;
                $current['total_overs'] = $u->innings_bowler ? $u->innings_bowler->overs_bowled : 0;
                array_push($player, $current);
                if ($u->innings_bowler) {
                    $obj['total_wickets'] = $obj['total_wickets'] + $u->innings_bowler->wickets;
                    // $obj['total_runs'] = $obj['total_runs'] + $u->innings_bowler->runs_achieved;

                    $obj['LBW'] = $obj['LBW'] + $u->innings_bowler->LBW;

                    $obj['total_fours'] = $obj['total_fours'] + $u->innings_bowler->fours;
                    $obj['total_sixes'] = $obj['total_sixes'] + $u->innings_bowler->sixes;
                    $obj['RHB'] = $obj['RHB'] + $u->innings_bowler->right_hand_wickets;
                    $obj['LHB'] = $obj['LHB'] + $u->innings_bowler->left_hand_wickets;

                    if($u['innings_bowler']['deliveries']){
                        $fetchedWickets = $u->innings_bowler->deliveries->pluck('wicket_type');
                        $wickets = $wickets->merge($fetchedWickets);
                    }
                }
            }

            if($wickets->count() > 0){
                $wickets = $wickets->countBy()->sort();
                $wicketType = $wickets->keys()->last();

                if($wicketType == 'CAUGHT_BOWLED'){
                    $wicketType = 'Caught and Bowled';
                } else if($wicketType == 'LBW'){
                    $wicketType = 'LBW';
                } else {
                    $wicketType = ucwords(strtolower(str_replace('_', '', $wicketType)));
                }

                $obj['max_wicket_type'] = $wicketType;
                $obj['max_wicket_value'] = $wickets->values()->last();
            }

        }
        // $obj['average'] = $obj['total_runs'] && $user->count() >1 ? $obj['total_runs']/ $user->count():null;

        return ["current_form" => $player, "last_five_innings" => (object)$obj];
    }

    public function battingsAvgByPosition($data){
        $player =  $this->playerQuery->battingsAvgByPosition($data);
        $Avg = [];
        $Avg['RAF'] = $player->total_wickets_against_RAF ? round($player->total_runs_against_RAF/$player->total_wickets_against_RAF, 2) :0;
        $Avg['RAM'] = $player->total_wickets_against_RAM ? round($player->total_runs_against_RAM/$player->total_wickets_against_RAM, 2) :0;
        $Avg['LAF'] = $player->total_wickets_against_LAF ? round($player->total_runs_against_LAF/$player->total_wickets_against_LAF, 2) :0;
        $Avg['LAM'] = $player->total_wickets_against_LAM ? round($player->total_runs_against_LAM/$player->total_wickets_against_LAM, 2) :0;
        $Avg['SLAO'] = $player->total_wickets_against_SLAO ? round($player->total_runs_against_SLAO/$player->total_wickets_against_SLAO, 2) :0;
        $Avg['SLAC'] = $player->total_wickets_against_SLAC ? round($player->total_runs_against_SLAC/$player->total_wickets_against_SLAC, 2) :0;
        $Avg['RAOB'] = $player->total_wickets_against_RAOB ? round($player->total_runs_against_RAOB/$player->total_wickets_against_RAOB, 2) :0;
        $Avg['RALB'] = $player->total_wickets_against_RALB ? round($player->total_runs_against_RALB/$player->total_wickets_against_RALB, 2) :0;
        $Avg['OTHERS'] = $player->total_wickets_against_OTHERS ? round($player->total_runs_against_OTHERS/$player->total_wickets_against_OTHERS, 2) :0;

        $SR =[];
        $SR['RAF'] = $player->total_balls_against_RAF ? round(($player->total_runs_against_RAF/$player->total_balls_against_RAF)*100, 2) :0;
        $SR['RAM'] = $player->total_balls_against_RAM ? round(($player->total_runs_against_RAM/$player->total_balls_against_RAM)*100, 2) :0;
        $SR['LAF'] = $player->total_balls_against_LAF ? round(($player->total_runs_against_LAF/$player->total_balls_against_LAF)*100, 2) :0;
        $SR['LAM'] = $player->total_balls_against_LAM ? round(($player->total_runs_against_LAM/$player->total_balls_against_LAM)*100, 2) :0;
        $SR['SLAO'] = $player->total_balls_against_SLAO ? round(($player->total_runs_against_SLAO/$player->total_balls_against_SLAO)*100, 2) :0;
        $SR['SLAC'] = $player->total_balls_against_SLAC ? round(($player->total_runs_against_SLAC/$player->total_balls_against_SLAC)*100, 2) :0;
        $SR['RAOB'] = $player->total_balls_against_RAOB ? round(($player->total_runs_against_RAOB/$player->total_balls_against_RAOB)*100, 2) :0;
        $SR['RALB'] = $player->total_balls_against_RALB ? round(($player->total_runs_against_RALB/$player->total_balls_against_RALB)*100, 2) :0;
        $SR['OTHERS'] = $player->total_balls_against_OTHERS ? round(($player->total_runs_against_OTHERS/$player->total_balls_against_OTHERS)*100, 2) :0;

        return ["average" =>$Avg, "SR" => $SR];
    }

    public function playerBowlingOverallStats($data)
    {
        $bowler = $this->playerQuery->playerBowlingOverallStats($data);
        // return $bowler;
        $bowler->total_maidens = $bowler->total_maidens ? (string)$bowler->total_maidens : null;
        $bowler->total_overs = $bowler->total_overs ? (string)$bowler->total_overs : null;
        $bowler->total_matches = $bowler->total_matches ? (string)$bowler->total_matches : null;
        $bowler->total_innings = $bowler->total_innings ? (string)$bowler->total_innings : null;
        $bowler->total_dots = $bowler->total_dots ? (string)$bowler->total_dots : null;
        $bowler->total_caughts = $bowler->total_caughts ? (string)$bowler->total_caughts : null;
        $bowler->total_sixes = $bowler->total_sixes ? (string)$bowler->total_sixes : null;
        $bowler->total_fours = $bowler->total_fours ? (string)$bowler->total_fours : null;
        $bowler->total_three_wickets = $bowler->total_three_wickets ? (string)$bowler->total_three_wickets : null;
        $bowler->total_five_wickets = $bowler->total_five_wickets ? (string)$bowler->total_five_wickets : null;
        $bowler->total_overs = $bowler->total_match_overs || $bowler->total_overs_extras_balls ? (int)$this->oversFormat($bowler->total_match_overs, $bowler->total_overs_extras_balls) : 0;

        $bowler->economy_rate = $bowler->total_runs && $bowler->total_overs ? number_format($bowler->total_runs / $this->oversFormatForEconomy($bowler->total_match_overs, $bowler->total_overs_extras_balls), 2, ".", "") : null;
        $bowler->strike_rate = $bowler->total_deliveries && $bowler->total_wickets ? number_format($bowler->total_deliveries / $bowler->total_wickets, 2, ".", "") : null;
        $bowler->best_score = $bowler->inningsBowler && isset($bowler->inningsBowler[0]) ? $bowler->inningsBowler[0]->wickets . '/' . $bowler->inningsBowler[0]->runs_gave : null;
        $bowler->average = $bowler->total_runs && $bowler->total_wickets ? number_format($bowler->total_runs / $bowler->total_wickets, 2, ".", "") : null;

        unset($bowler->inningsBowler, $bowler->total_match_overs, $bowler->total_overs_extras_balls);
        return $bowler;
    }

    public function battingComparison($data)
    {
        $player = $this->playerQuery->playerBattingComparison($data);
        $player->avg_score = $player->total_runs ? floor($player->total_runs/$player->total_matches_out) : 0;
        $player->highest_score = (int)$player->highest_score;
        $player->total_runs = (int)$player->total_runs;
        $player->strike_rate = $player->total_runs ? floor(($player->total_runs / $player->balls_faced) * 100) : 0;
        unset($player->balls_faced,$player->total_matches_out);
        return $player;
    }

    public function playerBattingComparison($data)
    {
        $player = $this->battingComparison($data);

        if (isset($data['compare_id']) && $data['compare_id']) {
            $obj = [
                "player_id" => $data['compare_id'],
            ];
            $compare = $this->battingComparison($obj);
        } else {
            $compare = [];
        }
        return ["player" => $player, "compare" => (object)$compare];
    }

    public function compareBattingWagon($data)
    {
        $status = $data['status'] ?? null;
        $player = $this->battingWagon($data);

        if (isset($data['compare_id']) && $data['compare_id']) {
            $obj = [
                "player_id" => $data['compare_id'],
                "status" => $status,
            ];
            $compare = $this->battingWagon($obj);
        } else {
            $compare = [];
        }
        return ["player" => $player, "compare" => (object)$compare];
    }

    public function compareBowlingWagon($data)
    {
        $player = $this->bowlingWagon($data);

        if (isset($data['compare_id']) && $data['compare_id']) {
            $obj = [
                "player_id" => $data['compare_id'],
                'status' => $data['status']
            ];
            $compare = $this->bowlingWagon($obj);
        } else {
            $compare = [];
        }
        return ["player" => $player, "compare" => (object)$compare];
    }

    public function bowlingComarison($data)
    {
        $bowler = $this->playerQuery->playerBowlingComparison($data);
        $bowler->economy_rate = $bowler->total_runs && $bowler->total_overs ? number_format($bowler->total_runs / $bowler->total_overs, 2, ".", "") : null;
        $bowler->strike_rate = $bowler->total_deliveries && $bowler->total_wickets ? number_format($bowler->total_deliveries / $bowler->total_wickets, 2, ".", "") : null;
        $bowler->best_score = $bowler->inningsBowler && isset($bowler->inningsBowler[0]) ? $bowler->inningsBowler[0]->wickets . '/' . $bowler->inningsBowler[0]->runs_gave : null;
        $bowler->average = $bowler->total_runs && $bowler->total_wickets ? number_format($bowler->total_runs / $bowler->total_wickets, 2, ".", "") : null;
        $bowler->total_overs = $bowler->total_overs ? round($bowler->total_overs, 2) : null;
        unset($bowler->inningsBowler);

        return $bowler;
    }

    public function fieldingCompare($data){
        $id = $data['player_id'];
        $player = $this->playerQuery->fieldingCompare($id);
        if(isset($data['compare_id']) && $data['compare_id']){

            $obj = [
                "player_id" => $data['compare_id'],
            ];

            $compare = $this->playerQuery->fieldingCompare($id);

        }else{
            $compare = [];
        }
        return ["player" => $player, "compare" => (object)$compare];
    }

    public function bowlingComarisonTop($data){
        $bowler = $this->playerQuery->bowlingComarisonTop($data);
        // return $bowler;
        $bowler->avgLHB = $bowler->total_runs_against_lh && $bowler->total_wickets_against_lh ? number_format($bowler->total_runs_against_lh/$bowler->total_wickets_against_lh, 2, ".",""):null;
        $bowler->avgRHB = $bowler->total_runs_against_rh && $bowler->total_wickets_against_rh ? number_format($bowler->total_runs_against_rh/$bowler->total_wickets_against_rh, 2, ".",""):null;
        $bowler->topSixDismissals =$bowler->first_six_wickets && $bowler->total_wickets ? number_format(($bowler->first_six_wickets/$bowler->total_wickets) *100, 2, ".","") :null;
        $bowler->bounderies = $bowler->total_boundaries && $bowler->total_balls_bowled ? number_format(($bowler->total_boundaries/$bowler->total_balls_bowled)*100, 2, ".",""):null;
        unset($bowler->total_boundaries,$bowler->total_balls_bowled,$bowler->first_six_wickets, $bowler->total_wickets,$bowler->total_runs_against_lh,$bowler->total_wickets_against_lh,$bowler->total_runs_against_rh,$bowler->total_wickets_against_rh);
        return $bowler;
    }

    public function playerBowlingComparison($data)
    {

        $data['limit'] = 2;
        $bowler = $this->bowlingComarison($data);
        $topStats = $this->bowlingComarisonTop($data);
        $last_two_innings = $this->playerCurrentBowlingFormAndInnings($data);
        if (isset($data['compare_id']) && $data['compare_id']) {

            $obj = [
                "player_id" => $data['compare_id'],
            ];

            $compare = $this->bowlingComarison($obj);
            $topStatsCompare = $this->bowlingComarisonTop($obj);

        } else {
            $compare = [];
            $topStatsCompare = [];
        }

        return ["topStats" => $topStats,"topStatsCompare"=> (object)$topStatsCompare, "bowler" => $bowler, "compare" => (object)$compare, "last_two_innings" => (object)$last_two_innings];
    }


    public function playerOutTypeComparison($playerId)
    {
        $messyObj = $this->playerQuery->playerOutTypeComparisonQuery($playerId);
        $formattedObj = collect();
        $formattedObj->put('total_bowled_wickets', $messyObj->total_bowled_wickets ?? "0");
        $formattedObj->put('total_caught_wickets', $messyObj->total_caught_wickets ?? "0");
        $formattedObj->put('total_caught_and_bowled_wickets', $messyObj->total_caught_and_bowled_wickets ?? "0");
        $formattedObj->put('total_caught_behind_wickets', $messyObj->total_caught_behind_wickets ?? "0");
        $formattedObj->put('total_stumped_wickets', $messyObj->total_stumped_wickets ?? "0");
        $formattedObj->put('total_run_out_wickets', $messyObj->total_run_out_wickets ?? "0");
        $formattedObj->put('total_lbw_wickets', $messyObj->total_lbw_wickets ?? "0");
        $formattedObj->put('total_absent_wickets', $messyObj->total_absent_wickets ?? "0");
        $formattedObj->put('total_retired_hurt_wickets', $messyObj->total_retired_hurt_wickets ?? "0");
        $formattedObj->put('total_action_wickets', $messyObj->total_action_wickets ?? "0");
        $formattedObj->put('total_hit_wickets', $messyObj->total_hit_wickets ?? "0");
        $formattedObj->put('total_hit_ball_twice_wickets', $messyObj->total_hit_ball_twice_wickets ?? "0");
        $formattedObj->put('total_obstructing_field_wickets', $messyObj->total_obstructing_field_wickets ?? "0");
        $formattedObj->put('total_time_out_wickets', $messyObj->total_time_out_wickets ?? "0");
        $formattedObj->put('total_wickets', $messyObj->total_wickets ?? "0");
        return $formattedObj;
    }

    public function getPlayerList($data)
    {
        return $this->playerQuery->getPlayerList($data);
    }

    public function battingFaceOff($data)
    {
        $player = $this->playerQuery->battingFaceOff($data);
        Log::channel('slack')->info(['d' => $player]);
        $player['average'] = '---';
        $player['strike_rate'] = '---';
        if(isset($player)){
            if($player['total_wickets'] > 0){
                $player['average'] = number_format(($player['total_runs_scored'] / $player['total_innings']), 2);
            }

            if($player['total_balled_faced'] > 0){
                $player['strike_rate'] = number_format((($player['total_runs_scored'] / $player['total_balled_faced']) * 100), 2);
            }
        }
        return $player;
    }

    public function bowlingFaceOff($data)
    {
        return $this->battingFaceOff([
            'player_id' => $data['face_off_player_id'],
            'face_off_player_id' => $data['player_id'],
        ]);
    }

    public function positionBowler($id){
        $bowler =$this->playerQuery->bowlingPostion($id);
        $arr = array();
        if($bowler->count() > 0){
            foreach($bowler as $b){
                $ob = [
                    "over_number" => $b->over_number ? (int)$b->over_number:0,
                    "total_overs" => $b->total_overs ? (int)$b->total_overs :0,
                    "total_runs" => $b->total_runs ? (int)$b->total_runs :0,
                    "total_wickets" => $b->total_wickets ? (int)$b->total_wickets :0,
                ];
                array_push($arr, $ob);
            }
        }else{
            $ob = [
                "over_number" => 0,
                "total_overs" => 0,
                "total_runs" => 0,
                "total_wickets" => 0,
            ];
            array_push($arr, $ob);
        }

        return $arr;

    }

    public function bowlingPostion($data){
        $id = $data['player_id'];
        $bowler = $this->positionBowler($id);

        if(isset($data['compare_id']) && $data['compare_id']){
            $id = $data['compare_id'];
            $compare = $this->positionBowler($id);
        }
        return ["bowler" => $bowler, "compare" => $compare];
    }

    public function positionBatter($id){
        $batter =$this->playerQuery->battingPosition($id);
        $arr = array();
        if($batter->count() > 0){
            foreach($batter as $b){
                $ob = [
                    "position" => $b->position ? (int)$b->position:0,
                    "totalInnings" => $b->totalInnings ? (int)$b->totalInnings :0,
                    "totalRuns" => $b->totalRuns ? (int)$b->totalRuns :0,
                    "strikeRate" => $b->strikeRate ? round($b->strikeRate, 2) :0,
                ];
                array_push($arr, $ob);
            }
        }else{
            $ob = [
                "position" => 0,
                "totalInnings" => 0,
                "totalRuns" => 0,
                "strikeRate" => 0,
            ];
            array_push($arr, $ob);
        }

        return $arr;
    }
    public function battingPosition($data){
        $id = $data['player_id'];
        $batter = $this->positionBatter($id);

        if(isset($data['compare_id']) && $data['compare_id']){
            $ob =[
                "player_id" => $data['compare_id']
            ];
            $compare = $this->positionBatter($ob);
        }
        return ["batter" => $batter, "compare" => $compare];
    }

    public function outBetweenRuns($data){

        $batter = $this->formattedOutBetweenRunsRecords($data['player_id']);
        $compare = $this->formattedOutBetweenRunsRecords($data['compare_id']);

        return ["batter" => $batter, "compare" => $compare];
    }

    public function formattedOutBetweenRunsRecords($playerId){
        $formattedRecords = [];
        $records = $this->playerQuery->getBatterInningsWicketRecords($playerId);
        $totalWickets = $records->count() ?? 0;
        if($totalWickets){
            $maxRuns = $records->max('runs_achieved');

            for($i = 1; $i <= $maxRuns; $i += 10){
                $j = $i + 9;
                $runs = $i."-".$j;
                $wickets = $records->whereBetween('runs_achieved', [$i, $j])->count();
                $wicketsPercentage = (($wickets / $totalWickets) * 100);
                $formattedRecords[] = [
                    'runs' => $runs,
                    'wickets' => $wicketsPercentage,
                ];
            }
        }

        return $formattedRecords;
    }

    public function battingAgainstDifferentBowlers($data){
        $id = $data['player_id'];
        $batter = $this->playerQuery->battingAgainstDifferentBowlers($id);
        return $batter;

        if(isset($data['compare_id']) && $data['compare_id']){
            $ob =[
                "player_id" => $data['compare_id']
            ];
            $compare = $this->playerQuery->outBetweenRuns($ob);
        }
        return ["batter" => $batter, "compare" => $compare];
    }

    public function bowlerStatesByYear($id){
        $bowler = $this->playerQuery->bowlerStatesByYear($id);

        foreach ($bowler as $b) {
            $b->total_overs = $this->oversFormat($b->total_overs_without_balls, $b->total_balls);
            $b->economy = $b->total_overs_without_balls || $b->total_balls ? round($b->total_runs / $this->oversFormatForEconomy($b->total_overs_without_balls, $b->total_balls), 1) : 0;
            unset($b->total_overs_without_balls, $b->total_balls);
        }

        $ob = [
            "player_id" => $id
        ];
        $overallstats = $this->playerBowlingOverallStats($ob);
        return ["yearStats" => $bowler, "overallStats" => $overallstats];
    }

    //Player-batting-insights-end


    //Face off comparison insights start
    public function getPlayerFaceOffOutsComparison($data)
    {
        if ($data['stats_type'] == 'BOWLING'){
            $temp = $data['player_id'];
            $data['player_id'] =  $data['comparer_id'];
            $data['comparer_id'] = $temp;
        }

        $obj = $this->playerQuery->getPlayerFaceOffOutsComparisonQuery($data);
        $obj->total_caught_wickets = (int)$obj->total_caught_wickets ?? 0;
        $obj->total_bowled_wickets = (int)$obj->total_bowled_wickets ?? 0;
        return $obj;
    }
    //Face off comparison insights end



}
