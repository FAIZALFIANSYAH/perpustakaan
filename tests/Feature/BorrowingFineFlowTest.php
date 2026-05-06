<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Category;
use App\Models\Fine;
use App\Models\FineConfig;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BorrowingFineFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Super Admin']);
        Role::create(['name' => 'Librarian']);
        Role::create(['name' => 'Member']);

        FineConfig::create([
            'grace_period_days' => 0,
            'max_borrowing_days' => 7,
            'fine_per_day' => 2000,
            'max_billable_days' => 30,
            'lost_book_fine' => 50000,
            'max_fine_per_item' => 1000000,
            'max_fine_per_borrowing' => 5000000,
            'lost_book_payment_deadline' => 14,
            'max_fine_cap' => null,
            'is_active' => true,
        ]);
    }

    public function test_on_time_return_does_not_create_fine(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(2)->toDateString(), now()->addDay()->toDateString(), 2);

        $response = $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), [
            'items' => [
                ['id' => $borrowing->items->first()->id, 'return_quantity' => 2],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseCount('fines', 0);
        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'returned',
        ]);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'stock' => 10,
        ]);
    }

    public function test_late_return_creates_late_fine(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(8)->toDateString(), now()->subDays(2)->toDateString(), 1);

        $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), [
            'items' => [
                ['id' => $borrowing->items->first()->id, 'return_quantity' => 1],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('fines', [
            'borrowing_item_id' => $borrowing->items->first()->id,
            'type' => 'late_return',
            'status' => 'unpaid',
        ]);
    }

    public function test_lost_partial_creates_lost_fine_and_sets_partial_status(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(3)->toDateString(), now()->addDays(3)->toDateString(), 3);

        $this->actingAs($admin)->post(route('admin.fines.report-lost', [
            'borrowing' => $borrowing->id,
            'borrowingItem' => $borrowing->items->first()->id,
        ]), [
            'lost_quantity' => 1,
            'notes' => 'hilang di perjalanan',
        ])->assertRedirect();

        $this->assertDatabaseHas('fines', [
            'type' => 'lost_book',
            'member_id' => $member->id,
            'status' => 'unpaid',
        ]);
        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'partial',
        ]);
    }

    public function test_lost_full_sets_lost_status_when_unpaid_fine_exists(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(3)->toDateString(), now()->addDays(3)->toDateString(), 2);

        $this->actingAs($admin)->post(route('admin.fines.report-lost', [
            'borrowing' => $borrowing->id,
            'borrowingItem' => $borrowing->items->first()->id,
        ]), [
            'lost_quantity' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('borrowings', [
            'id' => $borrowing->id,
            'status' => 'lost',
        ]);
    }

    public function test_partial_payment_sets_fine_status_partial(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(8)->toDateString(), now()->subDays(2)->toDateString(), 1);

        $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), [
            'items' => [['id' => $borrowing->items->first()->id, 'return_quantity' => 1]],
        ]);

        $fine = Fine::firstOrFail();
        $half = (float) $fine->amount / 2;

        $this->actingAs($admin)->post(route('admin.fines.payment', $fine), [
            'amount' => $half,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $fine->refresh();
        $this->assertSame('partial', $fine->status);
        $this->assertEqualsWithDelta($half, (float) $fine->paid_amount, 0.01);
    }

    public function test_full_payment_sets_fine_paid(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(8)->toDateString(), now()->subDays(2)->toDateString(), 1);

        $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), [
            'items' => [['id' => $borrowing->items->first()->id, 'return_quantity' => 1]],
        ]);

        $fine = Fine::firstOrFail();
        $this->actingAs($admin)->post(route('admin.fines.payment', $fine), [
            'amount' => $fine->amount,
            'payment_method' => 'transfer',
        ])->assertRedirect();

        $this->assertDatabaseHas('fines', [
            'id' => $fine->id,
            'status' => 'paid',
        ]);
    }

    public function test_double_submit_payment_second_attempt_is_rejected(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(8)->toDateString(), now()->subDays(2)->toDateString(), 1);

        $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), [
            'items' => [['id' => $borrowing->items->first()->id, 'return_quantity' => 1]],
        ]);

        $fine = Fine::firstOrFail();

        $this->actingAs($admin)->post(route('admin.fines.payment', $fine), [
            'amount' => $fine->amount,
            'payment_method' => 'cash',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('admin.fines.payment', $fine), [
            'amount' => 1000,
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('fine');
    }

    public function test_double_submit_return_second_attempt_is_rejected(): void
    {
        [$admin, $member, $book] = $this->createBaseActors();
        $borrowing = $this->createBorrowing($admin, $member, $book, now()->subDays(2)->toDateString(), now()->addDay()->toDateString(), 1);

        $payload = ['items' => [['id' => $borrowing->items->first()->id, 'return_quantity' => 1]]];

        $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), $payload)->assertRedirect();
        $this->actingAs($admin)->post(route('admin.borrowings.return', $borrowing), $payload)->assertSessionHasErrors('items.0.return_quantity');
    }

    public function test_role_access_for_borrowing_fine_routes(): void
    {
        [$admin, $librarian, $member] = $this->createRoleUsers();

        $this->actingAs($admin)->get(route('admin.fines.index'))->assertOk();
        $this->actingAs($librarian)->get(route('librarian.fines.index'))->assertOk();
        $this->actingAs($member)->get(route('member.fines.index'))->assertOk();

        $this->actingAs($member)->get(route('admin.fines.index'))->assertForbidden();
    }

    private function createBaseActors(): array
    {
        [$admin, , $member] = $this->createRoleUsers();
        $category = Category::create(['name' => 'Teknologi', 'slug' => 'teknologi']);
        $book = Book::create([
            'category_id' => $category->id,
            'title' => 'Clean Architecture',
            'author' => 'Robert C. Martin',
            'publisher' => 'Prentice Hall',
            'isbn' => 'ISBN-' . fake()->unique()->numerify('#####'),
            'publish_year' => 2018,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$admin, $member, $book];
    }

    private function createRoleUsers(): array
    {
        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $librarian = User::factory()->create();
        $librarian->assignRole('Librarian');

        $member = User::factory()->create(['borrow_limit' => 5]);
        $member->assignRole('Member');

        return [$admin, $librarian, $member];
    }

    private function createBorrowing(User $admin, User $member, Book $book, string $borrowedAt, string $dueAt, int $qty): Borrowing
    {
        $this->actingAs($admin)->post(route('admin.borrowings.store'), [
            'member_id' => $member->id,
            'borrowed_at' => $borrowedAt,
            'due_at' => $dueAt,
            'items' => [
                ['book_id' => $book->id, 'quantity' => $qty],
            ],
        ])->assertRedirect();

        return Borrowing::with('items')->latest('id')->firstOrFail();
    }
}
