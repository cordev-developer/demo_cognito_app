<?php

use Ellaisys\Cognito\AwsCognitoClient;

return [

    /*
    |--------------------------------------------------------------------------
    | Cognito Fields & DB Mapping
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito fields that shall be needed to be
    | updated. The array value is a mapping with DB model or Request data.
    |
    | DO NOT change the parameters on the left side of the array. They map to
    | the AWS Cognito User Pool fields.
    |
    | The right side of the array is the DB model field, and you can set the
    | value to null if you do not want to update the field.
    |
    */
    /*'cognito_user_fields'   => [
        'name' => 'name',
        'given_name' => null,
        'middle_name' => null,
        'family_name' => null,
        'nickname' => null,
        'preferred_username' => null,
        'email' => 'email', //Do Not set this parameter to null
        'phone_number' => 'phone_number',
        'gender' => null,
        'birthdate' => null,
        'locale' => null
    ],*/
    'cognito_user_fields'   => [
        'email' => 'email', //Do Not set this parameter to null
        'phone_number' => 'phone_number',
        'given_name' => null,
        'middle_name' => null,
        'family_name' => null,
        'nickname' => null,
        'preferred_username' => null,
        'gender' => null,
        'birthdate' => null,
        'locale' => null
    ],


    /*
    |--------------------------------------------------------------------------
    | Cognito Subject UUID
    |--------------------------------------------------------------------------
    |
    | This option controls the default cognito subject UUID that shall be needed
    | to be updated based on your local DB schema. This value is the attribute
    | in the local DB Model that maps with Cognito user subject UUID.
    |
    */
    //'user_subject_uuid' => env('AWS_COGNITO_USER_SUBJECT_UUID', 'sub'),

    /*
    |--------------------------------------------------------------------------
    | Set the parameters for the new user message action
    |--------------------------------------------------------------------------
    |
    | This option enables the new user message action. You can set the value to
    | SUPPRESS in order to stop the invitation mails from being sent. The default
    | value is set to null.
    |
    */
    'new_user_message_action' => env('AWS_COGNITO_NEW_USER_MESSAGE_ACTION', null),


];
