<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Controllers\WelcomeController;

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
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\PushSubscriptionController;
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
use App\Http\Controllers\Auth\GoogleAuthController;


Route::get('/', [WelcomeController::class, 'index'])->name('welcome');

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
Route::post('/daftar-ulang', [ReRegistrationController::class, 'store'])->middleware('throttle:10,1')->name('re-register.store');
use App\Http\Controllers\Public\ResignationController;

Route::post('/webhooks/xendit/invoice', [WebhookController::class, 'handleInvoiceCallback'])->name('webhooks.xendit.invoice');
Route::post('/webhooks/xendit/disbursement', [\App\Http\Controllers\Api\XenditWebhookController::class, 'handleDisbursement'])->name('webhooks.xendit.disbursement');

// Resignation Routes (Signed)
Route::get('/pengunduran-diri/{siswa}', [ResignationController::class, 'showForm'])->name('public.resignation.form')->middleware('signed');
Route::post('/pengunduran-diri/{siswa}', [ResignationController::class, 'submitForm'])->name('public.resignation.submit')->middleware('signed');

Route::get('/daftar-academy', [RegistrationController::class, 'createAcademy'])->name('register-academy.create');
Route::get('/daftar-ss', [RegistrationController::class, 'createSs'])->name('register-ss.create');
Route::post('/pendaftaran', [RegistrationController::class, 'store'])->middleware('throttle:10,1')->name('pendaftaran.store');
Route::post('/pendaftaran/check-email', [RegistrationController::class, 'checkEmail'])->middleware('throttle:30,1')->name('pendaftaran.check-email');
Route::get('/pendaftaran/sukses/{siswa}', [RegistrationSuccessController::class, 'show'])->name('registration.success');
Route::get('/pendaftaran/pending/{pending}', [RegistrationSuccessController::class, 'showPending'])->name('registration.success.pending');
Route::post('/promo/validate', [RegistrationController::class, 'validatePromoCode'])->middleware('throttle:30,1')->name('promo.validate');

