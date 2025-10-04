<?php

use App\Http\Controllers\Auth\ConfirmPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

#Auth::routes();

Route::get('/hello', function () {
    return view('hello');
});

/**
 * Routes added below to manage the AWS Cognito change in case you are
 * using Laravel Scafolling
 */

Route::get('/login', function () { return view('auth.login'); })->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::get('/login/mfa', function () { return view('auth.login_mfa_code'); })->name('cognito.form.mfa.code');
Route::post('/login/mfa', [App\Http\Controllers\WebMFAController::class, 'actionValidateMFA'])->name('cognito.form.mfa.code');
Route::get('/register', function () { return view('auth.register'); })->name('register');
Route::post('/register', [UserController::class, 'webRegister'])->name('register');
Route::get('/password/forgot', function () { return view('auth.passwords.email'); })->name('password.request');

// New routes, instead of using Auth:routes() we will use the same routes explicitly
Route::get('/password/reset', function () { return view('auth.passwords.reset'); })->name('cognito.form.reset.password.code');
Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update');
Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
Route::get('password/email', function () { return view('auth.passwords.email'); })->name('password.email');
Route::post('/password/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('password.email');
Route::get('password/confirm', [ConfirmPasswordController::class, 'showConfirmForm'])->name('password.confirm');
Route::post('password/confirm', [ConfirmPasswordController::class, 'confirm']);



 // Routes with middleware
Route::middleware('aws-cognito')->get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::middleware('aws-cognito')->get('/password/change', function () { return view('auth.passwords.change'); })->name('cognito.form.change.password');
Route::middleware('aws-cognito')->post('/password/change', [App\Http\Controllers\Auth\ChangePasswordController::class, 'actionChangePassword'])->name('cognito.action.change.password');

Route::middleware('aws-cognito')->get('/mfa/enable', [App\Http\Controllers\WebMFAController::class, 'actionEnableMFA'])->name('cognito.action.mfa.enable');
Route::middleware('aws-cognito')->get('/mfa/disable', [App\Http\Controllers\WebMFAController::class, 'actionDisableMFA'])->name('cognito.action.mfa.disable');
Route::middleware('aws-cognito')->get('/mfa/activate', [App\Http\Controllers\WebMFAController::class, 'actionActivateMFA'])->name('cognito.action.mfa.activate');
Route::middleware('aws-cognito')->post('/mfa/verify', [App\Http\Controllers\WebMFAController::class, 'actionVerifyMFA'])->name('cognito.action.mfa.verify');

Route::middleware('aws-cognito')->any('logout', function (\Illuminate\Http\Request $request) {
    Auth::guard()->logout();
    return redirect('/');
})->name('logout');
Route::middleware('aws-cognito')->any('logout/forced', function (\Illuminate\Http\Request $request) {
    Auth::guard()->logout(true);
    return redirect('/');
})->name('logout_forced');
