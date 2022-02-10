<?php

namespace App\Http\Controllers\Statistics;

use Illuminate\Support\Facades\Auth;
use Log;

class StatisticsService
{
    private $statisticsQuery;
    public function __construct(StatisticsQuery $statisticsQuery)
    {
        $this->statisticsQuery = $statisticsQuery;
    }
    public function playerRunVsPercentageStath($id)
    {
        $alldata = $this->statisticsQuery->playerRunVsPercentageStathQuery($id);
        $allcollections = $alldata['batting_by_deliveries'];

        $total = sizeof($allcollections);
        $allcollections2 = $alldata['batting_by_deliveries'];
        $allcollections3 = $alldata['batting_by_deliveries'];
        $allcollections4 = $alldata['batting_by_deliveries'];
        $formated_run = $allcollections->groupBy('runs');

        unset($alldata['batting_by_deliveries']);

        $bounderis_group = $allcollections2->groupBy('boundary_type');
        $bounderis = [];
        if (isset($bounderis_group['SIX']) && isset($bounderis_group['FOUR'])) {
            $bounderis = array_merge($bounderis_group['SIX']->toArray(), $bounderis_group['FOUR']->toArray());
        } else if (isset($bounderis_group['SIX'])) {
            $bounderis = $bounderis_group['SIX']->toArray();
        } else if (isset($bounderis_group['FOUR'])) {
            $bounderis = $bounderis_group['FOUR']->toArray();
        }
        $bounderis = collect($bounderis);
        $most_boundary_boller_id = -1;
        $most_boundary_count = -1;
        $boundries_grouby_boller = $bounderis->groupBy('bowler_id');
        $most_boundary_data = null;
        foreach ($boundries_grouby_boller as $key => $val) {
            if (sizeof($val) > $most_boundary_count) {
                $most_boundary_count = sizeof($val);
                $most_boundary_boller_id = $key;
            }
        }
        if ($most_boundary_count > -1) {
            $bowler = $this->statisticsQuery->getUserById($most_boundary_boller_id);
            $percentage = ($most_boundary_count / $total) * 100;
            $most_boundary_data = ['bowler' => $bowler, 'percentage' => $percentage, 'most_boundary_boller_id' => $most_boundary_boller_id, 'most_boundary_count' => $most_boundary_count];
        }

        $ball_by_run_percnetage_data = [];
        $dots_user_id = -1;
        $dot_ball_data = null;
        $dot_ball_count = -1;

        foreach ($formated_run as $key => $val) {
            // most dots boller start
            if ($key == 0) {
                $temp = $val->groupBy('bowler_id');
                if (sizeof($temp) > 0) {
                    foreach ($temp as $key_d => $val_d) {
                        if (sizeof($val_d) > $dot_ball_count) {
                            $dot_ball_count = sizeof($val_d);
                            $dots_user_id = $key_d;
                        }
                    }
                }
            } // most dots boller end
            // run type vs percentage start
            $ob = [
                'runs' => $key == 0 ? 'Dots' : $key . 's',
                // ==0?'Dots': `${key}s`,
                'percentage' => (sizeof($val) / $total) * 100
            ];

            $ob['percentage'] = round($ob['percentage'], 2);
            array_push($ball_by_run_percnetage_data, $ob);
            // run type vs percentage end

        }
        if ($dots_user_id != -1) {
            $dot_boller = $this
                ->statisticsQuery
                ->getUserById($dots_user_id);
            $percentage = ($dot_ball_count / $total) * 100;
            $percentage = round($percentage, 2);
            $dot_ball_data = ['percentage' => $percentage, 'bowler' => $dot_boller, 'dot_ball_count' => $dot_ball_count, 'total' => $total];
        }

        $total_innings = $this
            ->statisticsQuery
            ->getInningBatterResultQuery($id, null);
        $to = $total_innings;
        $run_30_plus = $this
            ->statisticsQuery
            ->getInningBatterResultQuery($id, 30);
        $run_50_plus = $this
            ->statisticsQuery
            ->getInningBatterResultQuery($id, 50);
        $run_100_plus = $this
            ->statisticsQuery
            ->getInningBatterResultQuery($id, 100);

        $winning_data = [];
        if($run_30_plus > 0){
            $percentage = round((($run_30_plus / $to) * 100), 2);
            $winning_data[] = [
                'percentage' => $percentage,
                'run_number' => '30'
            ];
        }
        if($run_50_plus > 0){
            $percentage = round((($run_50_plus / $to) * 100), 2);
            $winning_data[] = [
                'percentage' => $percentage,
                'run_number' => '50'
            ];
        }
        if($run_100_plus > 0){
            $percentage = round((($run_100_plus / $to) * 100), 2);
            $winning_data[] = [
                'percentage' => $percentage,
                'run_number' => '100'
            ];
        }

        // most runs against bowller start
        $formated_run_bollower_group = $allcollections3->groupBy('bowler_id');
        $max_runs = -1;
        $most_run_data = ['runs' => -1, 'bowler_id' => null, 'bowler' => null];
        foreach ($formated_run_bollower_group as $key_m_r_k => $val_m_r_v) {
            $temp_value = collect($val_m_r_v);
            $temp_max = $temp_value->sum('runs');
            if ($temp_max > $most_run_data['runs']) {
                $most_run_data = ['runs' => $temp_max, 'bowler_id' => $key_m_r_k,];
            }
        }
        if ($most_run_data['runs'] > -1) {
            $bowler = $this
                ->statisticsQuery
                ->getUserById($most_run_data['bowler_id']);
            $most_run_data['bowler'] = $bowler;
        }
        // most runs against bowller end
        $ball_vs_runs_playing_style = $this->statisticsQuery->getPlayerRunsByEveryDeleveryV2Query($id);



        // last founr item

        $innings_data = $this->statisticsQuery->getInningsWithDeleverQuery($id);

        $groupby_innings_vs_position_data = $innings_data->groupBy('position');
        $innings_vs_position_data = [];
        $strikerate_vs_position_data = [];
        $run_vs_position_data = [];
        $ball_vs_runs = [];

        foreach ($groupby_innings_vs_position_data as $key => $val) {
            $ob = ['number_of_innings' => sizeof($val), 'position' => $key];
            $ob2 = ['runs' => 0, 'position' => $key];
            $ob3 = ['strike_rate' => 0, 'position' => $key, 'total_ball' => 0, 'total_run' => 0,];
            foreach ($val as $key1 => $val1) {
                $ob2['runs'] += $val1['runs_achieved'];
                $ob3['total_ball'] += $val1['balls_faced'];
            }
            $ob3['total_run'] = $ob2['runs'];
            $f = 1;
            if ($ob3['total_ball'] > 0) {
                $f = $ob3['total_ball'];
            }
            $ob3['strike_rate'] = ($ob3['total_run'] / $f) * 100;
            $ob3['strike_rate'] = round($ob3['strike_rate'], 2);


            array_push($run_vs_position_data, $ob2);
            array_push($innings_vs_position_data, $ob);
            array_push($strikerate_vs_position_data, $ob3);
        }

        $travarser = 1;
        $total_runs = 0;
        $temp = 0;
        foreach ($ball_vs_runs_playing_style as $key_style => $value) {
            if ($travarser <= 10) {
                // break;
                $total_runs += $value['runs'];
                $travarser++;
            }
            $value['orginal_run'] = $value['runs'];
            $temp += $value['runs'];
            $value['runs'] = $temp;
            unset($value['run_type']);
        }

        $first_10_ball_strike_rate = ($total_runs / 10) * 100;
        if ($total < 10 && $total > 0) {
            $first_10_ball_strike_rate = ($total_runs / $total) * 100;
        }

        $first_10_ball_strike_rate = round($first_10_ball_strike_rate, 2);
        return [
            'player' => $alldata,
            'ball_by_run_percnetage_data' => $ball_by_run_percnetage_data,
            'most_run_data' => $most_run_data,
            'most_boundary_data' => $most_boundary_data,
            'dot_ball_data' => $dot_ball_data,
            'winning_data' => $winning_data,
            'ball_vs_runs_playing_style' => $ball_vs_runs_playing_style,
            'innings_vs_position_data' => $run_vs_position_data,
            'run_vs_position_data' => $run_vs_position_data,
            'strikerate_vs_position_data' => $strikerate_vs_position_data,
            'first_10_ball_strike_rate' => $first_10_ball_strike_rate
        ];
    }

