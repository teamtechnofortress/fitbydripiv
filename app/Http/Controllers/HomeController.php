<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\API\BaseController;

class HomeController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }


    /**
     * test function
     */
    public function test(Request $request)
    {
        $success = "Hello World!";
        return $this->sendResponse($success, 'Succesfully Get Test String');
    }
}
