<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemNotification;

class NotificationService
{
    /**
     * Kirim notifikasi ke Super Admin dan Admin dari Kelas yang relevan
     */
    public static function sendToAdmins(array $data, $kelasId = null)
    {
        // 1. Dapatkan Admin (Role: admin)
        $superAdmins = User::role('admin')->get();

        // 2. Dapatkan Admin Kelas jika kelasId diberikan
        $adminKelas = collect();
        if ($kelasId) {
            $adminKelas = User::whereHas('managedClasses', function ($q) use ($kelasId) {
                $q->where('kelas_user.id_kelas', $kelasId);
            })->get();
        }

        // Gabungkan tanpa duplikat
        $recipients = $superAdmins->merge($adminKelas)->unique('id');

        // Kirim notifikasi
        foreach ($recipients as $recipient) {
            $recipient->notify(new SystemNotification($data));
        }
    }
}
