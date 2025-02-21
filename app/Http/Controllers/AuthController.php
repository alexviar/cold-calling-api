<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RulesPassword;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required',
            'password' => 'required',
        ]);

        if (!Auth::attempt($credentials, true)) {
            return response()->json(["message" => __("auth.failed")], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('*')->plainTextToken
        ]);
    }

    public function revokeToken(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();
        /** @var PersonalAccessToken $currentAccessToken */
        $currentAccessToken = $user->currentAccessToken();

        $currentAccessToken->delete();
    }

    public function changePassword(Request $request)
    {
        $payload = $request->validate([
            'current_password' => ['required', 'current_password'],
            'new_password' => ['required', RulesPassword::default()],
        ]);

        $request->user()->update([
            'password' => $payload['new_password']
        ]);
    }

    function forgot_password(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['status' => __($status)]);
        }
        throw ValidationException::withMessages(['email' => __($status)]);
    }

    function reset_password(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['status', __($status)]);
        }
        throw ValidationException::withMessages(['email' => __($status)]);
    }
}
