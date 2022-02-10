<?php

namespace App\Http\Controllers\Notification;
use App\Models\User;
use App\Models\TournamentTeam;
use App\Models\TeamPlayer;
use App\Models\Notification;

class NotificationQuery
{
    public function notificationMethodQuery(){
        return 'I am returning from query class...';
    }

    public function createMultipleNotificationQuery($data){
        return  Notification::insert($data);

     }
     public function createNotificationQuery($data){
        return  Notification::create($data);

     }
     public function createNotificationInsertQuery($data){
      return  Notification::create($data);

   }
     public function getNotificaitonQuery($id,$lId,$general){
         $q= Notification::where('to', $id)->where('isGeneral',$general);
         if($lId){
             $q->where('id','<', $lId);
         }
        return $q->orderBy('id','desc')->limit(10)->get();

      }
      public function getNewNotificationCount($id){
         return Notification::where('to', $id)->where('count',1)->count();
      }
      public function notificationCountClose($id){
         return Notification::where('to', $id)->update([
             'count'=>0
         ]);
      }
      public function seenAllNotification($id){
         return Notification::where('to', $id)->update([
             'seen'=>1
         ]);
      }

      public function deleteNotification($data){
         return Notification::where('id', $data['id'])->where('to', $data['to'])->delete();
      }
      public function seenOrUnSeenSingleNotification($data){
        return Notification::where('id', $data['id'])->where('to', $data['to'])->update($data);
     }
     public function getClubs($tId){
        return TournamentTeam::select('id','tournament_id','team_id')->where('tournament_id',$tId)->
        with('tournament_team.turnament_club')->get();
     }
     public function getplayers($ids){
      return TeamPlayer::select('id','team_id','player_id')
         ->whereIn('team_id',$ids)->with('tema_player')->get();
   }

}
