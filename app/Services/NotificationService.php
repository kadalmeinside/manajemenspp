<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemNotification;

class NotificationService
{
    /**
     * Kirim notifikasi ke Super Admin dan Admin dari Kelas yang relevan.
     *
     * Digunakan via Laravel Service Container (dependency injection).
     * Contoh: app(NotificationService::class)->sendToAdmins([...])
     */
    public function sendToAdmins(array $data, $kelasId = null): void
    {
        // 1. Dapatkan semua Admin (Role: admin)
        $superAdmins = User::role('admin')->get();

        // 2. Dapatkan Admin Kelas jika kelasId diberikan
        $adminKelas = collect();
        if ($kelasId) {
            $adminKelas = User::whereHas('managedClasses', function ($q) use ($kelasId) {
                $q->where('kelas_user.kelas_id', $kelasId);
            })->get();
        }

        // Gabungkan tanpa duplikat lalu kirim notifikasi
        $superAdmins->merge($adminKelas)
                    ->unique('id')
                    ->each(fn (User $recipient) => $recipient->notify(new SystemNotification($data)));
    }
}
