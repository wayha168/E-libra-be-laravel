<?php

namespace App\Http\Controllers\Api\DrunkDrive;

use App\Http\Controllers\Api\Controller;
use App\Http\Responses\ApiResponses;
use App\Models\DrunkDrive\DriverProfile;
use App\Models\Role;
use App\Models\User;
use App\Support\DrunkDrive\LicenceOcrStub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = $this->createAccount($validated, 'user');
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        $token = $user->createToken('drunkdrive-api')->plainTextToken;

        return ApiResponses::created('DrunkDrive user registered', [
            'user' => $user->load('role'),
            'token' => $token,
        ]);
    }

    public function registerDriver(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],

            // Licence — scan and/or manual fields
            'licence_number' => ['nullable', 'string', 'max:64'],
            'licence_class' => ['nullable', 'string', 'max:32'],
            'licence_country' => ['nullable', 'string', 'max:64'],
            'licence_issued_at' => ['nullable', 'date'],
            'licence_expires_at' => ['nullable', 'date', 'after_or_equal:licence_issued_at'],
            'licence_scan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],

            // Address
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:120'],
            'address_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address_longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'vehicle_type' => ['nullable', 'string', 'max:64'],
            'vehicle_plate' => ['nullable', 'string', 'max:32'],
        ]);

        $hasManualLicence = filled($validated['licence_number'] ?? null);
        $hasScan = $request->hasFile('licence_scan');

        if (!$hasManualLicence && !$hasScan) {
            return response()->json([
                'message' => 'Provide licence_number (manual) and/or licence_scan (file upload).',
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request, $validated) {
                $user = $this->createAccount($validated, 'driver');

                $scanPath = null;
                if ($request->hasFile('licence_scan')) {
                    $scanPath = $request->file('licence_scan')->store('drunkdrive/licences', 'public');
                }

                $ocr = LicenceOcrStub::extract($request->file('licence_scan'), $validated);

                $profile = DriverProfile::create([
                    'user_id' => $user->id,
                    'licence_number' => $validated['licence_number'] ?? null,
                    'licence_class' => $validated['licence_class'] ?? null,
                    'licence_country' => $validated['licence_country'] ?? null,
                    'licence_issued_at' => $validated['licence_issued_at'] ?? null,
                    'licence_expires_at' => $validated['licence_expires_at'] ?? null,
                    'licence_scan_path' => $scanPath,
                    'licence_ocr_stub' => $ocr,
                    'address_line' => $validated['address_line'],
                    'city' => $validated['city'] ?? null,
                    'region' => $validated['region'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'country' => $validated['country'] ?? null,
                    'address_latitude' => $validated['address_latitude'] ?? null,
                    'address_longitude' => $validated['address_longitude'] ?? null,
                    'vehicle_type' => $validated['vehicle_type'] ?? null,
                    'vehicle_plate' => $validated['vehicle_plate'] ?? null,
                    'status' => 'pending',
                    'is_available' => false,
                ]);

                return [$user, $profile];
            });
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

        [$user, $profile] = $result;
        $token = $user->createToken('drunkdrive-api')->plainTextToken;

        return ApiResponses::created('DrunkDrive driver registered', [
            'user' => $user->load('role'),
            'driver_profile' => $profile,
            'token' => $token,
            'licence_scan_url' => $profile->licence_scan_path
                ? Storage::disk('public')->url($profile->licence_scan_path)
                : null,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::query()->where('email', $validated['email'])->first();

        if (!$user || !$user->password || !Hash::check($validated['password'], $user->password)) {
            return ApiResponses::unauthorized('Invalid credentials', null);
        }

        if (isset($user->status) && $user->status !== 'active') {
            return ApiResponses::unauthorized('Invalid credentials', null);
        }

        $allowed = ['user', 'driver', 'admin', 'super_admin', 'superadmin'];
        $roleName = $user->role?->role;
        if ($roleName && !in_array($roleName, $allowed, true) && !$user->isSuperAdmin()) {
            return ApiResponses::unauthorized('Account role is not permitted for DrunkDrive', null);
        }

        $token = $user->createToken('drunkdrive-api')->plainTextToken;

        return ApiResponses::ok('DrunkDrive login successful', [
            'user' => $user->load(['role', 'driverProfile']),
            'token' => $token,
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['role', 'driverProfile']);

        return ApiResponses::ok('DrunkDrive profile', [
            'user' => $user,
        ]);
    }

    private function createAccount(array $data, string $roleName): User
    {
        $roleId = Role::where('role', $roleName)->value('id');

        if (!$roleId && $roleName === 'superadmin') {
            $roleId = Role::where('role', 'super_admin')->value('id');
        }

        if (!$roleId) {
            throw new RuntimeException("Role '{$roleName}' is not configured. Run database seeders.");
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'confirm_password' => $data['password'],
            'role_id' => $roleId,
            'status' => 'active',
        ]);
    }
}