// Google OAuth Routes
Route::middleware('guest')->group(function () {
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

Route::get('/dashboard', function () {
    if (auth()->check() && auth()->user()->hasRole('siswa')) {
        return redirect()->route('siswa.dashboard');
    }
    // Jika admin atau role lain, redirect ke dashboard admin
    if (auth()->check() && auth()->user()->hasAnyRole(['admin', 'user', 'admin_kelas', 'staff_akademik'])) {
        return redirect()->route('admin.dashboard');
    }
    // Fallback jika tidak ada role yang cocok (seharusnya tidak terjadi jika setup benar)
    return Inertia::render('Welcome');
})->middleware(['auth', 'verified'])->name('dashboard');


Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::post('/siswa/agreements', [\App\Http\Controllers\UserAgreementController::class, 'store'])->name('siswa.agreements.store');
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
            Route::get('analytics', [\App\Http\Controllers\Admin\AnalyticsController::class, 'index'])->name('analytics.index');
            Route::resource('kelas', KelasController::class);
            Route::get('siswa/export', [SiswaController::class, 'export'])->name('siswa.export');
            Route::post('siswa/import', [SiswaController::class, 'import'])->name('siswa.import');
            Route::get('siswa/generate-nis/{kelas}', [SiswaController::class, 'generateNis'])->name('siswa.generate_nis');
            Route::get('siswa/pendaftar-lunas', [SiswaController::class, 'pendaftarLunas'])->name('siswa.pendaftar_lunas');
            Route::post('siswa/{siswa}/mulai-spp', [SiswaController::class, 'setMulaiSpp'])->name('siswa.set_mulai_spp');
            Route::get('siswa/{siswa}/legal-pdf/{agreement}', [SiswaController::class, 'downloadLegalPdf'])->name('siswa.legal_pdf');
            Route::post('siswa/{siswa}/generate-resignation-url', [SiswaController::class, 'generateResignationUrl'])->name('siswa.generate_resignation_url');
            
            // Mutasi Siswa
            Route::post('siswa/{siswa}/mutasi', [\App\Http\Controllers\Admin\MutasiSiswaController::class, 'store'])->name('mutasi.store');
            Route::post('mutasi/{mutasi}/regenerate', [\App\Http\Controllers\Admin\MutasiSiswaController::class, 'regenerate'])->name('mutasi.regenerate');
            Route::post('mutasi/{mutasi}/cancel', [\App\Http\Controllers\Admin\MutasiSiswaController::class, 'cancel'])->name('mutasi.cancel');
            
            // Remove the delete route manually or let except(['destroy']) handle it
            Route::resource('siswa', SiswaController::class)->except(['destroy']);
            Route::get('invoices/export-paid', [InvoiceController::class, 'exportPaid'])->name('invoices.export_paid');
            Route::resource('invoices', InvoiceController::class);
            Route::patch('invoices/{invoice}/mark-as-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark_as_paid');
            Route::post('invoices/{invoice}/recreate', [InvoiceController::class, 'recreate'])->name('invoices.recreate');
            Route::post('invoices/bulk-store', [InvoiceController::class, 'bulkStore'])->name('invoices.bulk_store');
            Route::post('invoices/bulk-store-all', [InvoiceController::class, 'bulkStoreAll'])->name('invoices.bulk_store_all');
            Route::resource('promos', PromoController::class)->except(['show']);
            Route::resource('products', \App\Http\Controllers\Admin\ProductController::class);
            Route::delete('products/{product}/images/{image}', [\App\Http\Controllers\Admin\ProductController::class, 'deleteImage'])->name('products.images.destroy');
            Route::get('products/{product}/stock', [\App\Http\Controllers\Admin\StockController::class, 'index'])->name('products.stock.index');
            Route::post('products/{product}/stock', [\App\Http\Controllers\Admin\StockController::class, 'store'])->name('products.stock.store');
            Route::get('pos', [\App\Http\Controllers\Admin\PosController::class, 'index'])->name('pos.index');
            Route::post('pos', [\App\Http\Controllers\Admin\PosController::class, 'store'])->name('pos.store');
            Route::get('orders', [\App\Http\Controllers\Admin\StoreOrderController::class, 'index'])->name('orders.index');
            Route::patch('orders/{order}/complete', [\App\Http\Controllers\Admin\StoreOrderController::class, 'complete'])->name('orders.complete');
            Route::get('laporan/pembayaran-bulanan', [LaporanController::class, 'pembayaranBulanan'])->name('laporan.pembayaran_bulanan');
            Route::get('laporan/aktivitas', [LaporanController::class, 'aktivitas'])->name('laporan.aktivitas');
            Route::get('laporan/aktivitas/export', [LaporanController::class, 'exportAktivitas'])->name('laporan.aktivitas.export');
            Route::get('laporan/export', [LaporanController::class, 'export'])->name('laporan.export');
            
            // Laporan Keuangan & Kas
            Route::get('finance', [\App\Http\Controllers\Admin\FinanceController::class, 'index'])->name('finance.index')->middleware('permission:view_finance|view_laporan');

            // Notifications
            Route::get('notifications/unread', [NotificationController::class, 'getUnread'])->name('notifications.unread');
            Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
            Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read_all');
            
            // Web Push Subscriptions
            Route::post('push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
            Route::delete('push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');

            Route::get('jobs', [\App\Http\Controllers\Admin\JobBatchController::class, 'index'])->name('jobs.index');

            // Student Leaves Management
            Route::get('leaves', [\App\Http\Controllers\StudentLeaveController::class, 'index'])->name('leaves.index');
            Route::patch('/leaves/{studentLeave}/approve', [\App\Http\Controllers\StudentLeaveController::class, 'approve'])->name('leaves.approve');
            Route::patch('/leaves/{studentLeave}/reject', [\App\Http\Controllers\StudentLeaveController::class, 'reject'])->name('leaves.reject');
            Route::patch('/leaves/{studentLeave}/cancel', [\App\Http\Controllers\StudentLeaveController::class, 'cancel'])->name('leaves.cancel');
            Route::post('/leaves/store-admin', [\App\Http\Controllers\StudentLeaveController::class, 'storeAdmin'])->name('leaves.storeAdmin');
        });

    });
});

