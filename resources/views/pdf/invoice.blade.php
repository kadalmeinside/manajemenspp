<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kuitansi Pembayaran</title>
    <style>
        * { margin: 0; padding: 0; }
        body { font-family: Arial, sans-serif; font-size: 13px; color: #1f2937; background: #fff; }
        .page { width: 100%; }

        /* ===== KOP SURAT - PUTIH ===== */
        .kop-table { width: 100%; border-collapse: collapse; border-bottom: 3px solid #e8600a; margin-bottom: 0; }
        .kop-table td { vertical-align: middle; padding: 16px 18px; background: #ffffff; }
        .kop-logo-cell { width: 90px; text-align: center; border-right: 1px solid #e5e7eb; }
        .kop-logo-cell img { height: 64px; width: auto; }
        .kop-logo-placeholder { width: 64px; height: 64px; background: #fff3ea; border: 2px solid #e8600a; border-radius: 50%; margin: 0 auto; line-height: 60px; text-align: center; font-size: 26px; font-weight: 800; color: #e8600a; }
        .kop-text-cell { padding-left: 18px; }
        .kop-text-cell h1 { color: #b84400; font-size: 15px; font-weight: 800; letter-spacing: 0.3px; margin-bottom: 4px; }
        .kop-text-cell p { color: #6b7280; font-size: 10px; line-height: 1.8; }
        .kop-right-cell { width: 170px; text-align: right; }
        .kop-right-cell .doc-label { color: #9ca3af; font-size: 9px; text-transform: uppercase; letter-spacing: 2px; font-weight: 700; }
        .kop-right-cell .doc-title { color: #b84400; font-size: 20px; font-weight: 800; margin: 2px 0; }
        .kop-right-cell .doc-number { color: #6b7280; font-size: 10px; }

        /* ===== STATUS BAR ===== */
        .status-bar { background: #ecfdf5; padding: 8px 18px; border-bottom: 1px solid #d1fae5; }
        .status-table { width: 100%; border-collapse: collapse; }
        .status-table td { padding: 0; vertical-align: middle; }
        .badge-lunas { background: #022c22; color: #34d399; padding: 4px 14px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; display: inline-block; }
        .status-date { text-align: right; font-size: 11px; color: #6b7280; }

        /* ===== BODY ===== */
        .body { padding: 22px 18px; }

        /* Info dua kolom */
        .info-top-table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
        .info-top-table td { vertical-align: top; padding: 0; }
        .info-top-table td.left-col { width: 55%; padding-right: 14px; }
        .info-top-table td.right-col { width: 45%; text-align: right; }
        .info-section-title { font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700; margin-bottom: 7px; padding-bottom: 5px; border-bottom: 1px solid #e5e7eb; }
        .info-section-content p { font-size: 12px; color: #374151; line-height: 1.9; }
        .info-section-content p strong { color: #111827; }
        .info-section-content .big-name { font-size: 14px; font-weight: 800; color: #111827; margin-bottom: 4px; }

        /* ===== TABEL INVOICE ===== */
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
        .invoice-table thead tr { background: #e8600a; }
        .invoice-table thead th { color: #ffffff; padding: 10px 14px; font-size: 10px; text-transform: uppercase; letter-spacing: 0.8px; font-weight: 700; }
        .invoice-table thead th.right { text-align: right; }
        .invoice-table thead th.center { text-align: center; }
        .invoice-table tbody tr { border-bottom: 1px solid #f3f4f6; }
        .invoice-table tbody td { padding: 12px 14px; font-size: 12px; color: #374151; vertical-align: middle; }
        .invoice-table tbody td.right { text-align: right; white-space: nowrap; }
        .invoice-table tbody td.center { text-align: center; }
        .invoice-table tbody td.no-col { color: #9ca3af; width: 30px; }
        .invoice-table tbody td.amount-col { width: 160px; }
        .desc-sub { font-size: 10px; color: #9ca3af; margin-top: 2px; }

        .subtotal-row td { background: #f9fafb; color: #6b7280; font-size: 11px; padding: 7px 14px; }
        .fee-row td { background: #f9fafb; color: #6b7280; font-size: 11px; padding: 7px 14px; }
        .total-row td { background: #b84400; color: #ffffff; font-weight: 700; font-size: 14px; padding: 13px 14px; }
        .total-row td.right { text-align: right; white-space: nowrap; }

        /* ===== CATATAN & TTD ===== */
        .bottom-table { width: 100%; border-collapse: collapse; margin-top: 18px; padding-top: 14px; border-top: 1px dashed #e5e7eb; }
        .bottom-table td { vertical-align: top; padding: 0; }
        .bottom-table td.notes-col { width: 60%; padding-right: 18px; }
        .bottom-table td.ttd-col { width: 40%; text-align: center; }
        .notes-title { font-size: 9px; color: #9ca3af; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 6px; }
        .notes-text { font-size: 11px; color: #6b7280; line-height: 1.7; }
        .ttd-box { border: 1px solid #fddcbf; border-radius: 6px; padding: 12px 18px; text-align: center; }
        .ttd-label { font-size: 10px; color: #c07040; text-transform: uppercase; letter-spacing: 0.8px; }
        .ttd-space { height: 44px; }
        .ttd-name { font-size: 12px; font-weight: 700; color: #374151; border-top: 1px solid #fddcbf; padding-top: 6px; margin-top: 6px; }
        .ttd-sub { font-size: 10px; color: #9ca3af; }

        /* ===== FOOTER ===== */
        .footer { background: #fff8f2; border-top: 2px solid #fddcbf; padding: 10px 18px; margin-top: 18px; }
        .footer-table { width: 100%; border-collapse: collapse; }
        .footer-table td { font-size: 10px; color: #9ca3af; line-height: 1.7; vertical-align: middle; }
        .footer-table td.right { text-align: right; }
    </style>
</head>
<body>
<div class="page">

    @php
        $appLogoValue = \App\Models\Setting::where('key', 'app_logo_cek_spp')->value('value');
        if (empty($appLogoValue)) {
            $appLogoValue = \App\Models\Setting::where('key', 'app_logo')->value('value');
        }
        $appLogo = !empty($appLogoValue) ? $appLogoValue : null;

        $kopNamaValue = \App\Models\Setting::where('key', 'kop_surat_nama')->value('value');
        $appNameValue = \App\Models\Setting::where('key', 'app_name')->value('value');
        $kopNama = !empty($kopNamaValue) ? $kopNamaValue : (!empty($appNameValue) ? $appNameValue : config('app.name'));

        $kopAlamatValue = \App\Models\Setting::where('key', 'kop_surat_alamat')->value('value');
        $kopAlamat = !empty($kopAlamatValue) ? $kopAlamatValue : '-';

        $kopKontakValue = \App\Models\Setting::where('key', 'kop_surat_kontak')->value('value');
        $kopKontak = !empty($kopKontakValue) ? $kopKontakValue : '-';

        $invoiceNumber = strtoupper(substr($invoice->id, 0, 8));
        $paidAt      = $invoice->paid_at ? $invoice->paid_at->format('d F Y, H:i') : now()->format('d F Y, H:i');
    @endphp

    <!-- KOP SURAT -->
    <table class="kop-table">
        <tr>
            <td class="kop-logo-cell">
                @if($appLogo)
                    <img src="{{ public_path('storage/' . $appLogo) }}" alt="{{ $kopNama }}">
                @else
                    <div class="kop-logo-placeholder">{{ strtoupper(substr($kopNama, 0, 1)) }}</div>
                @endif
            </td>
            <td class="kop-text-cell">
                <h1>{{ strtoupper($kopNama) }}</h1>
                <p>
                    {{ $kopAlamat }}<br>
                    {{ $kopKontak }}
                </p>
            </td>
            <td class="kop-right-cell">
                <div class="doc-label">Dokumen Resmi</div>
                <div class="doc-title">KUITANSI</div>
                <div class="doc-number">#{{ $invoiceNumber }}</div>
            </td>
        </tr>
    </table>

    <!-- STATUS BAR -->
    <div class="status-bar">
        <table class="status-table">
            <tr>
                <td><span class="badge-lunas">LUNAS</span></td>
                <td class="status-date">Tanggal Bayar: <strong>{{ $paidAt }} WIB</strong></td>
            </tr>
        </table>
    </div>

    <!-- BODY -->
    <div class="body">

        <!-- Info Dua Kolom -->
        <table class="info-top-table">
            <tr>
                <td class="left-col">
                    <div class="info-section-title">Ditagihkan Kepada</div>
                    <div class="info-section-content">
                        <p class="big-name">{{ $invoice->siswa->user->name ?? 'Wali Siswa' }}</p>
                        <p>
                            Siswa: <strong>{{ $invoice->siswa->nama_siswa ?? '-' }}</strong><br>
                            NIS: <strong>{{ $invoice->siswa->nis ?? '-' }}</strong><br>
                            Kelas: <strong>{{ $invoice->siswa->kelas->nama_kelas ?? '-' }}</strong>
                        </p>
                    </div>
                </td>
                <td class="right-col">
                    <div class="info-section-title">Detail Transaksi</div>
                    <div class="info-section-content">
                        <p>
                            No. Invoice: <strong>#{{ $invoiceNumber }}</strong><br>
                            Tanggal Bayar: <strong>{{ $invoice->paid_at ? $invoice->paid_at->format('d M Y') : now()->format('d M Y') }}</strong><br>
                            Metode: <strong>{{ strtoupper($invoice->payment_method ?? 'Transfer Online') }}</strong><br>
                            Status: <strong style="color:#047857">LUNAS</strong>
                        </p>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Tabel Invoice -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th class="center" style="width:32px">No</th>
                    <th>Deskripsi</th>
                    <th class="right" style="width:160px">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @if($invoice->childInvoices && $invoice->childInvoices->count() > 0)
                    @foreach($invoice->childInvoices as $i => $child)
                    <tr>
                        <td class="center">{{ $i + 1 }}</td>
                        <td>
                            {{ $child->description }}
                            @if($child->periode_tagihan)
                                <div class="desc-sub">Periode: {{ $child->periode_tagihan->format('F Y') }}</div>
                            @endif
                        </td>
                        <td class="right">Rp {{ number_format($child->amount, 0, ',', '.') }}</td>
                    </tr>
                    @endforeach
                @else
                    <tr>
                        <td class="center">1</td>
                        <td>
                            {{ $invoice->description }}
                            @if($invoice->periode_tagihan)
                                <div class="desc-sub">Periode: {{ $invoice->periode_tagihan->format('F Y') }}</div>
                            @endif
                        </td>
                        <td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                    </tr>
                @endif

                <!-- Subtotal -->
                <tr class="subtotal-row">
                    <td colspan="2" style="text-align:right">Subtotal</td>
                    <td class="right">Rp {{ number_format($invoice->amount, 0, ',', '.') }}</td>
                </tr>

                <!-- Fee -->
                @if(($invoice->admin_fee ?? 0) > 0)
                <tr class="fee-row">
                    <td colspan="2" style="text-align:right">Biaya Admin / Layanan</td>
                    <td class="right">Rp {{ number_format($invoice->admin_fee, 0, ',', '.') }}</td>
                </tr>
                @endif

                <!-- Total -->
                <tr class="total-row">
                    <td colspan="2" style="text-align:right">TOTAL PEMBAYARAN</td>
                    <td class="right">Rp {{ number_format($invoice->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>

        <!-- Catatan & TTD -->
        <table class="bottom-table">
            <tr>
                <td class="notes-col">
                    <div class="notes-title">Catatan</div>
                    <p class="notes-text">
                        Dokumen ini merupakan bukti pembayaran yang sah dan diterbitkan secara otomatis oleh sistem {{ $kopNama }}. Tidak diperlukan tanda tangan basah.<br><br>
                        Pembayaran yang sudah diterima tidak dapat dikembalikan (non-refundable).
                    </p>
                </td>
                <td class="ttd-col">
                    <div class="ttd-box">
                        <div class="ttd-label">Diterbitkan oleh</div>
                        <div class="ttd-space"></div>
                        <div class="ttd-name">Admin {{ $kopNama }}</div>
                        <div class="ttd-sub">{{ $kopNama }}</div>
                    </div>
                </td>
            </tr>
        </table>

    </div>

    <!-- FOOTER -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>Dokumen diterbitkan secara elektronik oleh sistem {{ $kopNama }}. {{ $kopKontak }}</td>
                <td class="right">Dicetak: {{ now()->format('d M Y, H:i') }} WIB</td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>
