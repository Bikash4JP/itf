<?php
declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;
use Illuminate\Http\JsonResponse;

use App\Http\Controllers\{
    ResumeController,
    ExportController,
    ResumeUploadController
};
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;

Route::prefix('rirek')->group(function () {
    // --- Health checks ---
    Route::get('/healthz', fn (): Response => new Response('OK', 200));
    Route::get('/ping', fn (): JsonResponse => response()->json([
        'pong' => true,
        'time' => now()->toDateTimeString(),
    ]));

    // --- Diagnostics (no middleware) ---
    Route::get('/diag/ok', fn () => 'OK-DIAG');
    Route::get('/diag/view-raw', fn () => response('<h1>RAW VIEW STRING</h1>', 200)
        ->header('Content-Type', 'text/html; charset=utf-8'));
    Route::get('/diag/login-plain', fn () => view('_plain_login'));
    Route::get('/diag/register-plain', fn () => view('_plain_register'));
    Route::get('/diag/session', function () {
        session()->put('probe', 'ok');
        return response()->json(['session' => session('probe')], 200);
    });

    // --- Auth (serve GET via views; POST via Breeze controllers) ---
    Route::middleware('guest')->group(function () {
        // GET via simple views (avoid controller-level surprises)
        Route::get('/login',    fn () => view('auth.login'))->name('login');
        Route::get('/register', fn () => view('auth.register'))->name('register');

        // POST via Breeze controllers (actual auth actions)
        Route::post('/login',    [AuthenticatedSessionController::class, 'store']);
        Route::post('/register', [RegisteredUserController::class, 'store']);
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->middleware('auth')->name('logout');

    // --- Home ---
    Route::get('/', function () {
        return Auth::check()
            ? redirect()->route('resumes.index')
            : redirect()->route('login');
    })->name('home');

    // --- Resumes (protected) ---
    Route::middleware('auth')->group(function () {
        Route::get('/resumes', [ResumeController::class, 'index'])->name('resumes.index');
        Route::get('/resumes/create', [ResumeController::class, 'create'])->name('resumes.create');
        Route::post('/resumes', [ResumeController::class, 'store'])->name('resumes.store');
        Route::get('/resumes/{resume}/edit', [ResumeController::class, 'edit'])->name('resumes.edit');
        Route::put('/resumes/{resume}', [ResumeController::class, 'update'])->name('resumes.update');

        Route::post('/resumes/{resume}/upload-photo', [ResumeUploadController::class, 'photo'])->name('resumes.photo');

        Route::get('/resumes/{resume}/preview/xlsx', [ExportController::class, 'previewXlsx'])->name('resumes.preview.xlsx');
        Route::get('/resumes/{resume}/download/xlsx', [ExportController::class, 'downloadXlsx'])->name('resumes.download.xlsx');
        Route::get('/resumes/{resume}/download/pdf',  [ExportController::class, 'downloadPdf'])->name('resumes.download.pdf');
    });

    // --- Fallback inside /rirek ---
    Route::fallback(fn (): Response => new Response('Laravel 404', 404));
});

// Root redirect → /rirek
Route::get('/', fn () => redirect('/rirek'));
