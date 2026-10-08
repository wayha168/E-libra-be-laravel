<?php

namespace App\Http\Controllers\Api\DrunkDrive;

use App\Http\Controllers\Api\Controller;
use App\Http\Responses\ApiResponses;
use App\Models\DrunkDrive\DriverProfile;
use App\Support\DrunkDrive\LicenceOcrStub;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class DriverController extends Controller
{
    public function profile(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;

        if (!$profile) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }

        return ApiResponses::ok('Driver profile', $this->present($profile));
    }

    public function updateLicence(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->driverProfile;

        if (!$profile) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }

        $validated = $request->validate([
            'licence_number' => ['nullable', 'string', 'max:64'],
            'licence_class' => ['nullable', 'string', 'max:32'],
            'licence_country' => ['nullable', 'string', 'max:64'],
            'licence_issued_at' => ['nullable', 'date'],
            'licence_expires_at' => ['nullable', 'date', 'after_or_equal:licence_issued_at'],
            'licence_scan' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'address_line' => ['sometimes', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:120'],
            'address_latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'address_longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'vehicle_type' => ['nullable', 'string', 'max:64'],
            'vehicle_plate' => ['nullable', 'string', 'max:32'],
        ]);

        if ($request->hasFile('licence_scan')) {
            if ($profile->licence_scan_path) {
                Storage::disk('public')->delete($profile->licence_scan_path);
            }
            $validated['licence_scan_path'] = $request->file('licence_scan')
                ->store('drunkdrive/licences', 'public');
            $validated['licence_ocr_stub'] = LicenceOcrStub::extract(
                $request->file('licence_scan'),
                $validated
            );
        }

        unset($validated['licence_scan']);
        $profile->update($validated);

        return ApiResponses::ok('Driver licence/profile updated', $this->present($profile->fresh()));
    }

    public function updateLocation(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;

        if (!$profile) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }

        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'is_available' => ['nullable', 'boolean'],
        ]);

        $profile->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
            'location_updated_at' => now(),
            'is_available' => array_key_exists('is_available', $validated)
                ? (bool) $validated['is_available']
                : $profile->is_available,
        ]);

        return ApiResponses::ok('Driver location updated', $this->present($profile->fresh()));
    }

    public function updateAvailability(Request $request): JsonResponse
    {
        $profile = $request->user()->driverProfile;

        if (!$profile) {
            return response()->json(['message' => 'Driver profile not found'], 404);
        }

        $validated = $request->validate([
            'is_available' => ['required', 'boolean'],
        ]);

        $profile->update(['is_available' => $validated['is_available']]);

        return ApiResponses::ok('Driver availability updated', $this->present($profile->fresh()));
    }

    public function approve(Request $request, DriverProfile $driverProfile): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['pending', 'approved', 'rejected', 'suspended'])],
        ]);

        $driverProfile->update(['status' => $validated['status']]);

        return ApiResponses::ok('Driver status updated', $this->present($driverProfile->fresh()));
    }

    public function index(Request $request): JsonResponse
    {
        $drivers = DriverProfile::query()
            ->with('user:id,name,email,status')
            ->orderByDesc('created_at')
            ->paginate(20);

        return ApiResponses::ok('Drivers fetched', $drivers);
    }

    private function present(DriverProfile $profile): array
    {
        return [
            'profile' => $profile,
            'licence_scan_url' => $profile->licence_scan_path
                ? Storage::disk('public')->url($profile->licence_scan_path)
                : null,
        ];
    }
}
