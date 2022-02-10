<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public static $wrap = null;
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'test_match_run' => (int)$this->test_match_run,
            'limited_match_run' => (int)$this->limited_match_run,
            'faced_test_ball' => (int)$this->faced_test_ball,
            'faced_limited_ball' => (int)$this->faced_limited_ball,
            'not_out_in_test' => (int)$this->not_out_in_test,
            'not_out_in_limited' => (int)$this->not_out_in_limited,
            'ducks_in_limited' => (int)$this->ducks_in_limited,
            'ducks_in_test' => (int)$this->ducks_in_test,
            'test_match' => (int)$this->test_match,
            'limited_match' => (int)$this->limited_match,
            'test_match_innings' => (int)$this->test_match_innings,
            'limited_match_innings' => (int)$this->limited_match_innings,
            'total_test_sixes' => (int)$this->total_test_sixes,
            'total_limited_match_sixes' => (int)$this->total_limited_match_sixes,
            'total_test_fours' => (int)$this->total_test_fours,
            'total_limited_match_fours' => (int)$this->total_limited_match_fours,
            // 'test_avg_run' => round((int)$this->test_match_run/(int)$this->test_match, 0),
            // 'limited_avg_run' => round((int)$this->limited_match_run/(int)$this->limited_match, 0),
            'test_match_hundred' => (int)$this->test_match_hundred,
            'limited_match_hundred' => (int)$this->limited_match_hundred,
            'test_match_fifty' => (int)$this->test_match_fifty,
            'limited_match_fifty' => (int)$this->limited_match_fifty,
            'test_match_thirty' => (int)$this->test_match_thirty,
            'limited_match_thirty' => (int)$this->limited_match_thirty
        ];
    }


}
