<?php

namespace App\Http\Controllers\Statistics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
 
    private $statisticsService;
    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }
    public function playerRunVsPercentageStath($id){
        return $this->statisticsService->playerRunVsPercentageStath($id);
    }
    public function bowller_position_vs_stath_without_slot($id){
        return $this->statisticsService->bowller_position_vs_stath_without_slot($id);
    }
    public function bowller_position_vs_stath_with_slot($id){
        return $this->statisticsService->bowller_position_vs_stath_with_slot($id);
    }
    public function bowller_stath_ball_by_percentage($id){
        return $this->statisticsService->bowller_stath_ball_by_percentage($id);
    }
    public function batting_position_wise_wicket($id){
        return $this->statisticsService->batting_position_wise_wicket($id);
    }
    public function bowller_type_of_runs_vs_percentages($id){
        return $this->statisticsService->bowller_type_of_runs_vs_percentages($id);
    }
    public function batting_style_comparison(Request $request){
        return $this->statisticsService->batting_style_comparison($request->all());
    }
   
    

    



    

     

    


    

}