    public function bowller_position_vs_stath_without_slot($id)
    {
        $alldata =  $this->statisticsQuery->playerOverWithDeleveryQuery($id);
        $gorup_by_obers_number = $alldata->groupBy('over_number');
        $over_number_vs_runs = [];
        for ($i = 1; $i <= 50; $i++) {
            $ob = [
                'position' => $i,
                'number_of_over' => 0,
                'runs' => 0,
                'number_of_wicket' => 0
            ];
            array_push($over_number_vs_runs, $ob);
        }

        foreach ($gorup_by_obers_number as $key => $val) {
            // $ob['position'] = $key;
            $temp = $val->countBy('id');
            // $ob['number_of_over'] =sizeof($temp);
            // $ob['runs'] =  $gorup_by_obers_number[$key]->sum('runs');
            $over_number_vs_runs[$key - 1]['number_of_over'] = sizeof($temp);
            $over_number_vs_runs[$key - 1]['runs'] = $gorup_by_obers_number[$key]->sum('runs');



            $total = 0;
            foreach ($val as $key1 => $val1) {
                if ($val1['wicket_by']) {
                    $total += 1;
                }
            }
            // $ob['number_of_wicket'] =  $total;
            $over_number_vs_runs[$key - 1]['number_of_wicket'] = $total;

            // array_push($over_number_vs_runs, $ob);
        }
        $alldata = $this->bowller_position_vs_stath_with_slot($id);
        return [
            'stath_data' => $over_number_vs_runs,
            'max_wicket' => $alldata['max_wicket'],
            'max_runs' => $alldata['max_runs'],
            'max_dots' => $alldata['max_dots'],
        ];
    }
    public function bowller_position_vs_stath_with_slot($id)
    {
        $alldata =  $this->statisticsQuery->playerOverWithDeleveryQuery($id);
        $gorup_by_obers_number = $alldata->groupBy('over_number');


        $over_number_vs_runs = [];

        $arrays['1-10'] = [
            'number_of_over' => 0,
            'runs' => 0,
            'number_of_wicket' => 0,
            'dots' => 0
        ];
        $arrays['11-20'] = [
            'number_of_over' => 0,
            'runs' => 0,
            'number_of_wicket' => 0,
            'dots' => 0
        ];
        $arrays['21-30'] = [
            'number_of_over' => 0,
            'runs' => 0,
            'number_of_wicket' => 0,
            'dots' => 0
        ];
        $arrays['31-40'] = [
            'number_of_over' => 0,
            'runs' => 0,
            'number_of_wicket' => 0,
            'dots' => 0
        ];
        $arrays['41-50'] = [
            'number_of_over' => 0,
            'runs' => 0,
            'number_of_wicket' => 0,
            'dots' => 0
        ];

        foreach ($gorup_by_obers_number as $key => $val) {
            $first = 0;
            $last = 0;

            if ($key % 10 == 0) {
                $first = (ceil($key / 10) * 10) - 9;
                $last = ($key / 10) * 10;
            } else {
                $first =  (ceil($key / 10) * 10) - 9;
                $last = ceil($key / 10) * 10;
            }
            $new_key = "$first-$last";
            $temp = $val->countBy('id');
            $arrays[$new_key]['runs'] += $gorup_by_obers_number[$key]->sum('runs');
            $total = 0;
            foreach ($val as $key1 => $val1) {
                if ($val1['wicket_by']) {
                    $total += 1;
                }
            }
            $ob['number_of_wicket'] =  $total;

            $arrays[$new_key]['number_of_over'] += sizeof($temp);
            $arrays[$new_key]['number_of_wicket'] += $total;

            $runs_group = $alldata->groupBy('runs');
            $max_dots_temp = 0;
            if (isset($runs_group['0']) and $runs_group['0']) {
                $max_dots_temp = sizeof($runs_group['0']);
            }
            $arrays[$new_key]['dots'] += $max_dots_temp;
        }




        $stath_data = [];
        $max_wicket = [
            'wickets' => -1,
            'position' => 0,
        ];
        $max_runs = [
            'runs' => -1,
            'position' => 0,
        ];
        $max_dots = [
            'dots' => -1,
            'position' => 0,
        ];
        foreach ($arrays as $key => $val) {
            if ($val['number_of_wicket'] > $max_wicket['wickets']) {
                $max_wicket['wickets'] = $val['number_of_wicket'];
                $max_wicket['position'] = $key;
            }
            if ($val['runs'] > $max_runs['runs']) {
                $max_runs['runs'] = $val['runs'];
                $max_runs['position'] = $key;
            }
            if ($val['dots'] > $max_dots['dots']) {
                $max_dots['dots'] = $val['dots'];
                $max_dots['position'] = $key;
            }

            $ob = [
                'number_of_over' => $val['number_of_over'],
                'number_of_wicket' => $val['number_of_wicket'],
                'runs' => $val['runs'],
                'position' => $key,
            ];
            array_push($stath_data, $ob);
        }
        return [
            'stath_data' => $stath_data,
            'max_wicket' => $max_wicket,
            'max_runs' => $max_runs,
            'max_dots' => $max_dots,

        ];
    }

