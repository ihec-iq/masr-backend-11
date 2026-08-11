<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\LoginRequest;
use App\Http\Resources\User\UserProfileResource;
use App\Http\Resources\User\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\TransientToken;

class AuthController extends Controller
{
    public function login(LoginRequest $request)
    {
        $identifier = $request->email;
        $user = User::where('active', true)
            ->where(function ($q) use ($identifier) {
                $q->where('email', $identifier)
                  ->orWhere('user_name', $identifier);
            })
            // ترتيب حاسم: بدونه يختار MySQL صفًّا عشوائيًا عند تطابق أكثر من مستخدم
            ->orderBy('id')
            ->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(
                [
                    'code' => '402',
                    'message' => "The provided credentials are incorrect or the account is inactive.",
                ],
                401
            );
        }
        $token = $user->createToken($identifier)->plainTextToken;

        return response()->json(
            [
                'id' => $user->id,
                'name' => $user->name,
                'token' => $token,
            ],
            200
        );
    }

    public function me()
    {
        return new UserResource(Auth::user());
    }

    public function profile()
    {
        return new UserProfileResource(Auth::user());
    }

    public function ho_me()
    {
        return new UserResource(Auth::user());
    }

    public function logout(Request $request)
    {
        // تحت حارس session/SPA يكون التوكن TransientToken بلا delete()، لذا نستثنيه هو
        // تحديدًا بدل التحقق من الصنف الفعلي (قد يُستبدل عبر Sanctum::usePersonalAccessTokenModel)
        $token = $request->user()?->currentAccessToken();
        if ($token && ! $token instanceof TransientToken) {
            $token->delete();
        }

        return response()->json(['message' => 'Successfully logged out'], 200);
    }
}
