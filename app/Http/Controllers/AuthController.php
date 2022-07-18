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
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'c_password' => 'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Validation Error.', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors()->messages());       
        }

        $userData = $validator->validated();
        $userData['password'] = bcrypt($userData['password']);

        try {
            $user = User::create($userData);
            $responseData['token'] = $user->createToken('InstaShareSanctum')->plainTextToken;
            $responseData['name'] = $user->name;
            
            return $this->sendResponse($responseData, 'User registered successfully.', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->sendError('Error creating user', $e->getCode(), [$e->getMessage()]);
        }
    }

    /**
     * Login user
     *
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), 
            [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation Error.', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors()->messages());
            }

            $credentials = $validator->validated();
            if (Auth::attempt(['email' => $credentials['email'], 'password' => $credentials['password']])) { 
                /** @var User $user */
                $user = Auth::user(); 
    
                $responseData['token'] =  $user->createToken('InstaShareSanctum')->plainTextToken; 
                $responseData['name'] =  $user->name;
    
                return $this->sendResponse($responseData, 'User login successfully.', Response::HTTP_OK);
            } 
            else { 
                return $this->sendError('Unauthorised.', Response::HTTP_UNAUTHORIZED, ['error' => 'Bad credentials.']);
            } 
        } catch (Exception $e) {
            return $this->sendError('Login error', $e->getCode(), [$e->getMessage()]);
        }
    }

    /**
     * Logout user
     *
     * @return \Illuminate\Http\Response
     */
    public function logout()
    {
      try {
        /** @var User $user */
        $user = Auth::user();

        // Revoke the token that was used to authenticate the current request 
        $user->currentAccessToken()->delete();

        return $this->sendResponse(null, 'Successful logout', Response::HTTP_OK);

      } catch (\Exception $e) {
        
        return $this->sendError('Logout error', $e->getCode(), [$e->getMessage()]);
      }
    }
}
