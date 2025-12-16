<?php

namespace App\Http\Controllers\Api\v1;

use App\DTO\SocialLoginDto;
use App\Helpers\Roles;
use App\Helpers\Traits\QueryBuilderTrait;
use App\Http\Controllers\Controller;
use App\Http\Repositories\v1\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Jenssegers\Agent\Agent;
use Throwable;

class AuthController extends Controller
{
    public function __construct(public UserRepository $userRepository)
    {
    }

    use QueryBuilderTrait;

    /**
     * @throws Throwable
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt(['login' => $request->login, 'password' => $request->password])) {
            return errorResponse(message:("Invalid credentials!"), status: 401);
        }

        DB::beginTransaction();
        try {
            $user = Auth::user();
            $accessToken = $user->createToken($user->role)->accessToken;
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            return errorResponse(message: $e->getMessage());
        }

        $this->allowIncludeAndAppend($request, $user);

        return okResponse([
            'user' => $user,
            'token' => $accessToken
        ]);
    }


    public function adminLogin(Request $request)
    {
        $request->validate([
            'login' => 'required',
            'password' => 'required'
        ]);

        if (!Auth::attempt(['login' => $request->login, 'password' => $request->password])) {
            return errorResponse(message: ("Invalid credentials!"), status: 401);
        }
        $user = Auth::user();
        if (!$user->userRole()->where('role', Roles::ROLE_ADMIN)->first()) {
            return errorResponse(status: 403);
        }
        $token = $user->createToken($user->login, [$user->role])->accessToken;
        $this->allowIncludeAndAppend($request, $user);
        return response()->json([
            'user' => $user,
            'token' => $token
        ]);
    }

    public function socialLogin(Request $request)
    {
        $request->validate([
            'provider' => 'required|string',
            'provider_id' => 'required|string',
            'email' => 'nullable|email',
            'full_name' => 'required|string',
            'avatar' => 'nullable|url'
        ]);
        DB::beginTransaction();
        try {
            $agent = new Agent();
            $socialLoginDto = new SocialLoginDto($request->all());
            $user = $this->userRepository->socialLogin($socialLoginDto);
            if (!$user) {
                return errorResponse(message: "User not found or social login failed", status: 404);
            }
            Auth::login($user);
            $token = $user->createToken($user->login, [$user->role])->accessToken;
            $this->allowIncludeAndAppend($request, $user);
            DB::commit();
            return response()->json([
                'user' => $user,
                'token' => $token,
            ]);
        } catch (Throwable $e) {
            DB::rollBack();
            return errorResponse(message: $e->getMessage(), status: $e->getCode());
        }
    }




    /**
     * Get authenticated user info
     *
     * @response {
     *   "id": 1,
     *   "name": "Azizbek Xolmatov",
     *   "email": "azizbek0@example.com",
     *   "login": "azizbek0",
     *   "phone": "+998901234567",
     *   "role": "user"
     * }
     * @return JsonResponse
     */

    public function getMe(Request $request)
    {
        return $this->userRepository->getMe($request);
    }

    /**
     * Update authenticated user info
     *
     * @bodyParam password string Minimum 6 characters. Example: newpassword123
     * @bodyParam photo integer File ID. Example: 12
     * @return JsonResponse
     */
    public function updateMe(Request $request)
    {
        $request->validate([
            'password' => 'nullable|string|min:6',
            'photo' => 'nullable|integer|exists:files,id',
        ]);
        return $this->userRepository->updateMe($request);
    }


    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
