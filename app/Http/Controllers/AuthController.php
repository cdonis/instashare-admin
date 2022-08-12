<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    /**
     * Register user
     *
     * @return \Illuminate\Http\Response
     */

    public function signup(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|regex:/^[a-zA-Z@áéíóúÁÉÍÓÚñÑ\s]+$/',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        $userData = $validator->validated();
        $userData['password'] = bcrypt($userData['password']);

        $user = User::create($userData);
        $responseData['token'] = $user->createToken('InstaShareSanctum')->plainTextToken;
        $responseData['name'] = $user->name;
        $responseData['user_id'] = $user->id;
        
        return response()->json($responseData, Response::HTTP_CREATED);
    }

    /**
     * Login user
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $validator->validated();
        if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) { 
            /** @var User $user */
            $user = Auth::user(); 

            $responseData['token'] = $user->createToken('InstaShareSanctum')->plainTextToken; 
            $responseData['name'] = $user->name;
            $responseData['user_id'] = $user->id;

            return response()->json($responseData, Response::HTTP_OK);

        } else { 
            throw new Exception('Wrong credentials.', Response::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Get current user information
     *
     * @return \Illuminate\Http\Response
     */
    public function getCurrentUser()
    {
      try {
        /** @var User $user */
        $user = Auth::user();
        return response()->json($user->only(['id', 'name', 'email']));

      } catch (\Exception $e) {
        throw new \Exception("Error getting current user information.", Response::HTTP_INTERNAL_SERVER_ERROR, $e);
      }
    }

    /**
     * Logout user
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
        /** @var User $user */
        $user = Auth::user();

        // Revoke the token that was used to authenticate the current request 
        /** @var Model $patObject */
        $patObject = $user->currentAccessToken();
        if (method_exists($patObject, 'delete')) {
          // PersonalAccessToken expected
          $patObject->delete();                          
        } else {
          // Fix to consider testing authentication where Sanctum uses TransientToken instead PersonalAccessToken 
          // because user is logged in via session/cookie
          // TransientToken expected 
          auth()->guard('web')->logout();
        }

        return response()->noContent();
    }
}
