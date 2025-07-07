<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class LegalDocumentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        //$this->authorize('manage application settings');
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        return Inertia::render('Admin/Legal/Index', [
            'documents' => LegalDocument::orderBy('name')->orderBy('version', 'desc')
                ->paginate(10)
                ->through(fn ($doc) => [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'type' => $doc->type,
                    'version' => $doc->version,
                    'published_at' => $doc->published_at,
                ]),
            'pageTitle' => 'Manajemen Dokumen Legal',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): Response
    {
        //$this->authorize('manage application settings');
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        return Inertia::render('Admin/Legal/Create', [
            'pageTitle' => 'Buat Dokumen Baru',
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        //$this->authorize('manage application settings');
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:legal_documents,name',
            'type' => 'required|string|in:terms_and_conditions,privacy_policy,refund_policy',
            'version' => 'required|string|max:50',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
        ]);

        LegalDocument::create($validated);
        return Redirect::route('admin.legal-documents.index')->with('message', 'Dokumen baru berhasil dibuat.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, LegalDocument $legalDocument)
    {
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        return response()->json($legalDocument);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, LegalDocument $legalDocument): Response
    {
        //$this->authorize('manage application settings');
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        return Inertia::render('Admin/Legal/Create', [
            'document' => $legalDocument,
            'pageTitle' => 'Edit Dokumen',
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LegalDocument $legalDocument): RedirectResponse
    {
        //$this->authorize('manage application settings');
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:legal_documents,name,' . $legalDocument->id,
            'type' => 'required|string|in:terms_and_conditions,privacy_policy,refund_policy',
            'version' => 'required|string|max:50',
            'content' => 'required|string',
            'published_at' => 'nullable|date',
        ]);

        $legalDocument->update($validated);
        return Redirect::route('admin.legal-documents.index')->with('message', 'Dokumen berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, LegalDocument $legalDocument): RedirectResponse
    {
        //$this->authorize('manage application settings');
        if (!$request->user()->can('manage application settings')) {
            abort(403);
        }

        $legalDocument->delete();
        return Redirect::route('admin.legal-documents.index')->with('message', 'Dokumen berhasil dihapus.');
    }
}
