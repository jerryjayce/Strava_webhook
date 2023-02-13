<?php

namespace App\Http\Controllers;

use App\Models\Orders;
use App\Models\Users;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StravaActivities extends Controller
{
    public function subscribeEndpoint(Request $request)
    {
        //Strava calls this endpoint to validate the callback url subscribed
        //return a http response code 200 to validate endpoint when subscribing to strava events

        $VERIFY_TOKEN = "STRAVA";
        $mode = $request->input("hub_mode");
        $verify_token = $request->input("hub_verify_token");
        $challenge = $request->input("hub_challenge");


        if (empty($mode) || empty($verify_token)) {
            return response('hub.mode and hub.verify_token should not be empty', 401);
        }

        if ($mode != 'subscribe' && $verify_token != $VERIFY_TOKEN) {
            return response('validation failed', 401);
        }

        //subscribe
        return response()->json([
            'hub.challenge' => $challenge,
        ]);

    }

    public function captureActivity(Request $request)
    {

        //TODO basic validation
        $event = $request->all();


        if (!array_key_exists('object_type', $event) || $event['object_type'] != 'activity') {
            return response('Invalid event', 422);
        }

        if (!array_key_exists('aspect_type', $event) || ($event['aspect_type'] != 'create' && $event['aspect_type'] != 'update')) {
            return response('Invalid Event', 422);
        }

        if (!array_key_exists('updates', $event) || empty($event['updates'])) {
            return response('Invalid Event', 422);
        }

        if (!array_key_exists('object_id', $event) || empty($event['object_id'])) {
            return response('Invalid Event', 422);
        }


        $changes = $event['updates'];
        $schema = [
            "title" => "",
            "name" => "",
            "distance" => "",
            "moving_time" => "",
            "elapsed_time" => "",
            "type" => "",
            "sport_type" => "",
            "start_date" => "",
        ];

        $update = [];

        foreach ($changes as $activity => $value) {
            if (array_key_exists($activity, $schema)) {
                $update[$activity] = $value;
            }
        }

        DB::table('activities')
            ->updateOrInsert(
                ['object_id' => $event['object_id']],
                $update
            );

        return response()->json(
            $update,
        );
    }
}
