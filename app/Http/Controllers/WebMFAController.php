<?php

namespace App\Http\Controllers;

//use Auth;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Ellaisys\Cognito\Guards\CognitoSessionGuard;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Collection;
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
     * Action to activate Google Authenticator MFA
     * @throws ValidationException
     */
    public function actionActivateMFA(): \Illuminate\Http\RedirectResponse
    {
        try
        {
            $user = auth()->guard('web')->user();

            // Cognito guard definition (Type Hint)
            /** @var CognitoSessionGuard $cognitoGuard */
            $cognitoGuard = auth()->guard('web');
            $response = $this->activateMFA();

            $userCognito = $cognitoGuard->getRemoteUserData($user->email);

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
            }
//            else {
//                //Do nothing
//            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Action to deactivate MFA Google Authenticator
     * @throws ValidationException
     */
    public function actionDeactivateMFA(): \Illuminate\Http\RedirectResponse
    {
        try
        {
            $user = auth()->guard('web')->user();

            // Cognito guard definition (Type Hint)
            /** @var CognitoSessionGuard $cognitoGuard */
            $cognitoGuard = auth()->guard('web');
            $response = $this->deactivateMFA();

            $userCognito = $cognitoGuard->getRemoteUserData($user->email);

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
            }
//            else {
//                //Do nothing
//            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Action to enable SMS MFA
     * @throws ValidationException
     */
    public function actionEnableMFA(): \Illuminate\Http\RedirectResponse
    {
        try
        {
            $user = auth()->guard('web')->user();

            // Cognito guard definition (Type Hint)
            /** @var CognitoSessionGuard $cognitoGuard */
            $cognitoGuard = auth()->guard('web');
            $response = $this->enableMFA('web', $user->email);

            // Using typed guard to call getRemoteUserData()
            $userCognito = $cognitoGuard->getRemoteUserData($user->email);
            //$userCognito = auth()->guard('web')->getRemoteUserData($user->email);

            // Add this bloc bellow to disable warning in line 112
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $response = $response->getData(true); // getData(true) to convert json object into array
            }

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
            }
//            else {
//                //Do nothing
//            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Action to disable SMS MFA
     * @throws ValidationException
     */
    public function actionDisableMFA(): \Illuminate\Http\RedirectResponse
    {
        try
        {
            $user = auth()->guard('web')->user();
            // Cognito guard definition (Type Hint)
            /** @var CognitoSessionGuard $cognitoGuard */
            $cognitoGuard = auth()->guard('web');
            $response = $this->disableMFA('web', $user->email);

            // Using typed guard to call getRemoteUserData()
            $userCognito = $cognitoGuard->getRemoteUserData($user->email);

            // Add this bloc bellow to disable warning in line 112
            if ($response instanceof \Illuminate\Http\JsonResponse) {
                $response = $response->getData(true); // getData(true) to convert json object into array
            }

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
            }
//            else {
//                //Do nothing
//            } //End if

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
            // Cognito guard definition (Type Hint)
            /** @var CognitoSessionGuard $cognitoGuard */
            $cognitoGuard = auth()->guard('web');

            $response = $this->verifyMFA('web', $code, $deviceName);

            // Using typed guard to call getRemoteUserData()
            $userCognito = $cognitoGuard->getRemoteUserData($user->email);

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
     * @param \Illuminate\Http\Request $request
     * @return RedirectResponse|Redirector|mixed
     * @throws ValidationException
     */
    public function actionValidateMFA(\Illuminate\Http\Request $request): mixed
    {
        $collection = null;

        try
        {
            //Create credentials object
            $collection = collect($request->all());

            //Authenticate the user request
            $response = $this->attemptLoginMFA($collection);
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
            return back()->withInput($request->only('username', 'remember'));
        } //try-catch ends
    } //Function ends

} //Class ends
