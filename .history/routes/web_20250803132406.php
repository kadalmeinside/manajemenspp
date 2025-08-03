<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Kelas;
use Illuminate\Support\Str;

// --- Import semua controller di satu tempat ---
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\JobBatchController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\LegalDocumentController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Public\CheckTagihanController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\RegistrationSuccessController;
use App\Http\Controllers\ReRegistrationController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;
use App\Http\Controllers\Siswa\ProfileController as SiswaProfileController;
use App\Http\Controllers\Siswa\TagihanController as SiswaTagihanController;
use App\Http\Controllers\WebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// --- Rute Webhook (Stateless) ---
// Webhook dari Xendit tidak memerlukan session atau CSRF, jadi diletakkan di luar grup 'web'.
Route::post('/webhooks/xendit/invoice', [WebhookController::class, 'handleInvoiceCallback'])->name('webhooks.xendit.invoice');


// --- RUTE YANG MEMERLUKAN SESSION (MIDDLEWARE 'WEB') ---
Route::middleware('web')->group(function () {

    // --- Rute Publik Statis & Halaman Informasi ---
    Route::get('/', function (Request $request) {
        return Inertia::render('Welcome', [
            'canLogin' => Route::has('login'),
            'canRegister' => Route::has('register'),
            'allKelas' => Kelas::orderBy('nama_kelas')->get()->map(function($kelas) {
                return [
                    'nama_kelas' => $kelas->nama_kelas,
                    'deskripsi' => Str::limit($kelas->deskripsi, 50),
                    'gambar' => 'https://placehold.co/400x300/e2e8f0/4a5563?text=' . urlencode($kelas->nama_kelas),
                ];
            }),
        ]);
    })->name('welcome');

    Route::get('/soccer-school', fn() => Inertia::render('Soccerschool'))->name('soccer-school');
    Route::get('/academy', fn() => Inertia::render('Academy'))->name('academy');
    Route::get('/persija-dna', fn() => Inertia::render('Persijadna'))->name('persija-dna');
    Route::get('/kontak', fn() => Inertia::render('Kontak'))->name('kontak');
    Route::get('/display', [DisplayController::class, 'index'])->name('display.index');

    // --- Rute Pendaftaran & Pembayaran Publik ---
    Route::controller(RegistrationController::class)->group(function () {
        Route::get('/daftar-academy', 'createAcademy')->name('register-academy.create');
        Route::get('/daftar-ss', 'createSs')->name('register-ss.create');
        Route::post('/pendaftaran', 'store')->name('pendaftaran.store');
        Route::post('/promo/validate', 'validatePromoCode')->name('promo.validate');
    });
    
    // Rute daftar ulang
    Route::controller(ReRegistrationController::class)->group(function () {
        Route::get('/daftar-ulang', 'create')->name('re-register.create');
        Route::get('/daftar-ulang-academy', 'createAcademy')->name('re-register-academy.create');
        Route::post('/daftar-ulang', 'store')->name('re-register.store');
    });

    Route::get('/pendaftaran/sukses', [RegistrationSuccessController::class, 'show'])->name('registration.success');
    Route::get('/pembayaran/sukses', [PaymentController::class, 'success'])->name('payment.success');
    Route::get('/pembayaran/gagal', [PaymentController::class, 'failure'])->name('payment.failure');

    // --- Rute Cek Tagihan Publik ---
    Route::controller(CheckTagihanController::class)->group(function () {
        Route::get('/cek-tagihan', 'showCheckForm')->name('tagihan.check_form');
        Route::post('/cek-tagihan', 'findSiswa')->name('tagihan.check_status');
        Route::get('/cek-tagihan/hasil', 'showResult')->name('tagihan.check_result');
        Route::post('/cek-tagihan/create-user', 'createUserAndLink')->name('tagihan.create_user');
        Route::post('/cek-tagihan/pay', 'createPublicPayment')->name('tagihan.check_pay');
    });

    // --- Rute Halaman Legal ---
    Route::controller(LegalController::class)->group(function () {
        Route::get('/syarat-ketentuan', 'terms')->name('legal.terms');
        Route::get('/kebijakan-privasi', 'privacy')->name('legal.privacy');
        Route::get('/ketentuan-pengembalian', 'refund')->name('legal.refund');
    });

    // --- Rute Otentikasi Bawaan Laravel ---
    Route::get('/dashboard', function () {
        if (auth()->check() && auth()->user()->hasRole('siswa')) {
            return redirect()->route('siswa.dashboard');
        }
        if (auth()->check() && auth()->user()->hasAnyRole(['admin', 'user', 'super_admin', 'admin_kelas'])) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('welcome');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    require __DIR__.'/auth.php';

    // --- Rute Area Admin ---
    Route::prefix('admin')->name('admin.')->group(function () {
        // Rute login & reset password admin (hanya untuk guest)
        Route::middleware('guest')->group(function () {
            Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
            Route::post('login', [AdminAuthenticatedSessionController::class, 'store']);
            Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
            Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
            Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
            Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
        });

        // Rute yang memerlukan login admin
        Route::middleware(['auth', 'verified'])->group(function () {
            // Rute khusus untuk Super Admin & Admin
            Route::middleware(['role:super_admin|admin'])->group(function () {
                Route::resource('roles', RoleController::class);
                Route::resource('permissions', PermissionController::class);
                Route::resource('users', UserController::class);
                Route::resource('legal-documents', LegalDocumentController::class);
                Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
                Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
                Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
            });

            // Rute untuk semua jenis admin (termasuk admin kelas)
            Route::middleware(['role:super_admin|admin|admin_kelas'])->group(function() {
                Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
                Route::resource('kelas', KelasController::class);
                Route::resource('siswa', SiswaController::class);
                Route::resource('invoices', InvoiceController::class);
                Route::resource('promos', PromoController::class)->except(['show']);
                Route::post('invoices/{invoice}/recreate', [InvoiceController::class, 'recreate'])->name('invoices.recreate');
                Route::post('invoices/bulk-store', [InvoiceController::class, 'bulkStore'])->name('invoices.bulk_store');
                Route::post('invoices/bulk-store-all', [InvoiceController::class, 'bulkStoreAll'])->name('invoices.bulk_store_all');
                Route::get('laporan/pembayaran-bulanan', [LaporanController::class, 'pembayaranBulanan'])->name('laporan.pembayaran_bulanan');
                Route::get('jobs', [JobBatchController::class, 'index'])->name('jobs.index');
            });
        });
    });

    // --- Rute Area Siswa ---
    Route::middleware(['auth', 'verified', 'role:siswa'])->prefix('siswa')->name('siswa.')->group(function () {
        Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
        Route::get('/profil', [SiswaProfileController::class, 'show'])->name('profil.show');
        Route::get('/tagihan', [SiswaTagihanController::class, 'index'])->name('tagihan.index');
        Route::post('/tagihan/pay', [SiswaTagihanController::class, 'createUnifiedPayment'])->name('invoices.unified_pay');
    });

});
