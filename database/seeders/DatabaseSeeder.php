<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Author;
use App\Models\Book;
use App\Models\BookCopy;
use App\Models\Genre;
use App\Models\Loan;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\RefreshToken;
use App\Models\Reservation;
use App\Models\RevokedAccessToken;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@biblio.test'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Root',
                'password_hash' => Hash::make('Admin123!'),
                'role' => 'admin',
                'is_active' => true,
                'qr_code' => 'MEM-ADMIN-001',
                'email_verified_at' => now(),
            ]
        );

        $librarian = User::query()->updateOrCreate(
            ['email' => 'librarian@biblio.test'],
            [
                'first_name' => 'Lina',
                'last_name' => 'Rian',
                'password_hash' => Hash::make('Librarian123!'),
                'role' => 'librarian',
                'is_active' => true,
                'qr_code' => 'MEM-LIB-001',
                'email_verified_at' => now(),
            ]
        );

        $readers = collect([
            ['reader1@biblio.test', 'Reader', 'One', 'MEM-READER-001'],
            ['reader2@biblio.test', 'Reader', 'Two', 'MEM-READER-002'],
            ['reader3@biblio.test', 'Reader', 'Three', 'MEM-READER-003'],
            ['reader4@biblio.test', 'Reader', 'Four', 'MEM-READER-004'],
        ])->map(function (array $data) {
            return User::query()->updateOrCreate(
                ['email' => $data[0]],
                [
                    'first_name' => $data[1],
                    'last_name' => $data[2],
                    'password_hash' => Hash::make('Reader123!'),
                    'role' => 'reader',
                    'is_active' => true,
                    'qr_code' => $data[3],
                    'email_verified_at' => now(),
                ]
            );
        });

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $admin->email],
            ['token' => Hash::make('seed-reset-token'), 'created_at' => now()]
        );

        DB::table('sessions')->updateOrInsert(
            ['id' => 'seed-session-1'],
            [
                'user_id' => $admin->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Seeder',
                'payload' => base64_encode('seed'),
                'last_activity' => now()->timestamp,
            ]
        );

        DB::table('cache')->updateOrInsert(
            ['key' => 'seed:welcome'],
            ['value' => serialize(['message' => 'hello']), 'expiration' => now()->addDay()->timestamp]
        );

        DB::table('cache_locks')->updateOrInsert(
            ['key' => 'seed:lock'],
            ['owner' => 'seeder', 'expiration' => now()->addMinutes(5)->timestamp]
        );

        DB::table('jobs')->updateOrInsert(
            ['id' => 1],
            [
                'queue' => 'default',
                'payload' => json_encode(['displayName' => 'SeedJob']),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->timestamp,
                'created_at' => now()->timestamp,
            ]
        );

        DB::table('job_batches')->updateOrInsert(
            ['id' => 'seed-batch-1'],
            [
                'name' => 'seed batch',
                'total_jobs' => 1,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_job_ids' => json_encode([]),
                'options' => json_encode([]),
                'cancelled_at' => null,
                'created_at' => now()->timestamp,
                'finished_at' => now()->timestamp,
            ]
        );

        DB::table('failed_jobs')->updateOrInsert(
            ['uuid' => 'seed-failed-job-uuid-001'],
            [
                'connection' => 'sqlite',
                'queue' => 'default',
                'payload' => json_encode(['displayName' => 'FailedSeedJob']),
                'exception' => 'Seed failure sample',
                'failed_at' => now(),
            ]
        );

        $authorsData = [
            ['Victor', 'Hugo', 'France'],
            ['Jane', 'Austen', 'UK'],
            ['Albert', 'Camus', 'France'],
            ['Naguib', 'Mahfouz', 'Egypt'],
            ['George', 'Orwell', 'UK'],
        ];

        $authors = collect($authorsData)->map(fn (array $data) => Author::query()->create([
            'first_name' => $data[0],
            'last_name' => $data[1],
            'biography' => "{$data[0]} {$data[1]} bio.",
            'nationality' => $data[2],
        ]));

        $genresData = [
            ['Roman', 'roman', '#3B82F6'],
            ['Science', 'science', '#10B981'],
            ['Histoire', 'histoire', '#F59E0B'],
            ['Philosophie', 'philosophie', '#8B5CF6'],
            ['BD', 'bd', '#EF4444'],
        ];

        $genres = collect($genresData)->map(fn (array $data) => Genre::query()->create([
            'name' => $data[0],
            'slug' => $data[1],
            'description' => "Genre {$data[0]}",
            'color_hex' => $data[2],
        ]));

        $booksData = [
            ['9782070409189', 'Les Miserables', 'fr', 1862],
            ['9780141439518', 'Pride and Prejudice', 'en', 1813],
            ['9782070360022', 'L\'Etranger', 'fr', 1942],
            ['9789770937297', 'Palace Walk', 'en', 1956],
            ['9780451524935', '1984', 'en', 1949],
            ['9782070413117', 'Notre-Dame de Paris', 'fr', 1831],
            ['9780141439600', 'Sense and Sensibility', 'en', 1811],
            ['9782070364129', 'La Peste', 'fr', 1947],
        ];

        $books = collect($booksData)->map(function (array $data, int $index) use ($authors, $genres) {
            $totalCopies = ($index % 3) + 2; // 2..4

            $book = Book::query()->create([
                'isbn' => $data[0],
                'title' => $data[1],
                'synopsis' => "Synopsis for {$data[1]}",
                'cover_url' => null,
                'published_year' => $data[3],
                'language' => $data[2],
                'total_copies' => $totalCopies,
                'available_copies' => $totalCopies,
                'average_rating' => 4.20,
            ]);

            $book->authors()->sync([
                $authors[$index % $authors->count()]->id,
            ]);

            $book->genres()->sync([
                $genres[$index % $genres->count()]->id,
            ]);

            return $book;
        });

        $allCopies = collect();

        foreach ($books as $book) {
            for ($i = 1; $i <= $book->total_copies; $i++) {
                $allCopies->push(BookCopy::query()->create([
                    'book_id' => $book->id,
                    'qr_code' => 'COPY-'.Str::upper(Str::random(10)),
                    'condition' => $i % 2 === 0 ? 'good' : 'excellent',
                    'shelf_location' => 'S-'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                ]));
            }
        }

        $loanReaders = $readers->values();
        $activeLoans = collect();

        foreach ($allCopies->take(8) as $idx => $copy) {
            $reader = $loanReaders[$idx % $loanReaders->count()];
            $isReturned = $idx % 3 === 0;
            $status = $isReturned ? 'returned' : 'active';

            $loan = Loan::query()->create([
                'user_id' => $reader->id,
                'book_copy_id' => $copy->id,
                'loan_date' => now()->subDays($idx + 2)->toDateString(),
                'due_date' => now()->addDays(14 - $idx)->toDateString(),
                'return_date' => $isReturned ? now()->subDay()->toDateString() : null,
                'status' => $status,
                'renewal_count' => $idx % 2,
                'created_by' => $librarian->id,
            ]);

            if ($loan->status->value === 'active') {
                $activeLoans->push($loan);
            }
        }

        foreach ($books as $book) {
            $activeForBook = Loan::query()
                ->whereHas('bookCopy', fn ($q) => $q->where('book_id', $book->id))
                ->where('status', 'active')
                ->count();

            $book->update([
                'available_copies' => max($book->total_copies - $activeForBook, 0),
            ]);
        }

        foreach ($books->take(3) as $idx => $book) {
            Reservation::query()->create([
                'user_id' => $loanReaders[($idx + 1) % $loanReaders->count()]->id,
                'book_id' => $book->id,
                'status' => $idx === 0 ? 'pending' : 'fulfilled',
                'requested_at' => now()->subDays($idx + 1),
                'expires_at' => $idx === 0 ? now()->addDays(2) : now()->subDay(),
            ]);
        }

        foreach ($books->take(5) as $idx => $book) {
            Rating::query()->create([
                'user_id' => $loanReaders[$idx % $loanReaders->count()]->id,
                'book_id' => $book->id,
                'score' => 4 + ($idx % 2),
                'comment' => 'Great read '.($idx + 1),
            ]);
        }

        $usersForNotifications = collect([$admin, $librarian])->merge($readers);
        foreach ($usersForNotifications as $idx => $user) {
            Notification::query()->create([
                'user_id' => $user->id,
                'type' => $idx % 2 === 0 ? 'loan_created' : 'reservation_available',
                'payload' => ['message' => 'Seed notification '.($idx + 1)],
                'is_read' => $idx % 3 === 0,
            ]);
        }

        foreach ([$admin, $librarian, $readers[0]] as $user) {
            AuditLog::query()->create([
                'user_id' => $user->id,
                'action' => 'seeded_action',
                'entity_type' => 'user',
                'entity_id' => $user->id,
                'ip_address' => '127.0.0.1',
            ]);
        }

        RefreshToken::query()->create([
            'user_id' => $admin->id,
            'token_hash' => hash('sha256', 'seed-refresh-admin'),
            'expires_at' => now()->addDays(7),
            'last_used_at' => now(),
        ]);

        RefreshToken::query()->create([
            'user_id' => $readers[0]->id,
            'token_hash' => hash('sha256', 'seed-refresh-reader-1'),
            'expires_at' => now()->addDays(7),
            'revoked_at' => now(),
            'last_used_at' => now()->subDay(),
        ]);

        RevokedAccessToken::query()->create([
            'jti' => 'seed-revoked-jti-1',
            'expires_at' => now()->addMinutes(15),
        ]);
    }
}
