<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    protected SmsService $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * POST /api/v1/auth/send-otp
     * Send OTP to phone number
     */
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'type'  => 'in:register,login,reset_password',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $phone = $request->phone;
        $type  = $request->type ?? 'login';

        if ($type === 'register') {
            $exists = User::where('phone', $phone)->exists();
            if ($exists) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Bu telefon raqami allaqachon ro\'yxatdan o\'tgan',
                ], 409);
            }
        }

        $this->smsService->sendOtp($phone, $type);

        return response()->json([
            'status'  => true,
            'message' => 'Tasdiqlash kodi yuborildi',
        ]);
    }

    /**
     * POST /api/v1/auth/verify-otp
     * Verify OTP code
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code'  => 'required|string|size:4',
            'type'  => 'in:register,login,reset_password',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $verified = $this->smsService->verifyOtp(
            $request->phone,
            $request->code,
            $request->type ?? 'login'
        );

        if (!$verified) {
            return response()->json([
                'status'  => false,
                'message' => 'Kod noto\'g\'ri yoki muddati o\'tgan',
            ], 400);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Kod tasdiqlandi',
            'verified' => true,
        ]);
    }

    /**
     * POST /api/v1/auth/register
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:100',
            'phone'    => 'required|string|unique:users,phone',
            'password' => 'required|string|min:6|confirmed',
            'code'     => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        // Verify OTP
        $verified = $this->smsService->verifyOtp($request->phone, $request->code, 'register');
        if (!$verified) {
            return response()->json([
                'status'  => false,
                'message' => 'Telefon raqami tasdiqlanmagan',
            ], 400);
        }

        $user = User::create([
            'name'               => $request->name,
            'phone'              => $request->phone,
            'password'           => Hash::make($request->password),
            'role'               => 'user',
            'is_verified'        => true,
            'phone_verified_at'  => now(),
        ]);

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status'  => true,
            'message' => 'Muvaffaqiyatli ro\'yxatdan o\'tdingiz',
            'token'   => $token,
            'user'    => $this->userResource($user),
        ], 201);
    }

    /**
     * POST /api/v1/auth/login
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone'    => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $credentials = ['phone' => $request->phone, 'password' => $request->password];

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
                'status'  => false,
                'message' => 'Telefon raqam yoki parol noto\'g\'ri',
            ], 401);
        }

        $user = JWTAuth::user();

        if (!$user->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Hisobingiz bloklangan. Admin bilan bog\'laning.',
            ], 403);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Muvaffaqiyatli kirdingiz',
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    /**
     * POST /api/v1/auth/login-otp
     * Login with phone + OTP (no password)
     */
    public function loginWithOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'code'  => 'required|string|size:4',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'errors' => $validator->errors()], 422);
        }

        $verified = $this->smsService->verifyOtp($request->phone, $request->code, 'login');
        if (!$verified) {
            return response()->json([
                'status'  => false,
                'message' => 'Kod noto\'g\'ri yoki muddati o\'tgan',
            ], 400);
        }

        $user = User::where('phone', $request->phone)->first();
        if (!$user) {
            return response()->json([
                'status'  => false,
                'message' => 'Foydalanuvchi topilmadi',
            ], 404);
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'status'  => true,
            'token'   => $token,
            'user'    => $this->userResource($user),
        ]);
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout()
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'status'  => true,
            'message' => 'Muvaffaqiyatli chiqdingiz',
        ]);
    }

    /**
     * POST /api/v1/auth/refresh
     */
    public function refresh()
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'status' => true,
            'token'  => $token,
        ]);
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me()
    {
        $user = JWTAuth::user();

        return response()->json([
            'status' => true,
            'user'   => $this->userResource($user),
        ]);
    }

    private function userResource(User $user): array
    {
        return [
            'id'      => $user->id,
            'name'    => $user->name,
            'phone'   => $user->phone,
            'email'   => $user->email,
            'avatar'  => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'role'    => $user->role,
            'balance' => $user->balance,
            'lang'    => $user->lang,
            'is_verified' => $user->is_verified,
        ];
    }
}
