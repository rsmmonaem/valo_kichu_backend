<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Resources\AddressResource;
use App\Http\Resources\UserResource;
use App\Models\Brand;
use App\Models\User;
use App\Models\Otp;
use App\Models\Address;
use App\Services\OtpService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class AuthController extends Controller
{
    protected $otpService;

    public function __construct(OtpService $otpService)
    {
        $this->otpService = $otpService;
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email|unique:users,email',
            'phone_number' => 'required|string|max:15|unique:users,phone_number',
            'password' => 'required|string|min:6',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $userData = [
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'gender' => $request->gender,
            'date_of_birth' => $request->date_of_birth,
            'role' => 'customer',
            'is_active' => true,
            'is_verified' => false, // User needs to verify
        ];

        // Only include email if it's provided
        if ($request->filled('email')) {
            $userData['email'] = $request->email;
        }

        $user = User::create($userData);

        // Send OTP - prioritize email if available, otherwise phone
        $email = $user->email;
        $phoneNumber = $user->phone_number;
        $sendResult = $this->otpService->sendOtp($user, $phoneNumber, $email);

        // if (! $sendResult['success']) {
        //     return response()->json([
        //         'error' => 'Registration successful but failed to send verification OTP.',
        //         'detail' => $sendResult['message'],
        //     ], 400);
        // }

        $token = $user->createToken('access_token')->plainTextToken;
        $user->refresh();

        return response()->json([
            'detail' => 'Registration successful!',
            'message' => 'Verification OTP has been sent to your '.$sendResult['method'].'.',
            'sent_to' => $sendResult['method'],
            'user' => new UserResource($user),
            'access_token' => $token,
            'token_type' => 'Bearer',
            'access' => $token,
            'refresh' => $token,
        ], 201);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone_number' => 'required_without:email|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($request->filled('email')) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->filled('phone_number')) {
            $user = User::where('phone_number', $request->phone_number)->first();
        } else {
            return response()->json(['error' => 'Email or phone number is required.'], 400);
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials.'], 400);
        }

        $token = $user->createToken('access_token')->plainTextToken;

        $user->refresh();
        if (! $user->is_active) {
            return response()->json(['error' => 'This account is inactive.'], 400);
        }

        if (! $user->is_verified) {
            // Generate and send OTP for verification
            // Use user's actual email/phone, not request values (in case user logged in with phone)
            $email = $user->email;
            $phoneNumber = $user->phone_number;

            $verifyRequired = getBusinessSetting('is_verify_required') == 1 ? true : false;
            $numberVerifyRequired = getBusinessSetting('number_verify_required') == 1 ? true : false;
            $emailVerifyRequired = getBusinessSetting('email_verify_required') == 1 ? true : false;
            if($numberVerifyRequired){
                $sendResult = $this->otpService->sendOtp($user, $phoneNumber, null);
            }elseif($emailVerifyRequired){
                $sendResult = $this->otpService->sendOtp($user, null, $email);
            }elseif($verifyRequired){
                $sendResult = $this->otpService->sendOtp($user, $phoneNumber, $email);
            }
            if($verifyRequired || $numberVerifyRequired || $emailVerifyRequired){
                return response()->json([
                    'error' => 'Account not verified. Please verify your account.',
                    'message' => 'Verification OTP has been sent to your '.$sendResult['method'].'.',
                    'sent_to' => $sendResult['method'],
                    'user' => new UserResource($user),
                    'is_verified' => false,
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'access' => $token,
                    'refresh' => $token,
                ], 200);
            }

        }


        return response()->json([
            'detail' => 'Login successful!',
            'user' => new UserResource($user),
            'access' => $token,
            'refresh' => $token,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 200);
    }

    public function sendVerification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if (! $request->email && ! $request->phone_number) {
            return response()->json(['error' => 'Email or phone number is required.'], 400);
        }

        // Find user by email or phone_number
        $user = null;
        if ($request->email && $request->email !== '') {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->phone_number && $request->phone_number !== '') {
            $user = User::where('phone_number', $request->phone_number)->first();
        } else {
            return response()->json(['error' => 'Email or phone number is required.'], 400);
        }

        if (! $user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Send OTP (service will create/update OTP and send it)
        $email = $request->email;
        $phoneNumber = $request->phone_number;

        $sendResult = $this->otpService->sendOtp($user, $phoneNumber, $email);

        if (! $sendResult['success']) {
            return response()->json([
                'error' => $sendResult['message'],
            ], 500);
        }

        return response()->json([
            'message' => $sendResult['message'],
            'sent_to' => $sendResult['method'],
        ], 200);
    }

    public function verification(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
            'otp' => 'required|string|size:6',
            'is_login' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        if ($request->is_login == null) {
            $request->is_login = false;
        }

        // Find user by email or phone_number
        $user = null;
        if ($request->email && $request->email !== '') {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->phone_number && $request->phone_number !== '') {
            $user = User::where('phone_number', $request->phone_number)->first();
        } else {
            return response()->json(['error' => 'Email or phone number is required.'], 400);
        }

        if (! $user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Verify OTP using service
        $verificationResult = $this->otpService->verifyOtp($user, $request->otp);

        if (! $verificationResult['success']) {
            return response()->json(['error' => $verificationResult['message']], 400);
        }

        // Delete OTP after successful verification
        $verificationResult['otp']->delete();

        // Update user verification status
        $user->is_verified = true;

        // Set verification timestamps based on what was verified
        if ($request->email) {
            $user->email_verified_at = now();
        }
        if ($request->phone_number) {
            $user->phone_number_verified_at = now();
        }

        $user->save();

        $user->refresh();

        // If is_login is true, return JWT token
        if ($request->is_login) {
            $token = $user->createToken('access_token')->plainTextToken;

            return response()->json([
                'detail' => 'Verification successful!',
                'user' => new UserResource($user),
                'access' => $token,
                'refresh' => $token,
            ], 200);
        }

        return response()->json([
            'detail' => 'Verification successful!',
            'user' => new UserResource($user),
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Successfully logged out'], 200);
    }

    public function userInfo(Request $request)
    {
        $user = $request->user();
        $user->refresh();

        return response()->json(new UserResource($user), 200);
    }

    public function me(Request $request)
    {
        return $this->userInfo($request);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:15|unique:users,phone_number,'.$user->id,
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user->fill($request->only([
            'first_name', 'last_name', 'phone_number', 'gender', 'date_of_birth',
        ]));

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($user->image && Storage::disk('public')->exists($user->image)) {
                Storage::disk('public')->delete($user->image);
            }

            // Save image as JPEG
            $imagePath = $this->saveImageAsJpeg($request->file('image'), 'profile_picture');
            $user->image = $imagePath;
        }

        $user->save();

        return response()->json([
            'detail' => 'Profile updated successfully!',
            'user' => new UserResource($user),
        ], 200);
    }


    public function updateFcmToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = $request->user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'message' => 'FCM token updated successfully.',
            'fcm_token' => $user->fcm_token,
        ], 200);
    }

    public function getAddresses(Request $request)
    {
        $addresses = Address::where('user_id', $request->user()->id)->get();

        return response()->json(AddressResource::collection($addresses), 200);
    }

    public function getAddress(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        return response()->json(new AddressResource($address), 200);
    }

    public function createAddress(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
            'email' => 'required|email',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'district' => 'required',
            'state' => 'nullable',
            'postal_code' => 'nullable',
            'country' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $address = Address::create(array_merge(
            $request->only([
                'title', 'name', 'phone', 'email', 'address_line1', 'address_line2',
                'city', 'district', 'state', 'postal_code', 'country',
                'latitude', 'longitude', 'is_billing', 'is_shipping'
            ]),
            ['user_id' => $request->user()->id]
        ));

        return response()->json(new AddressResource($address), 201);
    }

    public function updateAddress(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|string|max:255',
            'email' => 'sometimes|email',
            'address_line1' => 'sometimes|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'sometimes|string|max:100',
            'district' => 'sometimes|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $address->update($request->only([
            'title', 'name', 'phone', 'email', 'address_line1', 'address_line2',
            'city', 'district', 'state', 'postal_code', 'country',
            'latitude', 'longitude', 'is_billing', 'is_shipping'
        ]));

        return response()->json(new AddressResource($address), 200);
    }

    public function deleteAddress(Request $request, $id)
    {
        $address = Address::where('user_id', $request->user()->id)->findOrFail($id);
        $address->delete();

        return response()->json(['detail' => 'Address deleted successfully.'], 200);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // At least one identifier (email or phone_number) must be provided
        if (! $request->email && ! $request->phone_number) {
            return response()->json(['error' => 'Email or phone number is required.'], 400);
        }

        // Find user by email or phone_number
        $user = null;
        if ($request->email && ! empty(trim($request->email))) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->phone_number && ! empty(trim($request->phone_number))) {
            $user = User::where('phone_number', $request->phone_number)->first();
        }

        if (! $user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

        Otp::updateOrCreate(
            ['user_id' => $user->id],
            [
                'code' => $code,
                'created_at' => now(),
            ]
        );

        // Send OTP via email or SMS
        $email = $user->email;
        $phoneNumber = $user->phone_number;
        $sendResult = $this->otpService->sendOtp($user, $phoneNumber, $email);

        if (! $sendResult['success']) {
            return response()->json([
                'error' => 'Failed to send OTP.',
                'detail' => $sendResult['message'],
            ], 400);
        }

        return response()->json([
            'message' => 'Password reset OTP sent to your '.$sendResult['method'].'.',
            'sent_to' => $sendResult['method'],
        ], 200);
    }

    public function verifyResetToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:user,email',
            'otp' => 'required|string|size:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::where('email', $request->email)->first();
        $otp = Otp::where('user_id', $user->id)
            ->where('code', $request->otp)
            ->first();

        if (! $otp) {
            return response()->json(['error' => 'Invalid token.'], 400);
        }

        if (Carbon::parse($otp->created_at)->addMinutes(5)->isPast()) {
            return response()->json(['error' => 'Token has expired.'], 400);
        }

        $otp->delete();

        return response()->json(['message' => 'Token verified successfully.'], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'nullable|email',
            'phone_number' => 'nullable|string',
            'new_password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // At least one identifier (email or phone_number) must be provided
        if (! $request->email && ! $request->phone_number) {
            return response()->json(['error' => 'Email or phone number is required.'], 400);
        }

        // Find user by email or phone_number
        $user = null;
        if ($request->email && ! empty(trim($request->email))) {
            $user = User::where('email', $request->email)->first();
        } elseif ($request->phone_number && ! empty(trim($request->phone_number))) {
            $user = User::where('phone_number', $request->phone_number)->first();
        }

        if (! $user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // // Verify OTP
        // $otp = Otp::where('user_id', $user->id)
        //     ->where('code', $request->otp)
        //     ->first();

        // if (!$otp) {
        //     return response()->json(['error' => 'Invalid OTP.'], 400);
        // }

        // // Check if OTP is expired (5 minutes)
        // if (Carbon::parse($otp->created_at)->addMinutes(5)->isPast()) {
        //     $otp->delete();
        //     return response()->json(['error' => 'OTP has expired. Please request a new one.'], 400);
        // }

        // Reset password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json(['message' => 'Password reset successfully.'], 200);
    }

    public function socialLogin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'access_token' => 'required|string',
            'medium' => 'required|in:google,facebook',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $accessToken = $request->access_token;
        $medium = $request->medium;

        if ($medium === 'google') {
            $response = Http::get(
                'https://www.googleapis.com/oauth2/v1/userinfo',
                ['access_token' => $accessToken]
            );
        } elseif ($medium === 'facebook') {
            $response = Http::get(
                'https://graph.facebook.com/me',
                [
                    'fields' => 'id,name,email,picture',
                    'access_token' => $accessToken,
                ]
            );
        }

        if (! $response->successful()) {
            return response()->json([
                'error' => 'Failed to fetch user info',
            ], 400);
        }

        $userInfo = $response->json();

        $email = $userInfo['email'] ?? null;
        if (! $email) {
            return response()->json([
                'error' => 'Email not found in response',
            ], 400);
        }

        $firstName = $userInfo['given_name']
            ?? explode(' ', $userInfo['name'] ?? '')[0] ?? null;

        $lastName = $userInfo['family_name']
            ?? explode(' ', $userInfo['name'] ?? '')[1] ?? null;

        $socialId = $userInfo['id'] ?? null;

        $profilePicture = null;
        if ($medium === 'google') {
            $profilePicture = $userInfo['picture'] ?? null;
        } elseif ($medium === 'facebook') {
            $profilePicture = $userInfo['picture']['data']['url'] ?? null;
        }

        // Find user by email
        $user = User::where('email', $email)->first();
        $isNewUser = false;

        if (! $user) {
            $isNewUser = true;

            $user = User::create([
                'email' => $email,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone_number' => $userInfo['phone_number'] ?? null,
                'password' => Hash::make(uniqid()), // unusable password equivalent
                'social_id' => $socialId,
                'social_provider' => $medium,
                'is_verified' => true,
                'email_verified_at' => now(),
            ]);
            $user->refresh();
        }

        if ($isNewUser && $profilePicture) {
            $this->saveProfileImage($user, $profilePicture);
        }

        // Generate Sanctum token
        $token = $user->createToken('access_token')->plainTextToken;

        return response()->json([
            'detail' => 'Login successful!',
            'user' => new UserResource($user),
            'access' => $token,
            'refresh' => $token,
        ], 200);

    }

    /**
     * Save profile image from URL (social login)
     * Downloads image and saves as JPEG
     */
    protected function saveProfileImage($user, $imageUrl)
    {
        try {
            // Download image from URL
            /** @var \Illuminate\Http\Client\Response $imageData */
            $imageData = Http::timeout(10)->get($imageUrl);
            
            if (!$imageData->ok()) {
                \Log::warning('Failed to download profile image from URL: ' . $imageUrl);
                return;
            }

            // Create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'profile_');
            file_put_contents($tempFile, $imageData->body());

            // Convert and save as JPEG
            $imagePath = $this->saveImageAsJpeg($tempFile, 'profile_picture', true);
            
            // Update user image
            $user->image = $imagePath;
            $user->save();

            // Clean up temp file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        } catch (\Exception $e) {
            \Log::error('Error saving profile image: ' . $e->getMessage());
        }
    }

    /**
     * Save image as JPEG format
     * 
     * @param mixed $imageFile File path or UploadedFile instance
     * @param string $directory Storage directory
     * @param bool $isTempFile Whether the file is a temporary file path
     * @return string Saved image path
     */
    protected function saveImageAsJpeg($imageFile, $directory = 'profile_picture', $isTempFile = false)
    {
        try {
            // Get file path
            $filePath = $isTempFile ? $imageFile : $imageFile->getRealPath();
            
            // Read image data
            $imageData = file_get_contents($filePath);
            if ($imageData === false) {
                throw new \Exception('Failed to read image file');
            }

            // Create image resource from string (works with any image format)
            $sourceImage = @imagecreatefromstring($imageData);
            
            if ($sourceImage === false) {
                throw new \Exception('Failed to create image from string');
            }

            // Generate unique filename with .jpeg extension
            $filename = uniqid() . '_' . time() . '.jpeg';
            $path = $directory . '/' . $filename;
            $fullPath = storage_path('app/public/' . $path);

            // Ensure directory exists
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            // Convert to JPEG and save (quality: 85)
            $success = imagejpeg($sourceImage, $fullPath, 85);
            
            // Free memory
            imagedestroy($sourceImage);

            if (!$success) {
                throw new \Exception('Failed to save JPEG image');
            }

            return $path;
        } catch (\Exception $e) {
            \Log::error('Error converting image to JPEG: ' . $e->getMessage());
            
            // Fallback: try using Intervention Image
            try {
                $manager = new ImageManager(new Driver());
                $filePath = $isTempFile ? $imageFile : $imageFile->getRealPath();
                $image = $manager->read($filePath);
                
                $filename = uniqid() . '_' . time() . '.jpeg';
                $path = $directory . '/' . $filename;
                $fullPath = storage_path('app/public/' . $path);
                
                $dir = dirname($fullPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                
                // Try to save as JPEG using Intervention Image
                $image->toJpeg(85)->save($fullPath);
                return $path;
            } catch (\Exception $interventionError) {
                \Log::error('Intervention Image fallback failed: ' . $interventionError->getMessage());
                
                // Final fallback: save original file with .jpeg extension
                $filename = uniqid() . '_' . time() . '.jpeg';
                $path = $directory . '/' . $filename;
                Storage::disk('public')->put($path, file_get_contents($filePath));
                return $path;
            }
        }
    }

    public function deleteAccount(Request $request, $id)
    {
        if (! config('app.debug')) {
            return response()->json(['error' => 'Not allowed in production.'], 405);
        }

        $user = User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully.'], 200);
    }
}
