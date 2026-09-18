<?php

namespace Tests\Feature;

use App\Models\Books;
use App\Models\Role;
use App\Models\User;
use App\Models\UserBuyBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class StripeSessionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Keep Stripe unconfigured so the check never calls the live API.
        config(['services.stripe.secret' => null]);
    }

    private function createUser(string $roleName = 'user'): User
    {
        $role = Role::firstOrCreate(['role' => $roleName]);

        return User::create([
            'name' => ucfirst($roleName) . ' User',
            'email' => $roleName . '-' . uniqid('', true) . '@example.com',
            'password' => Hash::make('password'),
            'confirm_password' => 'password',
            'role_id' => $role->id,
            'status' => 'active',
        ])->fresh();
    }

    private function createBook(float $price = 9.99): Books
    {
        return Books::create([
            'title' => 'Stripe Status Book',
            'description' => 'For stripe session status tests',
            'price' => $price,
        ])->fresh();
    }

    public function test_returns_404_when_session_is_unknown(): void
    {
        Sanctum::actingAs($this->createUser(), ['*']);

        $this->getJson('/api/v1/stripe/status?session_id=cs_test_missing')
            ->assertNotFound()
            ->assertJsonPath('message', 'Purchase not found for this session.');
    }

    public function test_returns_paid_status_for_completed_purchase(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        $purchase = UserBuyBook::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'payment_method' => 'card',
            'status' => 'paid',
            'stripe_checkout_session_id' => 'cs_test_done',
            'purchased_at' => now(),
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/stripe/status?session_id=cs_test_done')
            ->assertOk()
            ->assertJsonPath('message', 'Purchase already paid.')
            ->assertJsonPath('data.local_status', 'paid')
            ->assertJsonPath('data.purchase.id', $purchase->id);

        // Lookup by purchase_id works too.
        $this->getJson("/api/v1/stripe/status?purchase_id={$purchase->id}")
            ->assertOk()
            ->assertJsonPath('data.local_status', 'paid');
    }

    public function test_pending_session_stays_pending_when_stripe_not_configured(): void
    {
        $user = $this->createUser();
        $book = $this->createBook();

        UserBuyBook::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'payment_method' => 'card',
            'status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_pending',
        ]);

        Sanctum::actingAs($user, ['*']);

        $this->getJson('/api/v1/stripe/status?session_id=cs_test_pending')
            ->assertOk()
            ->assertJsonPath('message', 'Stripe checkout session status fetched.')
            ->assertJsonPath('data.local_status', 'pending')
            ->assertJsonPath('data.author_earnings', 0);
    }

    public function test_other_user_cannot_check_someone_elses_session(): void
    {
        $owner = $this->createUser();
        $other = $this->createUser();
        $book = $this->createBook();

        UserBuyBook::create([
            'user_id' => $owner->id,
            'book_id' => $book->id,
            'amount' => 9.99,
            'payment_method' => 'card',
            'status' => 'pending',
            'stripe_checkout_session_id' => 'cs_test_owner',
        ]);

        Sanctum::actingAs($other, ['*']);

        $this->getJson('/api/v1/stripe/status?session_id=cs_test_owner')
            ->assertForbidden();
    }

    public function test_validation_requires_a_session_or_purchase_identifier(): void
    {
        Sanctum::actingAs($this->createUser(), ['*']);

        $this->getJson('/api/v1/stripe/status')
            ->assertStatus(422);
    }
}
