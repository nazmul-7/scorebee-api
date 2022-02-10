<?php

namespace App\Http\Controllers\Ground;

use App\Models\Ground;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GroundQuery
{
    public function addGroundQuery($obj){
        return Ground::create($obj);
    }

    public function updateGroundInfoQuery($uid, $id, $obj){
        return Ground::where('id', $id)->where('user_id', $uid)->update($obj);
    }

    public function removeGroundQuery($uid, $id){
        return Ground::where('id', $id)->where('user_id', $uid)->delete();
    }

    public function getGroundListQuery($data){
        $lastId = isset($data['last_id'])? $data['last_id'] : '';
        $str = isset($data['str']) ? $data['str'] : '';
         $g = Ground::orderBy('id', 'desc');
            if($lastId){
                $g->where('id', '<', $lastId);
            }
            if($str){
                $g->where(function($query) use ($str){
                    $query->orWhere('ground_name' , 'like' ,"%$str%");
                });
            }
        $ground= $g->get();
         return $ground;
         
    }
}
