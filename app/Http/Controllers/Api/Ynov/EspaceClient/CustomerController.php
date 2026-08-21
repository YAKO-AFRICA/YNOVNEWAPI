<?php

namespace App\Http\Controllers\Api\Ynov\EspaceClient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CustomerController extends Controller
{

    public function index(Request $request)
    {
        return response()->json(['type' => 'success']);
    }
    
}
