<?php

namespace App\Http\Controllers;

use Auth;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Auth\RegisterMFA;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Illuminate\Routing\Controller as BaseController;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
//            return back()
//                ->with('user', $userCognito->toArray())
//                ->with('actionActivateMFA', $response);
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionMFA', [
                    'status' => $status,
                    'message' => $status ? 'MFA activado correctamente' : 'No se pudo activar el MFA'
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

            $status = isset($response['@metadata']['statusCode']) && $response['@metadata']['statusCode'] == 200;

            //Return status to screen
//            return back()
//                ->with('user', $userCognito->toArray())
//                ->with('actionDeactivateMFA', $response);

            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionMFA', [
                    'status' => $status,
                    'message' => $status ? 'MFA deshabilitado correctamente' : 'No se pudo deshabilitar el MFA'
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
//            return back()
//                ->with('user', $userCognito->toArray())
//                ->with('actionEnableMFA', [
//                    'message' => $response
//                ]);
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionMFA', [
                    'status' => $status,
                    'message' => $status ? 'MFA activado correctamente' : 'No se pudo activar el MFA',
                    'type' => 'activate'  // <-- añadimos tipo
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
//            return back()
//                ->with('user', $userCognito->toArray())
//                ->with('actionDisableMFA', [
//                    'status' => $response['@metadata']['statusCode']==200
//                ]);
            return back()
                ->with('user', $userCognito->toArray())
                ->with('actionMFA', [
                    'status' => $status,
                    'message' => $status ? 'MFA desactivado correctamente' : 'No se pudo desactivar el MFA',
                    'type' => 'deactivate'  // <-- añadimos tipo
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
