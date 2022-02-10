<?php

namespace App\Http\Controllers\Auth;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->all();

        if (isset($data['date_of_birth']) && $data['date_of_birth']) {
            $date = str_replace('/', '-', $data['date_of_birth']);
            $data['date_of_birth'] = date("Y-m-d", strtotime($date));
        }

        $validator = Validator::make($data,[
            'username' => 'required|string|max:191|unique:users',
            'email' => 'required|string|email|max:191|unique:users',
            'password' => 'required|string|min:6',
            'first_name' => 'required|string|max:191',
            'last_name' => 'required|string|max:191',
            'phone' => 'required|string|max:191|unique:users',
            'registration_type' => 'required|string|max:191',

                //required if registration type is PLAYER
            // 'city' => 'required_if:registration_type,PLAYER|string|max:191',
            // 'state' => 'required_if:registration_type,PLAYER|string|max:191',
            // 'country' => 'required_if:registration_type,PLAYER|string|max:191',
            // 'gender' => 'required_if:registration_type,PLAYER|string|in:MALE,FEMALE,OTHER',
            // 'date_of_birth' => 'required_if:registration_type,PLAYER|date',
            // 'playing_role' => 'required_if:registration_type,PLAYER|string|max:20',
            // 'batting_style' => 'required_if:registration_type,PLAYER|string|max:20',
            // 'bowling_style' => 'required_if:registration_type,PLAYER|string|max:20',"
        ],[
            'city.required_if' => "City is required.",
            'state.required_if' => "State is required.",
            'country.required_if' => "Country is required.",
            'gender.required_if' => "Gender is required.",
            'date_of_birth.required_if' => "Birth date is required.",
            'playing_role.required_if' => "Role is required.",
            'batting_style.required_if' => "Batting style is required.",
            'bowling_style.required_if' => "Bowling style is required.",
        ]);

        if($validator->fails()){
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }

        $data['password'] = Hash::make($data['password']);
        $user = User::create($data);

        $token = $user->createToken('auth_token')->plainTextToken;
        return response()->json(['data' => $user,'access_token' => $token, 'token_type' => 'Bearer' ]);

    }

    public function updateProfile(Request $request)
    {
        $data = $request->all();
        $check = User::where('email', $data['email'])->first();

        if(!$check){
            return response()->json(['msg' => 'Invalid Creaentials'], 401);
        }
        if($check->first_name == '' || $check->last_name == '' ||$check->phone == '' ){

            $validator = Validator::make($request->all(),[
                'first_name' => 'required|string|max:191',
                'last_name' => 'required|string|max:191',
                'phone' => 'required|string|max:191|unique:users',
            ]);

        }else{
            $validator = Validator::make($request->all(),[
                'registration_type' => 'required|string|max:191'
            ],[
                'registration_type.required' => "Please, select your role."
            ]);
        }


        if($validator->fails()){
            return response()->json([
                'messages' => collect($validator->errors()->all())
            ], 422);
        }
        unset($data['email']);
        $user = User::where('email', $check->email)->update($data);
        if($user && $check->first_name && $check->last_name && $check->phone){
            $token = $check->createToken('auth_token')->plainTextToken;
            return response()->json(['data' => $check,'access_token' => $token, 'token_type' => 'Bearer', ]);
        }
         return $user;
    }

    public function login(Request $request)
    {

        $singleUser = User::where('email', $request['email']);
        $user = $singleUser->first();

        if(!$user){return response()->json(['message' => 'Invalid Creadentials.'], 401); }

        if($user && ($user->first_name == '' || $user->last_name == '' ||$user->phone == '')){
            return response()->json(['message' => 'Your account is incomplete.', 'status' => 'step1'], 401);
        }

        if($user && $user->registration_type == ''){
            return response()->json(['message' => 'Your account is incomplete.', 'status' => 'step2'], 401);
        }

        if (!Auth::attempt($request->only('email', 'password')))
        {
            return response()->json(['message' => 'Invalid Creadentials.'], 401);
        }

        $singleUser->update(['app_token' => $request['app_token']]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json(['message' => 'Hi '.$user->name.', welcome to home','access_token' => $token, 'token_type' => 'Bearer', ]);
    }
    public function getUser(Request $request){
        // we can get the loggedin user info from the request variable
        // request->user only works when "auth:sanctum" middleware is used..
        $auth = $request->user();

        if($auth->social_accounts){
            $auth->social_accounts = json_decode($auth->social_accounts);
        }

        return $auth;

    }
    public function checkIfLoggedInOrNot(Request $request){
        // this will return the user object if there is any or null if not loggedin
        return auth('sanctum')->user();

    }


    // method for user logout and delete token
    public function logout(Request $request)
    {
        $user = $request->user();
        $user->currentAccessToken()->delete();
        $user->update(['app_token' => null]);

        return [
            'message' => 'You have successfully logged out and the token was successfully deleted'
        ];
    }


    //Forgot-password

}
