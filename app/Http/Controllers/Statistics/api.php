
<?php
// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY... 
// ROUTE FILES ARE NOT REQUIRED TO IMPORT ANYWHRE.. ITS ADDED AUTOMATICALLY...

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Statistics\StatisticsController;


Route::prefix('api/statistics')->group(function () {
    Route::get('/playerRunVsPercentageStath/{player_id}',  [StatisticsController::class, 'playerRunVsPercentageStath']);
    Route::get('/bowller_position_vs_stath_without_slot/{player_id}',  [StatisticsController::class, 'bowller_position_vs_stath_without_slot']);
    Route::get('/bowller_position_vs_stath_with_slot/{player_id}',  [StatisticsController::class, 'bowller_position_vs_stath_with_slot']);
    Route::get('/bowller_stath_ball_by_percentage/{player_id}',  [StatisticsController::class, 'bowller_stath_ball_by_percentage']);
    Route::get('/batting_position_wise_wicket/{player_id}',  [StatisticsController::class, 'batting_position_wise_wicket']);
    Route::get('/bowller_type_of_runs_vs_percentages/{player_id}',  [StatisticsController::class, 'bowller_type_of_runs_vs_percentages']);
    Route::get('/batting_style_comparison',  [StatisticsController::class, 'batting_style_comparison']);
});




// bowller
// bowller
