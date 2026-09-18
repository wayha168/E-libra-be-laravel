<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Role;
use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        $role = Role::firstOrCreate(['role' => 'admin']);

        return User::create([
            'name' => 'Admin',
            'email' => 'admin-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ])->fresh();
    }

    public function test_register_records_activity_and_notifies_new_user_and_admins(): void
    {
        Role::firstOrCreate(['role' => 'user']);
        $admin = $this->makeAdmin();

        $response = $this->postJson('/api/v1/register', [
            'name' => 'New Reader',
            'email' => 'newreader@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated();

        $newUser = User::where('email', 'newreader@example.com')->firstOrFail();

        // Activity recorded (shows on the audit page).
        $this->assertDatabaseHas('user_activities', [
            'type' => 'user.registered',
            'user_id' => $newUser->id,
        ]);

        // New user gets a welcome notification.
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $newUser->id,
            'type' => 'user.welcome',
        ]);

        // Admin gets alerted about the registration.
        $this->assertDatabaseHas('app_notifications', [
            'user_id' => $admin->id,
            'type' => 'user.registered',
        ]);

        $this->assertSame(
            1,
            AppNotification::where('user_id', $admin->id)->where('type', 'user.registered')->count()
        );
        $this->assertSame(
            1,
            UserActivity::where('type', 'user.registered')->where('user_id', $newUser->id)->count()
        );
    }
}