Route::middleware(['auth', 'verified', 'role:siswa'])->prefix('siswa')
    ->name('siswa.')->group(function () {
        Route::post('/switch-siswa/{id_siswa}', \App\Http\Controllers\Siswa\SwitchSiswaController::class)->name('switch-siswa');
        Route::get('/dashboard', [SiswaDashboardController::class, 'index'])->name('dashboard');
        Route::put('/profil/update-info', [SiswaProfileController::class, 'updateInformation'])->name('profil.update_info');
        Route::put('/profil/update-password', [SiswaProfileController::class, 'updatePassword'])->name('profil.update_password');
        Route::get('/profil', [SiswaProfileController::class, 'show'])->name('profil.show');
        Route::get('/tagihan', [SiswaTagihanController::class, 'index'])->name('tagihan.index');
        Route::post('/invoices/{invoice}/pay', [SiswaTagihanController::class, 'createPaymentToken'])->name('tagihan.pay');
        Route::post('/invoices/bulk-pay', [SiswaTagihanController::class, 'createBulkPayment'])->name('invoices.bulk_pay');
        Route::post('/tagihan/pay', [SiswaTagihanController::class, 'createUnifiedPayment'])->name('invoices.unified_pay');
        
        // Store / E-commerce
        Route::get('/toko', [\App\Http\Controllers\Siswa\StoreController::class, 'index'])->name('store.index');
        Route::get('/toko/keranjang', [\App\Http\Controllers\Siswa\StoreController::class, 'cart'])->name('store.cart');
        Route::post('/toko/keranjang', [\App\Http\Controllers\Siswa\StoreController::class, 'addToCart'])->name('store.cart.add');
        Route::put('/toko/keranjang/{cartItem}', [\App\Http\Controllers\Siswa\StoreController::class, 'updateCartItem'])->name('store.cart.update');
        Route::delete('/toko/keranjang/{cartItem}', [\App\Http\Controllers\Siswa\StoreController::class, 'removeCartItem'])->name('store.cart.remove');
        // Checkout & Orders
        Route::post('/toko/checkout', [\App\Http\Controllers\Siswa\StoreCheckoutController::class, 'checkout'])->name('store.checkout');
        Route::get('/toko/pesanan', [\App\Http\Controllers\Siswa\StoreOrderController::class, 'index'])->name('store.orders.index');
        Route::get('/toko/pesanan/{order}', [\App\Http\Controllers\Siswa\StoreOrderController::class, 'show'])->name('store.orders.show');
        
        Route::get('/toko/{product:slug}', [\App\Http\Controllers\Siswa\StoreController::class, 'show'])->name('store.show');
});



// Route untuk Cuti Siswa (Public/Student - verified via session/auth in controller)
Route::post('/student-leaves', [\App\Http\Controllers\StudentLeaveController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('student-leaves.store');

Route::controller(CekSppController::class)->group(function () {
    Route::get('/cek-spp', 'showForm')->name('tagihan.spp.form');
    Route::post('/cek-spp', 'findSiswaByPhone')->middleware('throttle:10,1')->name('tagihan.spp.find');
    Route::post('/cek-spp/select', 'selectSiswa')->name('tagihan.spp.select');
    Route::get('/cek-spp/{siswa}', 'showTagihan')->name('tagihan.spp.show');
    Route::post('/cek-spp/{siswa}/create-user', 'createUserAndLink')->middleware('throttle:10,1')->name('tagihan.spp.create_user');
    Route::post('/cek-spp/{siswa}/pay', 'createSppPayment')->middleware('throttle:20,1')->name('tagihan.spp.pay');
    Route::get('/cek-spp/sukses/{siswa}', 'showSuccess')->name('tagihan.spp.success');
});

Route::post('/cek-spp/agreements', [\App\Http\Controllers\UserAgreementController::class, 'storePublic'])->name('tagihan.spp.agreements.store');

// Route Publik Mutasi Siswa
Route::get('/mutasi/{token}', [\App\Http\Controllers\Public\MutasiController::class, 'show'])->name('mutasi.show');
Route::post('/mutasi/{token}/approve', [\App\Http\Controllers\Public\MutasiController::class, 'approve'])->name('mutasi.approve');

require __DIR__.'/auth.php';

