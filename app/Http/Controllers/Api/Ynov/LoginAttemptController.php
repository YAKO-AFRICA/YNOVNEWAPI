<?php
namespace App\Http\Controllers\Api\Ynov;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\Ynov\LoginAttemptResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LoginAttemptController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $attempts = $request->user()->loginAttempts()
            ->orderByDesc('attempted_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => LoginAttemptResource::collection($attempts),
        ]);
    }
}