<?php

namespace Botble\Customers\Http\Controllers\API;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Customers\Models\Customers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class CustomerController extends BaseController
{
    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 400);
        }

        $credentials = $request->only(['email', 'password']);


        if (!$token = auth('api')->attempt($credentials)) {
            return $this->respondWithError("Unauthorized", 401);
        }


        return $this->respondWithToken($token);
    }


    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:255',
            'email' => 'required|email|unique:customers',
            'password' => 'required|confirmed|min:6',
        ]);

        if ($validator->fails()) {
            return $this->respondWithError($validator->errors(), 422);
        }

        $customer = new Customers([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $customer->save();

        $token = JWTAuth::fromUser($customer);

        return response()->json(compact('customer', 'token'), 201);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            "error" => false,
            "data" => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }

    protected function respondWithError($message, $type = 404)
    {
        return response()->json([
            "error" => true,
            "message" => $message,
            "type" => $type
        ]);
    }
}
