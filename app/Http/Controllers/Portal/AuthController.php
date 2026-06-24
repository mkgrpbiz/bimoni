<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(string $token)
    {
        $agent = \App\Models\Agent::where('access_token', $token)->firstOrFail();
        session(['portal_agent_id' => $agent->id]);
        return redirect()->route('portal.users');
    }
}
