<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller {
	public function store(Request $request) {
		$responseData = array();
		$responseError = null;

		$request->validate([
			'name' => ['required', 'max:255'],
			'email' => ['required', 'email', 'unique:users,email', 'max:255'],
			'password' => ['required'],
		]);

		$user = User::create([
			'name' => $request->input('name'),
			'email' => $request->input('email'),
			'password' => $request->input('password'),
		]);

		$responseData['status'] = 'success';

		return self::getAPIResponse($responseData, $responseError);
	}

	public function show(Request $request) {
		$responseData = array();
		$responseError = null;

		if(auth()->attempt([
			'email' => $request->input('email'),
			'password' => $request->input('password'),
		])) {
			$user = auth()->user();
			$responseData['name'] = $user->name;
			$responseData['email'] = $user->email;
		} else {
			$responseError = 'wrong_credentials';
		}

		return self::getAPIResponse($responseData, $responseError);
	}

	public function tokensCreate(Request $request) {
		$responseData = array();
		$responseError = null;

		if(auth()->attempt([
			'email' => $request->input('email'),
			'password' => $request->input('password'),
		])) {
			$user = auth()->user();

			$token = $user->createToken('api');
			$responseData['token'] = $token->plainTextToken;
		} else {
			$responseError = 'wrong_credentials';
		}

		return self::getAPIResponse($responseData, $responseError);
	}
}
