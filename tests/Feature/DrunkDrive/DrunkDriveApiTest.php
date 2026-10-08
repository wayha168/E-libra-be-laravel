<?php

namespace Tests\Feature\DrunkDrive;

use App\Models\DrunkDrive\DriverProfile;
use App\Models\DrunkDrive\Store;
use App\Models\DrunkDrive\Trip;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DrunkDriveApiTest extends TestCase
{
    use RefreshDatabase;

    private function role(string $name): Role
    {
        return Role::firstOrCreate(['role' => $name]);
    }

    private function createUser(string $roleName = 'user', array $overrides = []): User
    {
        $role = $this->role($roleName);

        return User::create(array_merge([
            'name' => ucfirst($roleName).' DD',
            'email' => $roleName.'-'.uniqid().'@drunkdrive.test',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ], $overrides));
    }

    public function test_user_can_register_and_login(): void
    {
        $this->role('user');

        $register = $this->postJson('/api/v1/drunkdrive/auth/register', [
            'name' => 'Rider One',
            'email' => 'rider@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $register->assertCreated()
            ->assertJsonPath('data.user.email', 'rider@example.com')
            ->assertJsonStructure(['data' => ['token', 'user']]);

        $login = $this->postJson('/api/v1/drunkdrive/auth/login', [
            'email' => 'rider@example.com',
            'password' => 'password123',
        ]);

        $login->assertOk()->assertJsonStructure(['data' => ['token']]);
    }

    public function test_driver_register_requires_licence_scan_or_manual(): void
    {
        $this->role('driver');

        $this->postJson('/api/v1/drunkdrive/auth/register-driver', [
            'name' => 'Driver No Licence',
            'email' => 'nolicence@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'address_line' => '1 Main St',
        ])->assertStatus(422);
    }

    public function test_driver_can_register_with_scan_and_manual_fields(): void
    {
        Storage::fake('public');
        $this->role('driver');

        $response = $this->post('/api/v1/drunkdrive/auth/register-driver', [
            'name' => 'Driver One',
            'email' => 'driver1@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'licence_number' => 'DL-12345',
            'licence_class' => 'B',
            'address_line' => '22 River Rd',
            'city' => 'Jakarta',
            'licence_scan' => UploadedFile::fake()->image('licence.jpg'),
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.driver_profile.licence_number', 'DL-12345')
            ->assertJsonPath('data.driver_profile.status', 'pending');

        $this->assertDatabaseHas('dd_driver_profiles', [
            'licence_number' => 'DL-12345',
        ]);
    }

    public function test_role_guard_blocks_user_from_creating_store(): void
    {
        $user = $this->createUser('user');
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/api/v1/drunkdrive/stores', [
            'name' => 'Blocked Store',
        ])->assertForbidden();
    }

    public function test_admin_can_create_store(): void
    {
        $admin = $this->createUser('admin');
        Sanctum::actingAs($admin, ['*']);

        $this->postJson('/api/v1/drunkdrive/stores', [
            'name' => 'Night Depot',
            'address_line' => '9 Depot Ave',
            'latitude' => -6.2,
            'longitude' => 106.8,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Night Depot');

        $this->assertDatabaseHas('dd_stores', ['name' => 'Night Depot']);
    }

    public function test_super_admin_alias_can_create_store(): void
    {
        $super = $this->createUser('super_admin');
        Sanctum::actingAs($super, ['*']);

        $this->postJson('/api/v1/drunkdrive/stores', [
            'name' => 'Super Store',
        ])->assertCreated();
    }

    public function test_fare_quote_matches_anchors(): void
    {
        $this->postJson('/api/v1/drunkdrive/fare/quote', ['distance_km' => 1])
            ->assertOk()
            ->assertJsonPath('data.fare_r', 2000);

        $this->postJson('/api/v1/drunkdrive/fare/quote', ['distance_km' => 5])
            ->assertOk()
            ->assertJsonPath('data.fare_r', 5000);
    }

    public function test_nearby_drivers_returns_available_within_radius(): void
    {
        $rider = $this->createUser('user');
        $driverUser = $this->createUser('driver');

        DriverProfile::create([
            'user_id' => $driverUser->id,
            'address_line' => 'Near',
            'status' => 'approved',
            'is_available' => true,
            'current_latitude' => -6.2000,
            'current_longitude' => 106.8167,
            'location_updated_at' => now(),
            'vehicle_type' => 'car',
        ]);

        $farDriver = $this->createUser('driver');
        DriverProfile::create([
            'user_id' => $farDriver->id,
            'address_line' => 'Far',
            'status' => 'approved',
            'is_available' => true,
            'current_latitude' => -7.2500,
            'current_longitude' => 112.7500,
            'location_updated_at' => now(),
        ]);

        Sanctum::actingAs($rider, ['*']);

        $response = $this->getJson('/api/v1/drunkdrive/drivers/nearby?latitude=-6.2001&longitude=106.8168&radius_km=5');

        $response->assertOk()
            ->assertJsonPath('data.count', 1)
            ->assertJsonPath('data.drivers.0.driver_id', $driverUser->id);
    }

    public function test_user_sees_own_trip_history_admin_sees_all(): void
    {
        $rider = $this->createUser('user');
        $other = $this->createUser('user');
        $admin = $this->createUser('admin');

        Trip::create([
            'user_id' => $rider->id,
            'status' => 'completed',
            'pickup_latitude' => -6.2,
            'pickup_longitude' => 106.8,
            'distance_km' => 1,
            'fare_r' => 2000,
            'requested_at' => now(),
        ]);

        Trip::create([
            'user_id' => $other->id,
            'status' => 'completed',
            'pickup_latitude' => -6.3,
            'pickup_longitude' => 106.9,
            'distance_km' => 5,
            'fare_r' => 5000,
            'requested_at' => now(),
        ]);

        Sanctum::actingAs($rider, ['*']);
        $mine = $this->getJson('/api/v1/drunkdrive/trips/history');
        $mine->assertOk();
        $this->assertCount(1, $mine->json('data.data'));

        Sanctum::actingAs($admin, ['*']);
        $all = $this->getJson('/api/v1/drunkdrive/trips/history');
        $all->assertOk();
        $this->assertCount(2, $all->json('data.data'));
    }

    public function test_user_can_request_trip_with_fare(): void
    {
        $rider = $this->createUser('user');
        Sanctum::actingAs($rider, ['*']);

        $this->postJson('/api/v1/drunkdrive/trips', [
            'pickup_latitude' => -6.2,
            'pickup_longitude' => 106.8,
            'dropoff_latitude' => -6.21,
            'dropoff_longitude' => 106.81,
            'distance_km' => 5,
        ])->assertCreated()
            ->assertJsonPath('data.fare_r', 5000)
            ->assertJsonPath('data.status', 'requested');
    }
}
