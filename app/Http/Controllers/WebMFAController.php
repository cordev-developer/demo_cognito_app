<?php

namespace App\Http\Controllers;

//use Auth;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;

use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Auth\RegisterMFA;
use Illuminate\Routing\Controller as BaseController;

use Exception;


class WebMFAController extends BaseController
{
    use AuthenticatesUsers;
    use RegisterMFA;

    /**
     * Action to activate MFA for the
     */
    public function actionActivateMFA()
    {
        try
        {
            $user = auth()->guard('web')->user();
            $response = $this->activateMFA();
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            $status = isset($response['@metadata']['statusCode']) && $response['@metadata']['statusCode'] == 200;

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionSmsMFA', [
                    'status' => $status,
                    'message' => 'MFA Google activado correctamente',
                    'type' => 'activate',  // <-- Add type
                    'response' => $response
                ]);

        } catch(Exception $e) {
            $message = 'Error activating the MFA.';
            if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
                $message = $e->getAwsErrorMessage();
            } else {
                //Do nothing
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Action to deactivate MFA for the
     */
    public function actionDeactivateMFA()
    {
        try
        {
            $user = auth()->guard('web')->user();
            $response = $this->deactivateMFA();
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionSmsMFA', [
                    'message' => 'MFA Google desactivado correctamente',
                    'type' => 'deactivate'  // <-- Add type
                ]);

        } catch(Exception $e) {
            $message = 'Error activating the MFA.';
            if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
                $message = $e->getAwsErrorMessage();
            } else {
                //Do nothing
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Action to enable MFA for the user
     */
    public function actionEnableMFA()
    {
        try
        {
            $user = auth()->guard('web')->user();
            $response = $this->enableMFA('web', $user->email);
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            $status = isset($response['@metadata']['statusCode']) && $response['@metadata']['statusCode'] == 200;

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionMFA', [
                    'status' => $status,
                    'message' => 'MFA activado correctamente',
                    //'message' => $response,
                    'type' => 'activate'  // <-- Add type
                ]);
        } catch(Exception $e) {
            $message = 'Error activating the MFA.';
            if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
                $message = $e->getAwsErrorMessage();
            } else {
                //Do nothing
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Action to disable MFA for the user
     */
    public function actionDisableMFA()
    {
        try
        {
            $user = auth()->guard('web')->user();
            $response = $this->disableMFA('web', $user->email);
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            $status = isset($response['@metadata']['statusCode']) && $response['@metadata']['statusCode'] == 200;

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionMFA', [
                    'status' => $status,
                    'message' => 'MFA desactivado correctamente',
                    'type' => 'deactivate'  // <-- Add type
                ]);

        } catch(Exception $e) {
            $message = 'Error activating the MFA.';
            if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
                $message = $e->getAwsErrorMessage();
            } else {
                //Do nothing
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Verify the MFA user code
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function actionVerifyMFA(Request $request)
    {
        try
        {
            $code = $request['code'];
            $deviceName = $request['device_name'];

            $user = auth()->guard('web')->user();
            $response = $this->verifyMFA('web', $code, $deviceName);
            $userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            //Return status to screen
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionVerifyMFA', [
                    'status' => true
                ]);
        } catch(Exception $e) {
            $message = 'Error activating the MFA.';
            if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
                $message = $e->getAwsErrorMessage();
            } else {
                $message = $e->getMessage();
            } //End if

            return back()
                ->with('actionVerifyMFA', $message);
        } //Try-catch ends
    } //Function ends


    /**
     * Authenticate using the MFA code using the Web console
     */
    public function actionValidateMFA(Request $request)
    {
        try
        {
            //Create credentials object
            $collection = collect($request->all());

            //Authenticate the user request
            $response = $this->attemptLoginMFA($request);
            if ($response===true) {
                $request->session()->regenerate();
                return redirect(route('home'));
            } else if ($response===false) {
                return redirect()
                    ->back()
                    ->withInput($request->only('username', 'remember'))
                    ->withErrors([
                        'username' => 'Incorrect username and/or password !!',
                    ]);
            } else {
                return $response;
            } //End if
        } catch (Exception $e) {
            Log::error($e->getMessage());
            $response = $this->sendFailedLoginResponse($collection, $e);
            return $response->back()->withInput($request->only('username', 'remember'));
        } //try-catch ends
    } //Function ends

} //Class ends
