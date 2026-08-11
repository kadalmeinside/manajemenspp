<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class PushSubscriptionController extends Controller
{
    /**
     * Create or update a push subscription.
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'endpoint' => 'required|string',
            'keys.auth' => 'required|string',
            'keys.p256dh' => 'required|string',
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];

        $user = $request->user();
        if ($user) {
            $user->updatePushSubscription($endpoint, $key, $token);
            Log::info("Web push subscription updated for user {$user->id}");
        }

        return response()->json(['success' => true], 200);
    }

    /**
     * Delete a push subscription.
     */
    public function destroy(Request $request)
    {
        $this->validate($request, ['endpoint' => 'required|string']);

        $user = $request->user();
        if ($user) {
            $user->deletePushSubscription($request->endpoint);
            Log::info("Web push subscription deleted for user {$user->id}");
        }

        return response()->json(['success' => true], 200);
    }
}
