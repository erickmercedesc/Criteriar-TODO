<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    /**
     * Update user's push subscription.
     */
    public function update(Request $request)
    {
        $this->validate($request, [
            'endpoint'    => 'required|string',
            'keys.auth'   => 'required|string',
            'keys.p256dh' => 'required|string'
        ]);

        $endpoint = $request->endpoint;
        $token = $request->keys['auth'];
        $key = $request->keys['p256dh'];
        
        $user = auth()->user();
        $user->updatePushSubscription($endpoint, $key, $token);
        
        return response()->json(['success' => true], 200);
    }

    /**
     * Delete user's push subscription.
     */
    public function destroy(Request $request)
    {
        $this->validate($request, [
            'endpoint' => 'required|string',
        ]);

        $user = auth()->user();
        $user->deletePushSubscription($request->endpoint);

        return response()->json(['success' => true], 200);
    }
}
