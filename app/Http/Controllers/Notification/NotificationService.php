<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Player\PlayerQuery;
use App\Models\User;
use Auth;

class NotificationService
{
    private $notificationQuery;
    private $playerQuery;

    public function __construct(NotificationQuery $notificationQuery, PlayerQuery $playerQuery)
    {
        $this->notificationQuery = $notificationQuery;
        $this->playerQuery = $playerQuery;
    }

    public function notificationMethod()
    {
        return $this->notificationQuery->notificationMethodQuery();
    }

    public function createMultipleNotification($data)
    {

        $all_notification_data = [];
        foreach ($data as $value) {
            $ob = [
                'from' => isset($value['from']) ? $value['from'] : null,
                'to' => isset($value['to']) ? $value['to'] : '',
                'msg' => $data['msg'],
            ];
            if ($value['club_id']) {
                $ob['club_id'] = $value['club_id'];
            }
            if ($value['tournament_id']) {
                $ob['tournament_id'] = $value['tournament_id'];
            }
            if ($value['fixture_id']) {
                $ob['fixture_id'] = $value['fixture_id'];
            }
            if ($value['team_id']) {
                $ob['team_id'] = $value['team_id'];
            }
            array_push($all_notification_data, $ob);
        }
        $this->notificationQuery->createMultipleNotificationQuery($all_notification_data);
        return 'success';
    }