    public function bowller_stath_ball_by_percentage($id)
    {
        $innings_data = $this->statisticsQuery->playerOverWithLegalDeleveryQuery($id);

        $bowlerDeliveries = $this->statisticsQuery->bowlerDeliveries($id);
        
        $totalWickets = $bowlerDeliveries->whereNotNull('wicket_by')->whereNotNull('ball_number');
        $totalBoundaries = $bowlerDeliveries->whereNotNull('boundary_type')->whereNotNull('ball_number');
        $totalDots = $bowlerDeliveries->where('runs', 0)->whereNotNull('ball_number');
        
        $countedWickets = $totalWickets->countBy(function($item){
            return substr(strrchr($item['ball_number'], "."), 1);
        });
        $countedBoundaries = $totalBoundaries->countBy(function($item){
            return substr(strrchr($item['ball_number'], "."), 1);
        });
        $countedDots = $totalDots->countBy(function($item){
            return substr(strrchr($item['ball_number'], "."), 1);
        });

        $percentageByType = [];
        $totalWickets = $totalWickets->count();
        $totalBoundaries = $totalBoundaries->count();
        $totalDots = $totalDots->count();
        for ($i = 1; $i <= 6; $i++) {
            $wickets = ($totalWickets and isset($countedWickets[(string)$i])) ? round($countedWickets[(string)$i] / $totalWickets * 100) : 0;
            $boundaries = ($totalBoundaries and isset($countedBoundaries[(string)$i])) ? round($countedBoundaries[(string)$i] / $totalBoundaries * 100) : 0;
            $dots = ($totalDots and isset($countedDots[(string)$i])) ? round($countedDots[(string)$i] / $totalDots * 100) : 0;

            $percentageByType[] = [
                'ball_number' => $i,
                'wicket_percentage' => $wickets,
                'boundery_percentage' => $boundaries,
                'dots_percentage' => $dots,
            ];
        }

        $format_ingnigs_data = [];

        $total = 0;
        foreach ($innings_data as $key => $val) {
            $i = 1;
            foreach ($val['oversDelivery'] as $key_1 => $val_1) {
                $val_1['position'] = $i;
                array_push($format_ingnigs_data, $val_1);
                $i++;
            }
            $total += sizeof($val['oversDelivery']);
        }

        $collection_postion = collect($format_ingnigs_data);
        // $temp_data = $collection_postion->groupBy('position');
        // $d = '';
        // $ball_by_wicket_percentage_data = [];
        // for ($i = 1; $i <= 6; $i++) {
        //     array_push($ball_by_wicket_percentage_data, [
        //         'ball_number' => $i,
        //         'wicket_percentage' => 0,
        //         'boundery_percentage' => 0,
        //         'dots_percentage' => 0,
        //     ]);
        // }

        // return $ball_by_wicket_percentage_data;
        // return $ball_by_wicket_percentage_data;
        // foreach ($temp_data as $key => $val) {
        //     $ob = [
        //         'ball_number' => $key,
        //         'wicket_percentage' => 0,
        //         'boundery_percentage' => 0,
        //         'dots_percentage' => 0,
        //     ];
        //     $wicket_group =  $val->groupBy('wicket_by');
        //     if (isset($wicket_group[$id])) {
        //         return $wicket_group;
        //         $d = $wicket_group[$id];
        //         if ($total != 0)
        //             $ball_by_wicket_percentage_data[$key]['wicket_percentage'] = round(((sizeof($d) / $total) * 100), 2);
        //     }


        //     $bounderis_group = $val->groupBy('boundary_type');
        //     $six_four_count = 0;
        //     // return isset($bounderis_group['FOUR'])? sizeof($bounderis_group['FOUR']):'nothing';

        //     if (isset($bounderis_group['FOUR'])) {
        //         $six_four_count += sizeof($bounderis_group['FOUR']);
        //     }
        //     if (isset($bounderis_group['SIX'])) {
        //         $six_four_count += sizeof($bounderis_group['SIX']);
        //     }
        //     if ($total > 0)
        //         $ball_by_wicket_percentage_data[$key]['boundery_percentage'] = round((($six_four_count / $total) * 100), 2);


        //     $dots_ball_group = $val->groupBy('runs');
        //     if (isset($dots_ball_group[0]) && $total > 0) {
        //         $ball_by_wicket_percentage_data[$key]['dots_percentage'] = round(((sizeof($dots_ball_group[0]) / $total) * 100), 2);
        //     }

        //     // array_push($ball_by_wicket_percentage_data, $ob);
        // }

        $collection_postion_type_of_wickets = collect($format_ingnigs_data);
        $group_by_wickets_type = $collection_postion_type_of_wickets->groupBy('wicket_type');

        $wicket_type_data = [];
        array_push($wicket_type_data, ['type' => 'BOWLED', 'value' => 0]);
        array_push($wicket_type_data, ['type' => 'LBW', 'value' => 0]);
        array_push($wicket_type_data, ['type' => 'STUMPED', 'value' => 0]);
        array_push($wicket_type_data, ['type' => 'CAUGHT', 'value' => 0]);
        array_push($wicket_type_data, ['type' => 'CAUGHT_BEHIND', 'value' => 0]);
        array_push($wicket_type_data, ['type' => 'CAUGHT_BOWLED', 'value' => 0]);

        $total_wicket = 0;

        foreach ($group_by_wickets_type as $key => $val) {

            if ($key != '') {
                $value = sizeof($val);

                $currentTypeIndex = array_search($key, array_column($wicket_type_data, 'type'));
                if ($currentTypeIndex != -1) {
                    $wicket_type_data[$currentTypeIndex]['value'] = $value;
                }

                $total_wicket += $value;
            }
        }
        //   $batter_avg_query_data =
        $batter_avg_query_data =   $this->statisticsQuery->playerOverWithLegalDeleveryWithBatterQuery($id);
        $group_by_batting_style = $batter_avg_query_data->groupBy('batting_style');


        $count_ball_RH = isset($group_by_batting_style['RH']) ? sizeof($group_by_batting_style['RH']) : 0;
        $count_run_RH = isset($group_by_batting_style['RH']) ? $group_by_batting_style['RH']->sum('runs') : 0;
        $count_ball_LH = isset($group_by_batting_style['LH']) ? sizeof($group_by_batting_style['LH']) : 0;
        $count_run_LH = isset($group_by_batting_style['LH']) ? $group_by_batting_style['LH']->sum('runs') : 0;
        $RH_STRIKE_RATE = 0;
        $RH_AVG = $count_run_RH /  ($count_ball_RH ? $count_ball_RH : 1);
        $LH_AVG = $count_run_LH /  ($count_ball_LH ? $count_ball_LH : 1);
        $LH_STRIKE_RATE = 0;
        if ($count_ball_RH > 0)
            $RH_STRIKE_RATE = round((($count_run_RH / $count_ball_RH) * 100), 2);
        if ($count_ball_LH > 0)
            $LH_STRIKE_RATE = round((($count_run_LH / $count_ball_LH) * 100), 2);

        $RH_WICKET_GROUP = isset($group_by_batting_style['RH']) ? $group_by_batting_style['RH']->groupBy('wicket_by') : [];
        $LH_WICKET_GROUP = isset($group_by_batting_style['LH']) ? $group_by_batting_style['LH']->groupBy('wicket_by') : [];
        $RH_WICKET_PERCENTAGE = 0;
        $LH_WICKET_PERCENTAGE = 0;

        $totalWickets = $batter_avg_query_data->groupBy('wicket_by');

        if($totalWickets->get($id)){
            $totalWickets = $totalWickets->get($id)->count();
        } else {
            $totalWickets = 0;
        }

        if($totalWickets){
            if (isset($RH_WICKET_GROUP[$id])) {
                $cnt = $RH_WICKET_GROUP[$id]->count('id');
                $RH_WICKET_PERCENTAGE = round((($cnt / $totalWickets) * 100), 2);
            }
            if (isset($LH_WICKET_GROUP[$id])) {
                $cnt = $LH_WICKET_GROUP[$id]->count('id');
                $LH_WICKET_PERCENTAGE = round((($cnt / $count_ball_LH) * 100), 2);
            }
        }

        //   $take_more_wickets_data =
        $firstInnins = $this->statisticsQuery->getFirstOrSecondInnings($id, 1);
        $firstResult = $firstInnins->sum('wickets');
        $secondInnins = $this->statisticsQuery->getFirstOrSecondInnings($id, 0);
        $secondResult = $secondInnins->sum('wickets');
        $text = 'Takes more wickets in 1st innings';
        if ($secondResult > $firstResult) {
            $text = 'Takes more wickets in 2nd innings';
        }

        return [
            'ball_by_wicket_percentage_data' => $percentageByType,
            'wicket_type_data' => $wicket_type_data,
            'total_wicket' => $total_wicket,
            'rh_strike_rate' => $RH_STRIKE_RATE,
            'lh_strike_rate' => $LH_STRIKE_RATE,
            'rh_avarage' => round($RH_AVG, 2),
            'lh_avarage' => round($LH_AVG, 2),
            'rh_wicket_percentage' => $RH_WICKET_PERCENTAGE,
            'lh_wicket_percentage' => $LH_WICKET_PERCENTAGE,
            'wicket_taking_text' => $text
        ];
    }

