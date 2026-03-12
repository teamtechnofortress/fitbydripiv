<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\API\BaseController as BaseController;

class AuthController extends BaseController
{
    private $defaultPassword = "12345678";
    //
    public function signin(Request $request)
    {
        // Validate email and password
        $validator = Validator::make($request->all(), [
            'email'    => ['required', 'email',
                function ($attribute, $value, $fail) {
                    if (!DB::table('users')->where('email', $value)->where(['deleted' => 0, 'status' => 1])->exists()) {
                        $fail('The selected email is not valid or the user is not active.');
                    }
                },
            ],
            'password' => 'required|string|min:8',
        ]);

        if($validator->fails()){
            Log::warning('Auth validation failed', [
                'email' => $request->email,
                'errors' => $validator->errors()->toArray(),
                'ip' => $request->ip(),
            ]);
            return $this->sendError('Error validation', $validator->errors(), 400);
        }

        Log::info('Auth login attempt', [
            'email' => $request->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Attempt login
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            Log::warning('Auth login failed', [
                'email' => $request->email,
                'ip' => $request->ip(),
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password',
            ], 401);
        }

        $user = Auth::user();

        if ($user->two_factor_confirmed_at) {
            Auth::logout();
            Log::info('Auth login requires 2FA', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return response()->json([
                'requires_2fa' => true,
                'email' => $user->email,
            ]);
        }

        $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;
        $success['user'] = $user;
        $success['userAbilities'][] = [
            'action' => 'manage',
            'subject' => 'all'
        ];

        Log::info('Auth login success', [
            'user_id' => $user->id,
            'email' => $user->email,
        ]);

        Auth::logout();

        return $this->sendResponse($success, 'User login');
    }

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firstName'    => 'required|string|max:255',
            'lastName'     => 'required|string|max:255',
            'email'        => 'required|email|unique:users,email|max:255',
            'message'      => 'nullable|string|max:500',
            'password'     => 'required|string|min:8',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $user = User::create([
            'firstName'   => $request->firstName,
            'lastName'    => $request->lastName,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => "admin",
            'deleted'     => 0,
            'status'      => 1,
            'profile_step' => 2,
            'profile_completed_at' => null,
        ]);


        $success['user'] =  $user;

        $success['token'] =  $user->createToken('MyAuthApp')->plainTextToken;

        return $this->sendResponse($success, 'User created successfully.');
    }

    /**
     * Lightweight registration endpoint for basic onboarding flows.
     */
    public function simpleSignup(Request $request)
    {
        $payload = $request->validate([
            'firstName' => ['required', 'string', 'max:255'],
            'lastName'  => ['required', 'string', 'max:255'],
            'email'     => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'  => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'firstName' => $payload['firstName'],
            'lastName'  => $payload['lastName'],
            'email'     => $payload['email'],
            'password'  => Hash::make($payload['password']),
            'role'      => 'staff',
            'deleted'   => 0,
            'status'    => 1,
            'profile_step' => 2,
            'profile_completed_at' => null,
        ]);

        $response = [
            'user' => $user,
            'token' => $user->createToken('MyAuthApp')->plainTextToken,
            'userAbilities' => [
                [
                    'action' => 'manage',
                    'subject' => 'all',
                ],
            ],
        ];

        return $this->sendResponse($response, 'User registered successfully.');
    }

    public function signout(Request $request)
    {        
        $user = $request->user();
        if ($user) {
            event(new Logout('sanctum', $user));            
            $user->currentAccessToken()->delete();
        }
                
        $success['result'] = true;

        return $this->sendResponse($success, 'Successfully logged out!');
    }

    public function getUsers(Request $request){

        $users = User::where('deleted', 0)->get();

        $success['users'] = $users;
        return $this->sendResponse($success, 'Successfully get users');
    }

    //
    public function changeUserRole(Request $request){

        $validator = Validator::make($request->all(), [
            'uId'        => ['required', 'integer', Rule::exists('users', 'id')->where('deleted', 0)],
            "isCompany"  => 'required',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $user = User::where(['id'=>$request->uId])->first();
        $user->isCompany = $request->isCompany;
        $user->save();

        return $this->sendResponse($user, 'Successfully changed users role');
    }

    //
    public function getProfile(Request $request)
    {
        $success['user'] = Auth::user()->load('personalInfo');
        return $this->sendResponse($success, 'Successfully get the user profile!');
    }

    public function saveProfile(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'firstName'    => 'required|string|max:255',
            'lastName'     => 'required|string|max:255',
            'email'        => ['required', 'email','max:255',
                                Rule::unique('users', 'email')->ignore($user->id)],
            'company'      => 'nullable|string|max:255',
            "city"         => 'required|string|max:255',
            "postCode"     => 'required|string|max:255',
            "country"      => 'required|string|max:255',
            "language"     => 'required|string|max:255',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $user->firstName = $request->firstName;
        $user->lastName = $request->lastName;
        $user->email = $request->email;
        $user->save();

        return $this->getProfile($request);
    }

    public function resetPassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'newPassword'    => 'required|string|min:8',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $user->password = Hash::make($request->newPassword);
        $user->save();

        return $this->sendResponse(true, 'Successfully resettled password!');
    }

    public function confirmPassword(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'password'    => 'required|string|min:8',
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }
        
        if( Hash::check($request->password, $user->password) ){
            return $this->sendResponse(true, 'Password is correct!');
        }else {
            return $this->sendResponse(false, 'Password is not correct!');
        }
    }

    public function userRemove(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'uId'        => ['required', 'integer', Rule::exists('users', 'id')->where('deleted', 0)],
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $user = User::where(['id'=>$request->uId])->first();
        $user->deleted = 1;
        $user->save();

        return $this->sendResponse(true, 'Successfully closed account!');
    }

    public function userAddNew(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userList'   => ['required', 'array'],
            'userList.*.firstName' => ['required', 'string', 'max:255'],
            'userList.*.lastName'  => ['required', 'string', 'max:255'],
            'userList.*.email'     => ['required', 'email', Rule::unique('users', 'email')],
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        $insertData = [];
        foreach($request->userList as $user){
            $insertData[] = [
                'firstName'    => $user['firstName'],
                'lastName'     => $user['lastName'],
                'email'        => $user['email'],
                'password'     => Hash::make($this->defaultPassword),
                'regType'      => "csv",
                'isCompany'    => 0,
                'created_at'   => now(),
                'deleted'      => 0,
                'profile_step' => 2,
                'profile_completed_at' => null,
            ];
        }
        DB::table('users')->insert($insertData);

        return $this->sendResponse(true, 'Successfully imported users!');
    }

    public function removeAccount(Request $request)
    {
        $user = Auth::user();
        $user->deleted = 1;
        $user->save();

        return $this->sendResponse(true, 'Successfully closed account!');
    }

    public function saveSecurity(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'userList'   => ['required', 'array'],
            'userList.*.status' => ['required', 'boolean'],
        ]);

        if($validator->fails()){
            return $this->sendError('Error validation', $validator->errors());
        }

        foreach($request->userList as $user){            
            // DB::table('users')->where(['id' => $user['id'], 'role' => 'staff'])->update(['status' => $user['status'] ?? 0]);
            DB::table('users')->where(['id' => $user['id']])->update(['status' => $user['status'] ?? 0]);
        }

        return $this->sendResponse(true, 'Successfully updated users!');
    }
}
