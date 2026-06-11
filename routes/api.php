<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\AuthorController;
use App\Http\Controllers\Api\V1\BookController;
use App\Http\Controllers\Api\V1\BookCopyController;
use App\Http\Controllers\Api\V1\GenreController;
use App\Http\Controllers\Api\V1\LoanController;
use App\Http\Controllers\Api\V1\MemberController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
        Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:login');
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);

        Route::middleware('auth.jwt')->group(function () {
            Route::post('/logout', [AuthController::class, 'logout']);
            Route::get('/me', [AuthController::class, 'me']);
        });
    });

    Route::middleware(['auth.jwt'])->group(function () {
        Route::prefix('search')->group(function () {
            Route::get('/books', [SearchController::class, 'books']);
            Route::get('/users', [SearchController::class, 'users']);
            Route::get('/book-copies', [SearchController::class, 'bookCopies']);
            Route::get('/book-copies-by-book/{bookId}', [SearchController::class, 'bookCopiesByBookId']);
        });

        Route::get('/members/profile', [MemberController::class, 'profile']);
        Route::get('/members/{member}/history', [MemberController::class, 'history']);
        Route::get('/members/{member}/qr', [MemberController::class, 'qr']);
        Route::patch('/members/{member}/status', [MemberController::class, 'updateStatus'])->middleware('role:librarian,admin');
        Route::get('/members', [MemberController::class, 'index'])->middleware('role:librarian,admin');
        Route::post('/members', [MemberController::class, 'store'])->middleware('role:librarian,admin');
        Route::get('/members/{member}', [MemberController::class, 'show']);
        Route::put('/members/{member}', [MemberController::class, 'update']);
        Route::patch('/members/{member}', [MemberController::class, 'update']);
        Route::delete('/members/{member}', [MemberController::class, 'destroy'])->middleware('role:admin');

        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);

        Route::get('/loans', [LoanController::class, 'index']);
        Route::post('/loans', [LoanController::class, 'store'])->middleware('role:librarian,admin');
        Route::post('/loans/scan/member', [LoanController::class, 'scanMember']);
        Route::post('/loans/scan/book', [LoanController::class, 'scanBook']);
        Route::post('/loans/{loan}/return', [LoanController::class, 'returnLoan'])->middleware('role:librarian,admin');
        Route::post('/loans/{loan}/renew', [LoanController::class, 'renew']);

        Route::get('/books', [BookController::class, 'index']);
        Route::get('/books/{book}', [BookController::class, 'show']);
        Route::post('/books', [BookController::class, 'store'])->middleware('role:librarian,admin');
        Route::put('/books/{book}', [BookController::class, 'update'])->middleware('role:librarian,admin');
        Route::patch('/books/{book}', [BookController::class, 'update'])->middleware('role:librarian,admin');
        Route::delete('/books/{book}', [BookController::class, 'destroy'])->middleware('role:admin');

        Route::get('/authors', [AuthorController::class, 'index']);
        Route::get('/authors/{author}', [AuthorController::class, 'show']);
        Route::post('/authors', [AuthorController::class, 'store'])->middleware('role:librarian,admin');
        Route::put('/authors/{author}', [AuthorController::class, 'update'])->middleware('role:librarian,admin');
        Route::patch('/authors/{author}', [AuthorController::class, 'update'])->middleware('role:librarian,admin');
        Route::delete('/authors/{author}', [AuthorController::class, 'destroy'])->middleware('role:admin');

        Route::get('/genres', [GenreController::class, 'index']);
        Route::get('/genres/{genre}', [GenreController::class, 'show']);
        Route::post('/genres', [GenreController::class, 'store'])->middleware('role:librarian,admin');
        Route::put('/genres/{genre}', [GenreController::class, 'update'])->middleware('role:librarian,admin');
        Route::patch('/genres/{genre}', [GenreController::class, 'update'])->middleware('role:librarian,admin');
        Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->middleware('role:admin');

        Route::get('/book-copies', [BookCopyController::class, 'index']);
        Route::get('/book-copies/{book_copy}', [BookCopyController::class, 'show']);
        Route::post('/book-copies', [BookCopyController::class, 'store'])->middleware('role:librarian,admin');
        Route::put('/book-copies/{book_copy}', [BookCopyController::class, 'update'])->middleware('role:librarian,admin');
        Route::patch('/book-copies/{book_copy}', [BookCopyController::class, 'update'])->middleware('role:librarian,admin');
        Route::delete('/book-copies/{book_copy}', [BookCopyController::class, 'destroy'])->middleware('role:admin');
    });
});
