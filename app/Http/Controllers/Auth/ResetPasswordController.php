<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Ellaisys\Cognito\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;



class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */


    protected string $redirectTo = '/login';


    /**
     * Handle a successful password reset response.
     *
     * @param Request $request
     * @param  string  $response
     * @return RedirectResponse
     */
    protected function sendResetResponse(Request $request, $response): RedirectResponse
    {
        // ✅ Send successfully message to login page
        return redirect($this->redirectPath())
            ->with('status', __('Your password has been reset successfully.'));
    }

    /**
     * Handle a failed password reset response.
     *
     * @param Request $request
     * @param  string  $response
     * @return RedirectResponse
     */
    protected function sendResetFailedResponse(Request $request, $response): RedirectResponse
    {
        // ✅ Send error message
        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __('Failed to reset password: ') . $response]);
    }
}