    public function batting_position_wise_wicket($id)
    {
        $innings_data = $this->statisticsQuery->batting_position_wise_wicketQuery($id);
        $group_by_position = $innings_data->groupBy('position');

        $top_order_wicket = 0;
        $middle_order_wicket = 0;
        $lower_order_wicket = 0;
        $total = 0;
        $position_vs_wicket = [];
        for ($i = 1; $i <= 11; $i++) {
            array_push($position_vs_wicket, ['position' => $i, 'wicket_number' => 0]);
        }
        foreach ($group_by_position as $key => $val) {
            $siz = sizeof($val);
            $total += $siz;
            $ob = [
                'position' => $key,
                'wicket_number' => $siz
            ];
            if ($key > 0 && $key <= 3) {
                $top_order_wicket += $siz;
            }
            if ($key > 3 && $key <= 7) {
                $middle_order_wicket += $siz;
            }
            if ($key > 7 && $key <= 11) {
                $lower_order_wicket += $siz;
            }
            array_push($position_vs_wicket, $ob);
        }

        $top_order_wicket_percenatage = 0;
        $middle_order_wicket_percenatage = 0;
        $lower_order_wicket_percenatage = 0;
        if ($total > 0) {
            $top_order_wicket_percenatage = ($top_order_wicket / $total) * 100;
            $middle_order_wicket_percenatage = ($middle_order_wicket / $total) * 100;
            $lower_order_wicket_percenatage = ($lower_order_wicket / $total) * 100;
        }
        $innings_data_by_boller_id = $this->statisticsQuery->getInningsbyBollwerId($id);
        $group_by_innings = $innings_data_by_boller_id->groupBy('wickets');
        $tem_array = [
            '0' => 1,
            '1' => 2,
            '2' => 3,
            '3' => 4,
            '4' => 5,
            '5' => 6,
            '6' => 7,
            '7' => 8,
            '8' => 9,
            '9' => 10,
            '10' => 11,
        ];
        $innings_vs_wickets = [];


        foreach ($group_by_innings as $key => $val) {
            $flag = 0;
            if (isset($tem_array[$key])) {
                $flag = 1;
            }
            if ($flag > 0) unset($tem_array[$key]);
            array_push($innings_vs_wickets, [
                'innings' => sizeof($val),
                'wickets' => $key
            ]);
        }
        foreach ($tem_array as $key => $val) {

            array_push($innings_vs_wickets, [
                'innings' => 0,
                'wickets' => $key
            ]);
        }
        for ($i = 0; $i < sizeof($innings_vs_wickets); $i++) {
            $temp = $innings_vs_wickets[$i];
            $flag = $i;
            for ($j = $i + 1; $j < sizeof($innings_vs_wickets); $j++) {
                if ($temp['wickets'] > $innings_vs_wickets[$j]['wickets']) {
                    $temp = $innings_vs_wickets[$j];
                    $flag = $j;
                }
            }
            if ($flag != $i) {
                $temp2 = $innings_vs_wickets[$i];
                $innings_vs_wickets[$flag] = $temp2;
                $innings_vs_wickets[$i] = $temp;
            }
        }
        for ($i = 0; $i < sizeof($innings_vs_wickets); $i++) {
            $innings_vs_wickets[$i]['wickets'] = (string)$innings_vs_wickets[$i]['wickets'];
        }

        $win_fixture = $this->statisticsQuery->getMatchesByBowllerId($id, 1);
        $loss_fixture = $this->statisticsQuery->getMatchesByBowllerId($id, 2);
        $win_fixture_group_by_wickets =  $win_fixture->groupBy('wickets');
        $loss_fixture_group_by_wickets =  $loss_fixture->groupBy('wickets');
        $max_win_by_wickets_percenatage = 0;
        $len = sizeof($win_fixture);
        if ($len > 0) {
            foreach ($win_fixture_group_by_wickets as $key => $val) {
                if ($key < 2) {
                    $tem_percentage = (sizeof($val) / $len) * 100;
                    $max_win_by_wickets_percenatage += $tem_percentage;
                }
            }
        }


        $max_loss_by_wickets_percenatage = 0;
        $len = sizeof($loss_fixture);
        if ($len > 0) {
            if (isset($loss_fixture_group_by_wickets[0])) {
                $max_loss_by_wickets_percenatage = (sizeof($loss_fixture_group_by_wickets[0]) / $len) * 100;
            }
        }
        $player = $this->statisticsQuery->getUserById($id);
        return [
            'total_wicket' => $total,
            'position_vs_wicket' => $position_vs_wicket,
            'top_order_wicket_percenatage' => round($top_order_wicket_percenatage),
            'middle_order_wicket_percenatage' => round($middle_order_wicket_percenatage),
            'lower_order_wicket_percenatage' => round($lower_order_wicket_percenatage),
            'innings_vs_wickets' => $innings_vs_wickets,
            'team_win_text' => round($max_win_by_wickets_percenatage) > 0 ? 'Team win ' . round($max_win_by_wickets_percenatage) . '% if '
                . $player['first_name'] . ' ' . $player['last_name'] . ' takes 3+ wickets' : null,
            'team_loss_text' => ($max_loss_by_wickets_percenatage) > 0 ? 'Team loses ' . $max_loss_by_wickets_percenatage . ' % if '
                . $player['first_name'] . ' ' . $player['last_name'] . ' goes wicket less' : null,
        ];
    }

