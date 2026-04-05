<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController {
	use AuthorizesRequests;

	static public function getAPIResponse($responseData = array(), $responseError = null) {
		if(!is_null($responseError)) {
			$responseData['error'] = $responseError;
		}

		return response()->make($responseData, (!is_null($responseError)?422:200));
	}
}
