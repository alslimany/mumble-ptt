<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;

class WebSocketController extends Controller
{
    /**
     * Authenticate a device for a private broadcasting channel.
     *
     * Devices send their JWT token as a Bearer token. The `auth:api` middleware
     * (JWT guard) resolves the Device model, which is then used by the channel
     * authorisation callbacks defined in routes/channels.php.
     */
    public function auth(Request $request)
    {
        return Broadcast::auth($request);
    }
}
