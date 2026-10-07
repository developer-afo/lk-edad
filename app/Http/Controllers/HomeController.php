<?php

namespace App\Http\Controllers;

use App\Models\QuizSession;
use App\Models\User;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->attributes->get('edad_user');
        $fresh = $request->boolean('new');

        $active = QuizSession::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [QuizSession::READY, QuizSession::IN_PROGRESS])
            ->latest('id')
            ->first();

        $lastDone = QuizSession::query()
            ->where('user_id', $user->id)
            ->where('status', QuizSession::DONE)
            ->latest('id')
            ->first();

        return view('home', [
            'active' => $fresh ? null : $active,
            'lastDone' => $lastDone,
            'fresh' => $fresh,
        ]);
    }
}
