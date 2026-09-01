<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SettingsController extends Controller
{
    use AuthorizesRequests;

    public function index(Request $request)
    {
        $this->authorize('manage application settings'); 

        // Ambil semua pengaturan dan ubah menjadi format key => value
        $settings = Setting::all()->pluck('value', 'key');
        
        // Ambil list dokumen legal untuk dropdown
        $legalDocuments = LegalDocument::orderBy('name')->orderBy('version', 'desc')->get()->map(function($doc) {
            return [
                'id' => $doc->id,
                'name' => $doc->name . ' (v' . $doc->version . ') - ' . $doc->type,
                'type' => $doc->type,
            ];
        });

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
            'legalDocuments' => $legalDocuments,
            'pageTitle' => 'Pengaturan Aplikasi',
            'can' => [
                'update_settings' => auth()->user()->can('manage application settings'),
            ]
        ]);
    }

    public function update(Request $request)
    {
        $this->authorize('manage application settings');
        // if (!$request->user()->can('manage application settings')) {
        //     abort(403);
        // }

        $validated = $request->validate([
            'app_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:255',
            'app_build' => 'nullable|string|max:255',
            'app_logo' => 'nullable|image|max:1024', // Max 1MB
            'app_logo_cek_spp' => 'nullable|image|max:1024', // Max 1MB
            'kop_surat_nama' => 'nullable|string|max:255',
            'kop_surat_alamat' => 'nullable|string',
            'kop_surat_kontak' => 'nullable|string',
            'enable_parent_login' => 'nullable|in:0,1',
        ]);

        // Simpan atau update pengaturan teks
        $textSettings = ['app_name', 'app_version', 'app_build', 'kop_surat_nama', 'kop_surat_alamat', 'kop_surat_kontak', 'enable_parent_login'];
        foreach ($textSettings as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $validated[$key] ?? '']
                );
            }
        }

        // Simpan atau update logo aplikasi
        if ($request->hasFile('app_logo')) {
            // Hapus logo lama jika ada
            $oldLogoPath = Setting::where('key', 'app_logo')->value('value');
            if ($oldLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            // Simpan logo baru
            $path = $request->file('app_logo')->store('logos', 'public');
            Setting::updateOrCreate(
                ['key' => 'app_logo'],
                ['value' => $path]
            );
        }

        // Simpan atau update logo Cek SPP
        if ($request->hasFile('app_logo_cek_spp')) {
            // Hapus logo lama jika ada
            $oldLogoCekSppPath = Setting::where('key', 'app_logo_cek_spp')->value('value');
            if ($oldLogoCekSppPath) {
                Storage::disk('public')->delete($oldLogoCekSppPath);
            }

            // Simpan logo baru
            $pathCekSpp = $request->file('app_logo_cek_spp')->store('logos', 'public');
            Setting::updateOrCreate(
                ['key' => 'app_logo_cek_spp'],
                ['value' => $pathCekSpp]
            );
        }

        // Simpan konfigurasi Legal Documents ke Settings
        $legalSettings = [
            'legal_doc_registration_public',
            'legal_doc_registration_academy',
            'legal_doc_registration_ss',
            'legal_doc_re_registration',
            'legal_doc_resignation',
            'legal_doc_mutasi'
        ];

        foreach ($legalSettings as $key) {
            if ($request->has($key)) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $request->input($key)]
                );
            }
        }

        // Hapus cache pengaturan agar yang baru digunakan
        Cache::forget('app_settings');

        return Redirect::route('admin.settings.index')->with([
            'message' => 'Pengaturan berhasil diperbarui.',
            'type' => 'success'
        ]);
    }
}
