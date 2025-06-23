<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Kelas;

use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\CekTagihanController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\PermissionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\KelasController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ActivityLogController;

use App\Http\Controllers\Siswa\ProfileController as SiswaProfileController;
use App\Http\Controllers\Siswa\TagihanController as SiswaTagihanController;
use App\Http\Controllers\Siswa\DashboardController as SiswaDashboardController;

Route::get('/', function (Request $request) {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip(),
        // 'laravelVersion' => Application::VERSION,
        // 'phpVersion' => PHP_VERSION,
        'allKelas' => Kelas::orderBy('nama_kelas')->get()->map(function($kelas) {
            return [
                'nama_kelas' => $kelas->nama_kelas,
                'deskripsi' => Str::limit($kelas->deskripsi, 50),
                // Tambahkan path gambar jika Anda punya, jika tidak kita gunakan placeholder
                'gambar' => 'https://placehold.co/400x300/e2e8f0/4a5563?text=' . urlencode($kelas->nama_kelas),
            ];
        }),
    ]);
})->name('welcome'); 

Route::get('/cek-tagihan', [CekTagihanController::class, 'showForm'])->name('tagihan.check_form');
Route::post('/cek-tagihan', [CekTagihanController::class, 'checkStatus'])->name('tagihan.check_status');
Route::get('/pembayaran/sukses', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/pembayaran/gagal', [PaymentController::class, 'failure'])->name('payment.failure');
Route::get('/pendaftaran', [RegistrationController::class, 'create'])->name('pendaftaran.create');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])->name('pendaftaran.store');
Route::get('/display', [DisplayController::class, 'index'])->name('display.index');
Route::post('/webhooks/xendit/invoice', [WebhookController::class, 'handleInvoiceCallback'])->name('webhooks.xendit.invoice');

Route::get('/dashboard', function () {
    if (auth()->check() && auth()->user()->hasRole('siswa')) {
        return redirect()->route('siswa.dashboard');
    }
    // Jika admin atau role lain, redirect ke dashboard admin
    if (auth()->check() && auth()->user()->hasAnyRole(['admin', 'user'])) {
        return redirect()->route('admin.dashboard');
    }
    // Fallback jika tidak ada role yang cocok (seharusnya tidak terjadi jika setup benar)
    return Inertia::render('Welcome');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthenticatedSessionController::class, 'create'])
                ->middleware('guest') // Hanya bisa diakses oleh guest (belum login)
                ->name('login');

    Route::post('login', [AdminAuthenticatedSessionController::class, 'store'])
                ->middleware('guest');

    Route::middleware(['auth', 'verified'])->group(function () {
        
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('roles', RoleController::class);
            Route::resource('permissions', PermissionController::class);
            Route::resource('users', UserController::class);
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
        });

        Route::middleware(['role:admin|user'])->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('kelas', KelasController::class);
            Route::get('siswa/export', [SiswaController::class, 'export'])->name('siswa.export');
            Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
            Route::get('siswa/generate-nis/{kelas}', [SiswaController::class, 'generateNis'])->name('siswa.generate_nis');
            Route::resource('siswa', SiswaController::class);
            Route::resource('invoices', InvoiceController::class);
            Route::post('invoices/bulk-store', [InvoiceController::class, 'bulkStore'])->name('invoices.bulk_store');
            Route::post('invoices/bulk-store-all', [InvoiceController::class, 'bulkStoreAll'])->name('invoices.bulk_store_all');
            Route::get('laporan/pembayaran-bulanan', [LaporanController::class, 'pembayaranBulanan'])->name('laporan.pembayaran_bulanan');
            Route::get('jobs', [\App\Http\Controllers\Admin\JobBatchController::class, 'index'])->name('jobs.index');
        });

    });
});

Route::middleware(['auth', 'verified', 'role:siswa'])->prefix('siswa')
    ->name('siswa.')->group(function () {
        Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
        Route::put('/profil/update-info', [SiswaProfileController::class, 'updateInformation'])->name('profil.update_info');
        Route::put('/profil/update-password', [SiswaProfileController::class, 'updatePassword'])->name('profil.update_password');
        Route::get('/profil', [SiswaProfileController::class, 'show'])->name('profil.show');
        Route::get('/tagihan', [SiswaTagihanController::class, 'index'])->name('tagihan.index');
        Route::post('/invoices/{invoice}/pay', [SiswaTagihanController::class, 'createPaymentToken'])->name('tagihan.pay');
});

require __DIR__.'/auth.php';