    public function bowller_type_of_runs_vs_percentages($id)
    {

        $zero_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 0, 0, 'count');
        $one_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 1, 0, 'count');
        $two_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 2, 0, 'count');
        $three_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 3, 0, 'count');
        $four_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 4, 0, 'count');
        $five_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 5, 0, 'count');
        $six_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 6, 0, 'count');


        $total_sum = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 0, 0, 'sum');
        $six_four_runs = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 6, 6, 'sum');
        $six_four_runs_extra = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, 6, 6, 'sum_extra');
        $total_boundary_runs = $six_four_runs + $six_four_runs_extra;
        $nb_count = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id, -1, 0, 'sum_extra');
        $total_runs = $total_sum + $nb_count;



        $bowlerStats = $this->statisticsQuery->getBowlerDeliveriesStatisticsQuery($id);
        $dot_percentage = 0;
        $one_percentage = 0;
        $two_percentage = 0;
        $three_percentage = 0;
        $four_percentage = 0;
        $six_percentage = 0;

        $tem_percentage = 0;
        if ($bowlerStats['total_runs'] > 0) {
            $tem_percentage = ($bowlerStats['total_boundary_runs'] / $bowlerStats['total_runs']) * 100;
            $tem_percentage = round($tem_percentage);
        }

        $percentage_vs_run_type = [];

        if ($bowlerStats['total_deliveries'] > 0) {
            $dot_percentage = ($bowlerStats['total_dots'] / $bowlerStats['total_deliveries']) * 100;
            array_push($percentage_vs_run_type, [
                'percentage' => round($dot_percentage),
                'runs' => 'Dots'
            ]);

            $one_percentage = ($bowlerStats['total_ones'] / $bowlerStats['total_deliveries']) * 100;
            array_push($percentage_vs_run_type, [
                'percentage' => round($one_percentage),
                'runs' => '1s'
            ]);

            $two_percentage = ($bowlerStats['total_twos'] / $bowlerStats['total_deliveries']) * 100;
            array_push($percentage_vs_run_type, [
                'percentage' => round($two_percentage),
                'runs' => '2s'
            ]);
            $three_percentage = ($bowlerStats['total_threes'] / $bowlerStats['total_deliveries']) * 100;
            array_push($percentage_vs_run_type, [
                'percentage' => round($three_percentage),
                'runs' => '3s'
            ]);
            $four_percentage = ($bowlerStats['total_fours'] / $bowlerStats['total_deliveries']) * 100;
            array_push($percentage_vs_run_type, [
                'percentage' => round($four_percentage),
                'runs' => '4s'
            ]);
            $six_percentage = ($bowlerStats['total_sixes'] / $bowlerStats['total_deliveries']) * 100;
            array_push($percentage_vs_run_type, [
                'percentage' => round($six_percentage),
                'runs' => '6s'
            ]);
        } else {
            for ($i = 0; $i <= 6; $i++) {
                if ($i == 0) {
                    array_push($percentage_vs_run_type, [
                        'percentage' => 0,
                        'runs' => 'Dots'
                    ]);
                } else {
                    array_push($percentage_vs_run_type, [
                        'percentage' => 0,
                        'runs' => $i . 's'
                    ]);
                }
            }
        }

        $wide_ball_count = $this->statisticsQuery->getBowllerEveryDeleveryByTypeQuery($id, 'WD');
        $no_ball_count = $this->statisticsQuery->getBowllerEveryDeleveryByTypeQuery($id, 'NB');
        $total_overs = $this->statisticsQuery->getBowllerEveryDeleveryByTypeQuery($id, 'any');

        $t = $total_overs;
        if ($total_overs <= 0) {
            $t = 1;
        }
        $total_run_sum = $bowlerStats['total_runs'];
        // $total_run_sum = $this->statisticsQuery->getBowllerEveryDeleveryQuery($id,-1,0,'sum');

        return [
            'total_runs' => $total_run_sum,
            'percentage_vs_run_type' => $percentage_vs_run_type,
            'run_in_boundary_text' => $tem_percentage . '%  Runs in boundaries out of total.',
            'wide_ball_count' => $wide_ball_count,
            'no_ball_count' => $no_ball_count,
            'wide_ball_in_every_four_over' => 'Bowls ' . round(($wide_ball_count / $t) / 4) . ' wide in 4 overs',
            'no_ball_in_every_four_over' => 'Bowls ' . round(($no_ball_count / $t) / 4) . ' no balls in 4 overs',
        ];
    }


    public function batting_style_comparison($data)
    {
        // return $data;
        $first_palyer = [];
        $second_player = [];
        if (isset($data['player_1']) && isset($data['player_2'])) {
            $first_palyer =  $this->batting_style_comparison_formatted($data['player_1']);
            $second_player =  $this->batting_style_comparison_formatted($data['player_2']);
        }
        return [
            'first_player' =>  $first_palyer,
            'second_player' =>  $second_player
        ];
    }

    public function batting_style_comparison_formatted($playerId){
        $deliveries =  $this->statisticsQuery->getPlayerRunsByEveryDeleveryV2Query($playerId);
        $totalRuns = 0;
        foreach($deliveries as $delivery){
            $delivery['original_runs'] = $delivery['runs'];
            $delivery['runs'] += $totalRuns;
            $totalRuns = $delivery['runs'];
        }

        return $deliveries;
    }
}
