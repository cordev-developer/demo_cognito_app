<?php

namespace App\Http\Controllers\User;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

use App\Models\User;
use Ellaisys\Cognito\Auth\RegistersUsers;
use Ellaisys\Cognito\Auth\SendsPasswordResetEmails;
use Ellaisys\Cognito\Auth\ResetsPasswords;
use Illuminate\Support\Collection;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

use Illuminate\Routing\Controller as BaseController;

use Exception;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class UserController extends BaseController
{
    use RegistersUsers;
    use SendsPasswordResetEmails;
    use ResetsPasswords;

    /*
    public function webRegister(Request $request)
    {
        $cognitoRegistered=false;

        $validator = $request->validate([
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'email' => 'required|email|max:64|unique:users',
            'password' => 'required|confirmed|min:6|max:64',
        ]);

        //User::create($request->only('name', 'email', 'password'));
        $data = $request->only('first_name', 'email', 'password');
        $data['name'] = $data['first_name'];
        unset($data['first_name']);

        //Create credentials object
        $collection = collect($request->all());
        Log::info($collection);

        //Register User in Cognito
        $cognitoRegistered=$this->createCognitoUser($collection);
        if ($cognitoRegistered==true) {
            unset($data['password']);
            User::create($data);

            //Send to login page
            return view('auth.login');
        } else {
            return redirect()->back()
            ->withInput()
            ->with('status', 'error')
            ->with('message', 'The given email exists')
            ->withErrors(['email' => 'The email has already been taken.']);
        } //End if
    } //Function ends
    */

    public function webRegister(Request $request)
    {
        $cognitoRegistered = false;

        // 1. Validate input fields
        $validator = $request->validate([
            'name'                  => 'required|string|max:50',
            'email'                 => 'required|string|email|max:64|unique:users',
//            'phone'                 => 'required|string|min:8|max:20',
            'password'              => 'required|string|min:8|max:64|confirmed',
            'password_confirmation' => 'required|string|min:8|max:64',
        ]);

        // 2. Prepare user data for local database
        $data['name'] = $request->get('name');
        $data['email'] = $request->get('email');
//        $data['phone_number'] = $request->get('phone');


        // 🚨 Do NOT save the raw password locally
        // If you want to store users in the DB, make sure to hash it first:
        $data['password'] = bcrypt($request->get('password'));

        // 3. Create a Collection for Cognito registration
        $collection = collect($data);
        $collection['password'] = $request->get('password');


        Log::info('User registration data received', $collection->toArray());

        // 4. Try to register user in AWS Cognito
        $cognitoRegistered = $this->createUser($collection);

        if ($cognitoRegistered) {
            // Save user locally (only if needed for your app)
            unset($data['password']);
//            unset($data['phone_number']);
            //unset($data['password']);
            $data['name'] = $request->get('name');


            // Check the user 'sub' attribute
            if (isset($cognitoRegistered['UserAttributes'])) {
                foreach ($cognitoRegistered['UserAttributes'] as $attribute) {
                    if ($attribute['Name'] == 'sub') {
                        $data['sub'] = $attribute['Value']; // Assign the value to $data['sub']

                    }
                }
            }

            // Register user in DB
            User::create($data);

            //Send to login page
            return view('auth.login');

            // Redirect to login page with success message
            //return redirect()->route('login')->with('status', 'success')->with('message', 'User registered successfully. Please verify your email or phone number to activate your account.');
        } else {
            // If Cognito fails (e.g., duplicate email or username)
            return redirect()->back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'The user already exists in Cognito')
                ->withErrors(['email' => 'The email is already taken.']);
        }
    }

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Support\Collection  $request
     * @return \Illuminate\Http\Response
     * @throws InvalidUserFieldException
     */
    public function createUser(Collection $request, array $clientMetadata=null, string $groupname=null)
    {
        //Initialize Cognito Attribute array
        $attributes = [];

        //Get the configuration for new user invitation message action.
        $messageAction = config('cognito.new_user_message_action', null);

        //Get the registration fields
        $userFields = config('cognito.cognito_user_fields');

        //Iterate the fields
        foreach ($userFields as $key => $userField) {
            if ($userField!=null) {
                if ($request->has($userField)) {
                    $attributes[$key] = $request->get($userField);
                } else {
                    Log::error('RegistersUsers:createCognitoUser:InvalidUserFieldException');
                    Log::error("The configured user field {$userField} is not provided in the request.");
                    throw new InvalidUserFieldException("The configured user field {$userField} is not provided in the request.");
                } //End if
            } //End if
        } //Loop ends

        //Register the user in Cognito
        $userKey = $request->has('username')?'username':'email';

        //Password parameter
        $password = null;
        if (config('cognito.force_new_user_password', true)) {
            $password = $request->has($this->paramPassword) ? $request[$this->paramPassword] : null;
        }

//        app()->make(AwsCognitoClient::class)->register(
//            //$request[$userKey], $password, $attributes
//            $request->get('name'), $password, $attributes
//        );

        app()->make(AwsCognitoClient::class)->inviteUser(
            $request->get('name'), $password, $attributes,
            $clientMetadata, $messageAction,
            $groupname
        );

        return app()->make(AwsCognitoClient::class)->getUser($request['name']);

    } //Function ends


    public function sendPasswordResetEmail(Request $request)
    {
        //Method with SendsPasswordResetEmails trait
        if ($this->sendCognitoResetLinkEmail($request['email'])) {
            //Send to reset page
            return view('auth.passwords.reset');
        } //End if
    }


    public function actionResetPasswordCode(Request $request)
    {
        return $this->reset($request);
    }

} //Class ends
