<?php

namespace App\Traits;

trait HandlesActiveSiswa
{
    /**
     * Get the currently active Siswa for the logged-in user.
     * Defaults to the first Siswa if no active_siswa_id is set in session.
     */
    protected function getActiveSiswa($user)
    {
        $activeId = session('active_siswa_id');
        
        if ($activeId) {
            $siswa = $user->siswas()
                          ->where('id_siswa', $activeId)
                          ->whereNotIn('status_siswa', ['Keluar', 'Non-Aktif'])
                          ->first();
            if ($siswa) {
                return $siswa;
            }
        }
        
        return $user->siswas()
                    ->whereNotIn('status_siswa', ['Keluar', 'Non-Aktif'])
                    ->first();
    }
}