    public function createNotification($data)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $ob = [
            'from' => $user_id,
            'to' => $user_id,
            'msg' => 'some message',
        ];
        if ($data['club_id']) {
            $ob['club_id'] = $data['club_id'];
        }
        if ($data['tournament_id']) {
            $ob['tournament_id'] = $data['tournament_id'];
        }
        if ($data['fixture_id']) {
            $ob['fixture_id'] = $data['fixture_id'];
        }
        if ($data['team_id']) {
            $ob['team_id'] = $data['team_id'];
        }
        $this->notificationQuery->createNotificationQuery($ob);
    }

    public function getNotificaiton($data)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $lId = isset($data['last_id']) ? $data['last_id'] : '';
        $general = isset($data['isGeneral']) ? $data['isGeneral'] : 1;
        return $this->notificationQuery->getNotificaitonQuery($user_id, $lId, $general);
    }

    public function getNewNotificationCount()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $count = $this->notificationQuery->getNewNotificationCount($user['id']);
        $pendingRequestsNumber = 0;
        if ($user['registration_type']) {
            $pendingRequestsNumber = $this->playerQuery->getPendingRequestsNumber($user->id);
        }
        return response()->json([
            'total' => $count,
            'total_pending_requests' => $pendingRequestsNumber,
        ], 200);
    }

    public function notificationCountClose()
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $this->notificationQuery->notificationCountClose($user_id);
        return response()->json([
            'success' => true
        ], 200);
    }

    public function seenOrUnSeenSingleNotification($data)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $ob = [
            'to' => $user_id,
            'seen' => $data['seen'],
            'id' => $data['id']
        ];
        $this->notificationQuery->seenOrUnSeenSingleNotification($ob);
        return response()->json([
            'success' => true
        ], 200);
    }

    public function deleteNotification($data)
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $ob = [
            'to' => $user_id,
            'id' => $data['id']
        ];
        $this->notificationQuery->deleteNotification($ob);
        return response()->json([
            'success' => true
        ], 200);
    }

    public function seenAllNotification()
    {
        $user_id = Auth::id();
        if (!$user_id) {
            return response()->json([
                'messages' => 'You cannot perform that action.'
            ], 402);
        }
        $this->notificationQuery->seenAllNotification($user_id);
        return response()->json([
            'success' => true
        ], 200);
    }

    public function sendNotificationGlobalMethod($data)
    {
        $user = isset($data['to']) ? User::find($data['to']) : null;
        $data['app_token'] = $user->app_token;

        if (isset($data['app_token'])) {
            $this->sendPushNotificationGlobalMethod($data);
            unset($data['app_token']);
        }

        $data['title'] = 'New notification';
        return $this->notificationQuery->createNotificationQuery($data);
    }


    public function sendPushNotificationGlobalMethod($data)
    {
        $id = '';
        if (isset($data['club_id'])) {
            $id = $data['club_id'];
        } else if (isset($data['tournament_id'])) {
            $id = $data['tournament_id'];
        } else if (isset($data['team_id'])) {
            $id = $data['team_id'];
        } else if (isset($data['fixture_id'])) {
            $id = $data['fixture_id'];
        } else $id = null;
        // \Log::info($data['app_token']);
        $url = 'https://fcm.googleapis.com/fcm/send';
        $chunkIds = [];
        array_push($chunkIds, $data['app_token']);
        //        \Log::info($chunkIds);
        //        \Log::info($data);
        $url = 'https://fcm.googleapis.com/fcm/send';
        $fields = array(
            'registration_ids' => $chunkIds,
            'data' => array(
                'title' => 'New notification',
                "message" => $data['msg'],
                "id" => $id,
                "type" => $data['type'],
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                'tournament_id' => isset($data['tournament_id']) ? $data['tournament_id'] : null,
                'team_id' => isset($data['team_id']) ? $data['team_id'] : null,
                'club_id' => isset($data['club_id']) ? $data['club_id'] : null,
                'fixture_id' => isset($data['fixture_id']) ? $data['fixture_id'] : null,
            ),
            'notification' => array(
                'title' => 'New notification',
                "body" => $data['msg'],
                "sound" => true,
                "badge" => 1,
            ),
            'priority' => 'high',
            'time_to_live' => 6000,

        );
        $fields = json_encode($fields);
        //        \Log::info($fields);
        $headers = array(
            'Authorization: key=' . "AAAA__3-JSI:APA91bESlSRenRBBRmLrYfTsFC28fcaiU5LXnf-LbQb-_gifEtGO_acdtiTe-mtqlK-SSu_bqKdTBdVCltle0VqmFvaNxVuQuAUjNH8B07s-We3J3RinCc8GGZA-PvVXVC_jqGttCQyH",
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function notificationFromTrunamentOwner($data)
    {
        $players = [];
        $clubs = [];
        $app_tokens = [];
        $notification_users = [];
        $ids = [];
        if (isset($data['type']) && $data['type'] == 'both') {
            $clubs = $this->notificationQuery->getClubs($data['tournament_id']);
            // return $clubs;
            foreach ($clubs as $value) {
                array_push($ids, $value['team_id']);
                if (isset($value['tournament_team']) && isset($value['tournament_team']['turnament_club'])) {
                    array_push($notification_users, [
                        'from' => null,
                        'to' => $value['tournament_team']['turnament_club']['id'],
                        'msg' => $data['msg'],
                        'type' => 'tournament_notification',
                        'tournament_id' => $data['tournament_id'],
                        'title' => $data['title']
                    ]);
                    if (isset($value['tournament_team']['turnament_club']['app_token'])) {

                        if ($value['tournament_team']['turnament_club']['app_token'])
                            array_push($app_tokens, $value['tournament_team']['turnament_club']['app_token']);
                    }
                }
            }
            $players = $this->notificationQuery->getplayers($ids);
            foreach ($players as $value) {
                if (isset($value['tema_player']) && isset($value['tema_player'])) {
                    array_push($notification_users, [
                        'from' => null,
                        'to' => $value['tema_player']['id'],
                        'msg' => $data['msg'],
                        'type' => 'tournament_notification',
                        'tournament_id' => $data['tournament_id'],
                        'title' => $data['title']
                    ]);
                }
                if (isset($value['tema_player']) && isset($value['tema_player']['app_token'])) {
                    if ($value['tema_player']['app_token'])
                        array_push($app_tokens, $value['tema_player']['app_token']);
                }
            }
        } else if (isset($data['type']) && $data['type'] == 'club') {
            $clubs = $this->notificationQuery->getClubs($data['tournament_id']);
            foreach ($clubs as $value) {
                array_push($ids, $value['team_id']);
                if (isset($value['tournament_team']) && isset($value['tournament_team']['turnament_club'])) {
                    array_push($notification_users, [
                        'from' => null,
                        'to' => $value['tema_player']['id'],
                        'msg' => $data['msg'],
                        'type' => 'tournament_notification',
                        'tournament_id' => $data['tournament_id'],
                        'title' => $data['title']
                    ]);
                }
                if (isset($value['tournament_team']['turnament_club']['app_token'])) {

                    if ($value['tournament_team']['turnament_club']['app_token'])
                        array_push($app_tokens, $value['tournament_team']['turnament_club']['app_token']);
                }
            }
        } else if (isset($data['type']) && $data['type'] == 'player') {
            $clubs = $this->notificationQuery->getClubs($data['tournament_id']);
            foreach ($clubs as $value) {
                array_push($ids, $value['team_id']);
            }
            $players = $this->notificationQuery->getplayers($ids);
            foreach ($players as $value) {
                if (isset($value['tema_player'])) {
                    array_push($notification_users, [
                        'from' => null,
                        'to' => $value['tema_player']['id'],
                        'msg' => $data['msg'],
                        'type' => 'tournament_notification',
                        'tournament_id' => $data['tournament_id'],
                        'title' => $data['title']
                    ]);
                }
                if (isset($value['tema_player']['app_token'])) {
                    if ($value['tema_player']['app_token'])
                        array_push($app_tokens, $value['tema_player']['app_token']);
                }
            }
        }
        // return $notification_users;

        if (sizeof($notification_users) > 0) {
            $this->notificationQuery->createMultipleNotificationQuery($notification_users);
        }
        if (sizeof($app_tokens) > 0) {
            $ob = [
                'app_tokens' => $app_tokens,
                'msg' => $data['msg'],
                'title' => $data['title'],
                'type' => 'tournament_notification',
                'id' => $data['tournament_id'],
            ];
            $this->sendTournamentNotifications($ob);
        }
        return response()->json([
            'success' => true,
        ], 200);
    }


    public function sendTournamentNotifications($data)
    {
        //firebase
        $url = 'https://fcm.googleapis.com/fcm/send';
        if (sizeof($data['app_tokens']) == 0) return 0;
        $allIds = array_chunk($data['app_tokens'], 500);
        // foreach ($allIds as $chunkIds) {
        $fields = array(
            'registration_ids' => $data['app_tokens'],
            'data' => array(
                'title' => $data['title'],
                "message" => $data['msg'],
                "id" => $data['id'],
                "type" => $data['type'],
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ),
            'notification' => array(
                'title' => $data['title'],
                "body" => $data['msg'],
                "sound" => true,
                "badge" => 1,
            ),
            'priority' => 'high',
            'time_to_live' => 6000,

        );
        $fields = json_encode($fields);

        $headers = array(
            'Authorization: key=' . "AAAAmpcwba4:APA91bEpwJeKy9lhVwh1YDmqEfwawZBKKWFTEZI7sdas1Eq89VbtVoWmqsiyhp5lO8EmrTl9ms6uY0wSkZTvC_F9kGi63PppU2cp5Zcf9INK08gANoSd_EhrJ2ORvyNVmfOIcgCmhhMe",
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);

        // }
        return response()->json([
            'success' => true,
        ], 200);
    }

    public function notificationFromClubOwner($data)
    {
        $ids = [];
        $app_tokens = [];
        $notification_users = [];
        array_push($ids, $data['club_id']);
        $players = $this->notificationQuery->getplayers($ids);
        foreach ($players as $value) {
            if (isset($value['tema_player'])) {
                array_push($notification_users, [
                    'from' => null,
                    'to' => $value['tema_player']['id'],
                    'msg' => $data['msg'],
                    'type' => 'club_notification',
                    'club_id' => $data['club_id'],
                    'title' => $data['title']
                ]);
            }
            if (isset($value['tema_player']) && isset($value['tema_player']['app_token'])) {
                if ($value['tema_player']['app_token'])
                    array_push($app_tokens, $value['tema_player']['app_token']);
            }
        }
        if (sizeof($app_tokens) > 0) {
            $ob = [
                'app_tokens' => $app_tokens,
                'msg' => $data['msg'],
                'title' => $data['title'],
                'type' => 'club_notification',
                'id' => $data['club_id'],
            ];
            return $this->sendTournamentNotifications($ob);
        }
        if (sizeof($notification_users) > 0) {

            $this->notificationQuery->createNotificationInsertQuery($notification_users);
        }
        return response()->json([
            'success' => true,
        ], 200);
    }


    public function test_pushNotis()
    {
        $url = 'https://fcm.googleapis.com/fcm/send';
        $chunkIds = ['e6JWyfUgR9ajk7-HvinNVi:APA91bEGYQ414RMT5IQ9hZkZ54EmHUhmaK5L6pOjMb3-tZsPmjQmkIzN3LzTGkdalVREkJ6aPEu97LDmjiHc-AcJGbjj7DhLp7P47ymbRGKpi7eMuJ2rLZ0UuHjNsDhITQ7HgIASJa6p'];
        $fields = array(
            'registration_ids' => $chunkIds,
            'data' => array(
                'title' => 'ttttt notification',
                "message" => 'hello test',
                'tournament_id' => isset($data['tournament_id']) ? $data['tournament_id'] : null,
                'team_id' => isset($data['team_id']) ? $data['team_id'] : null,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ),
            'notification' => array(
                'title' => 'ttttt notification',
                "body" => 'hello test',
                "sound" => true,
                "badge" => 1,
            ),
            'priority' => 'high',
            'time_to_live' => 6000,

        );
        $fields = json_encode($fields);

        $headers = array(
            'Authorization: key=' . "AAAA__3-JSI:APA91bESlSRenRBBRmLrYfTsFC28fcaiU5LXnf-LbQb-_gifEtGO_acdtiTe-mtqlK-SSu_bqKdTBdVCltle0VqmFvaNxVuQuAUjNH8B07s-We3J3RinCc8GGZA-PvVXVC_jqGttCQyH",
            'Content-Type: application/json'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
