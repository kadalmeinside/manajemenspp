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
use App\Http\Controllers\Public\CheckTagihanController;
use App\Http\Controllers\Public\CekSppController;
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
use App\Http\Controllers\Admin\Auth\PasswordResetLinkController;
use App\Http\Controllers\Admin\Auth\NewPasswordController;
use App\Http\Controllers\Admin\LegalDocumentController;
use App\Http\Controllers\Admin\PromoController;
use App\Http\Controllers\ReRegistrationController;
use App\Http\Controllers\RegistrationSuccessController;
use App\Http\Controllers\LegalController;

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
Route::get('/soccer-school', function (Request $request) {
    return Inertia::render('Soccerschool', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip()
    ]);
})->name('soccer-school');
Route::get('/academy', function (Request $request) {
    return Inertia::render('Academy', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip()
    ]);
})->name('academy');
Route::get('/persija-dna', function (Request $request) {
    return Inertia::render('Persijadna', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip()
    ]);
})->name('persija-dna');
Route::get('/kontak', function (Request $request) {
    return Inertia::render('Kontak', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'userIp' => $request->ip()
    ]);
})->name('kontak'); 
// Route::get('/cek-tagihan', [CekTagihanController::class, 'showForm'])->name('tagihan.check_form');
// Route::post('/cek-tagihan', [CekTagihanController::class, 'checkStatus'])->name('tagihan.check_status');
Route::get('/pembayaran/sukses', [PaymentController::class, 'success'])->name('payment.success');
Route::get('/pembayaran/gagal', [PaymentController::class, 'failure'])->name('payment.failure');
Route::get('/pendaftaran', [RegistrationController::class, 'create'])->name('pendaftaran.create');
Route::get('/display', [DisplayController::class, 'index'])->name('display.index');
Route::get('/syarat-ketentuan', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/kebijakan-privasi', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/ketentuan-pengembalian', [LegalController::class, 'refund'])->name('legal.refund');
Route::get('/daftar-ulang', [ReRegistrationController::class, 'create'])->name('re-register.create');
Route::get('/daftar-ulang-academy', [ReRegistrationController::class, 'createAcademy'])->name('re-register-academy.create');
// Route::get('/daftar-ss', [ReRegistrationController::class, 'createSs'])->name('re-register-ss.create');
Route::post('/daftar-ulang', [ReRegistrationController::class, 'store'])->name('re-register.store');
Route::post('/webhooks/xendit/invoice', [WebhookController::class, 'handleInvoiceCallback'])->name('webhooks.xendit.invoice');


Route::get('/daftar-academy', [RegistrationController::class, 'createAcademy'])->name('register-academy.create');
Route::get('/daftar-ss', [RegistrationController::class, 'createSs'])->name('register-ss.create');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])->name('pendaftaran.store');
Route::get('/pendaftaran/sukses/{siswa}', [RegistrationSuccessController::class, 'show'])->name('registration.success');
Route::post('/promo/validate', [RegistrationController::class, 'validatePromoCode'])->name('promo.validate');

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
    
    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
                ->middleware('guest')
                ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
                ->middleware('guest')
                ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
                ->middleware('guest')
                ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
                ->middleware('guest')
                ->name('password.store');

    Route::middleware(['auth', 'verified'])->group(function () {
        
        Route::middleware(['role:admin'])->group(function () {
            Route::resource('roles', RoleController::class);
            Route::resource('permissions', PermissionController::class);
            Route::resource('users', UserController::class);
            Route::resource('legal-documents', LegalDocumentController::class);
            Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');
            Route::get('activity-log', [ActivityLogController::class, 'index'])->name('activity.index');
        });

        Route::middleware(['role:admin|user|admin_kelas'])->group(function() {
            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::resource('kelas', KelasController::class);
            Route::get('siswa/export', [SiswaController::class, 'export'])->name('siswa.export');
            Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
            Route::get('siswa/generate-nis/{kelas}', [SiswaController::class, 'generateNis'])->name('siswa.generate_nis');
            Route::resource('siswa', SiswaController::class);
            Route::resource('invoices', InvoiceController::class);
            Route::resource('promos', PromoController::class)->except(['show']);
            Route::post('invoices/{invoice}/recreate', [InvoiceController::class, 'recreate'])->name('invoices.recreate');
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
        Route::post('/invoices/bulk-pay', [SiswaTagihanController::class, 'createBulkPayment'])->name('invoices.bulk_pay');
        Route::post('/tagihan/pay', [SiswaTagihanController::class, 'createUnifiedPayment'])->name('invoices.unified_pay');
});

Route::controller(CheckTagihanController::class)->group(function () {
    Route::get('/cek-tagihan', 'showCheckForm')->name('tagihan.check_form');
    Route::post('/cek-tagihan', 'findSiswa')->name('tagihan.check_status');
    Route::get('/cek-tagihan/hasil', 'showResult')->name('tagihan.check_result');
    Route::post('/cek-tagihan/create-user', 'createUserAndLink')->name('tagihan.create_user');
    Route::post('/cek-tagihan/pay', 'createPublicPayment')->name('tagihan.check_pay'); // Route pembayaran baru
});

Route::controller(CekSppController::class)->group(function () {
    Route::get('/cek-spp', 'showForm')->name('tagihan.spp.form');
    Route::post('/cek-spp', 'findSiswaByPhone')->name('tagihan.spp.find');
    Route::get('/cek-spp/{siswa}', 'showTagihan')->name('tagihan.spp.show');
    Route::post('/cek-spp/{siswa}/pay', 'createSppPayment')->name('tagihan.spp.pay');
    Route::get('/cek-spp/sukses/{siswa}', 'showSuccess')->name('tagihan.spp.success');
});

require __DIR__.'/auth.php';
