<?php

namespace Database\Seeders;

use App\Models\AppNotification;
use App\Models\Author;
use App\Models\BankAccount;
use App\Models\BookComment;
use App\Models\BookLike;
use App\Models\Books;
use App\Models\Category;
use App\Models\Permission;
use App\Models\User;
use App\Models\UserActivity;
use App\Models\UserBuyBook;
use App\Models\UserCategoryPermission;
use App\Support\PurchaseCommission;
use Illuminate\Database\Seeder;

class TemplateDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedPurchases();
        $this->seedBookFeedback();
        $this->seedBankAccounts();
        $this->seedNotifications();
        $this->seedActivities();
        $this->seedCategoryPermissions();
    }

    private function seedPurchases(): void
    {
        $books = Books::whereNotNull('price')->where('price', '>', 0)->take(8)->get();
        $buyers = User::whereHas('role', fn ($q) => $q->whereIn('role', ['user', 'author']))->get();

        if ($books->isEmpty() || $buyers->isEmpty()) {
            return;
        }

        $methods = ['card', 'card', 'khqr'];

        foreach ($books as $i => $book) {
            $buyer = $buyers[$i % $buyers->count()];

            $purchase = UserBuyBook::updateOrCreate(
                ['user_id' => $buyer->id, 'book_id' => $book->id],
                [
                    'amount' => $book->price,
                    'payment_method' => $methods[$i % count($methods)],
                    'status' => $i % 5 === 0 ? 'pending' : 'paid',
                    'purchased_at' => $i % 5 === 0 ? null : now()->subDays($i + 1),
                ]
            );

            if ($purchase->status === 'paid') {
                PurchaseCommission::applyToPurchase($purchase->fresh());
            }
        }
    }

    private function seedBookFeedback(): void
    {
        $books = Books::take(10)->get();
        $users = User::take(6)->get();

        foreach ($books as $i => $book) {
            foreach ($users->take(2 + ($i % 3)) as $user) {
                BookLike::firstOrCreate([
                    'user_id' => $user->id,
                    'book_id' => $book->id,
                ]);
            }

            BookComment::firstOrCreate(
                [
                    'user_id' => $users[$i % $users->count()]->id,
                    'book_id' => $book->id,
                    'body' => 'Sample review for "' . $book->title . '" — great read!',
                ]
            );
        }
    }

    private function seedBankAccounts(): void
    {
        $staff = User::whereHas('role', fn ($q) => $q->whereIn('role', ['author', 'admin', 'super_admin']))->get();

        foreach ($staff as $i => $user) {
            BankAccount::updateOrCreate(
                ['user_id' => $user->id, 'provider' => 'bank', 'account_number' => '100000' . str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT)],
                [
                    'bank_name' => 'ABA Bank',
                    'account_holder' => $user->name,
                    'branch' => 'Phnom Penh Main',
                    'is_default' => true,
                ]
            );

            if ($user->isAuthor()) {
                $user->update([
                    'payway_account' => 'payway_' . strtolower(str_replace(' ', '', $user->name)),
                    'bakong_account' => 'bakong@example.com',
                ]);
            }
        }
    }

    private function seedNotifications(): void
    {
        $staff = User::whereHas('role', fn ($q) => $q->whereIn('role', ['admin', 'author', 'super_admin']))->get();

        foreach ($staff as $i => $user) {
            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'system',
                'title' => 'Welcome to e-Libra',
                'body' => 'Your dashboard is ready. Explore books, earnings, and notifications.',
                'read_at' => $i % 2 === 0 ? now() : null,
            ]);

            AppNotification::create([
                'user_id' => $user->id,
                'type' => 'sale',
                'title' => 'New book sale',
                'body' => 'Someone purchased one of your books (sample data).',
                'data' => ['sample' => true],
                'read_at' => null,
            ]);
        }
    }

    private function seedActivities(): void
    {
        $admin = User::whereHas('role', fn ($q) => $q->where('role', 'admin'))->first();
        $author = User::whereHas('role', fn ($q) => $q->where('role', 'author'))->first();

        if (!$admin) {
            return;
        }

        UserActivity::create([
            'user_id' => $author?->id,
            'actor_id' => $admin->id,
            'type' => 'login',
            'title' => 'User signed in',
            'description' => 'Sample activity: author logged into the dashboard.',
        ]);

        UserActivity::create([
            'user_id' => null,
            'actor_id' => $admin->id,
            'type' => 'book_created',
            'title' => 'Book published',
            'description' => 'Sample activity: a new book was added to the catalog.',
            'metadata' => ['sample' => true],
        ]);
    }

    private function seedCategoryPermissions(): void
    {
        $permission = Permission::where('name', 'view_categories')->first();
        $user = User::whereHas('role', fn ($q) => $q->where('role', 'user'))->first();
        $category = Category::where('slug', 'fiction')->first();

        if (!$permission || !$user || !$category) {
            return;
        }

        UserCategoryPermission::firstOrCreate([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'permission_id' => $permission->id,
        ]);
    }
}
