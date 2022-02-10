<?php

namespace App\Http\Controllers\Ground;
use Illuminate\Support\Facades\Auth;

class GroundService
{
    private $groundQuery;
    public function __construct(GroundQuery $groundQuery)
    {
        $this->groundQuery = $groundQuery;
    }

    public function addGround($data){
        $data['user_id'] = Auth::id();
        return $this->groundQuery->addGroundQuery($data);
    }

    public function updateGroundInfo($data){
        $gId = $data['ground_id'];
        unset($data['ground_id']);
        return $this->groundQuery->updateGroundInfoQuery(Auth::id(), $gId, $data);
    }

    public function removeGround($data){
        return $this->groundQuery->removeGroundQuery(Auth::id(), $data['ground_id']);
    }

    public function getGroundList($data){
        return $this->groundQuery->getGroundListQuery($data);
    }

}
