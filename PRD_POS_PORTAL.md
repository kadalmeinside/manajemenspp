# Product Requirements Document (PRD)
**Project**: Revamp Portal Orang Tua & Sistem Mini POS (Merchandise)
**Status**: Draf / Perencanaan Masa Depan

---

## 1. Revamp Portal Orang Tua (Dukungan Multi-Siswa)

### 1.1 Latar Belakang & Tujuan
Saat ini, banyak wali murid yang memiliki lebih dari satu anak yang terdaftar di akademi. Sistem harus mempermudah mereka untuk mengelola profil, tagihan, dan riwayat pembayaran seluruh anak mereka menggunakan satu akun *login* saja.

### 1.2 User Flow & Pengalaman Pengguna
- **Login**: Orang tua login menggunakan nomor WhatsApp atau email yang terdaftar.
- **Dashboard Keluarga (Family Dashboard)**:
  - Setelah login, layar utama akan menampilkan ringkasan **semua anak** yang terdaftar di bawah akun tersebut.
  - Terdapat *card* peringatan untuk **Total Tagihan Gabungan** (misal: "Anda memiliki 2 tagihan SPP yang belum dibayar").
- **Detail Siswa**:
  - Orang tua dapat mengklik *card* salah satu anak untuk melihat:
    - Biodata lengkap siswa.
    - Riwayat kelas dan status.
    - Daftar tagihan spesifik untuk anak tersebut (Riwayat lunas & belum lunas).

### 1.3 Kebutuhan Database (Best Practice)
- Relasi `users` dan `siswa` sudah menggunakan `siswa.id_user`. Pastikan query di backend selalu mengambil `$user->siswa` sebagai *HasMany* (Banyak siswa untuk 1 user).
- Memastikan logika Xendit dan *invoice* bisa membedakan tagihan SPP Anak A dan Anak B secara akurat jika dibayar melalui satu pintu (opsi keranjang/checkout multi-tagihan).

---

## 2. Sistem Mini POS (Point of Sales) & E-Commerce

### 2.1 Latar Belakang & Tujuan
Akademi akan menjual produk fisik seperti seragam, perlengkapan latihan (bola, deker), dan *merchandise* (jersey). Orang tua harus bisa membeli barang ini langsung melalui portal mereka.

### 2.2 Fitur Frontend (Portal Orang Tua/Siswa)
- **Menu "Koperasi" atau "Toko"**:
  - Halaman katalog produk dengan desain *grid* modern (menampilkan gambar produk, harga, ketersediaan stok).
  - Saat mengklik produk (misal: Jersey Latihan), muncul *modal* untuk memilih **Varian (Size: S, M, L, XL)**.
- **Proses Checkout**:
  - Memilih produk akan diarahkan ke konfirmasi pembayaran.
  - Terintegrasi langsung dengan Xendit (bayar via QRIS, Virtual Account, dll).
  - Terdapat pilihan "Ditujukan untuk Siswa: [Dropdown Anak A / Anak B]" agar admin tahu pesanan ini untuk siapa.

### 2.3 Fitur Admin (Manajemen Penjualan)
- **Katalog Produk (CRUD)**:
  - Menambah/mengedit produk, mengunggah foto produk, mengatur harga dasar.
  - **Sistem Varian**: Mengelola varian ukuran dan stok masing-masing varian secara mandiri.
- **Manajemen Pesanan (Order Management)**:
  - Tabel riwayat pesanan khusus produk (terpisah dari SPP).
  - *Status Flow*: `Menunggu Pembayaran` ➔ `Lunas (Siap Diambil)` ➔ `Selesai (Sudah Diserahkan)`.
  - Admin dapat menekan tombol "Tandai Sudah Diserahkan" setelah siswa mengambil jerseynya di tempat latihan.

### 2.4 Desain Database (Draft Schema)
1. `products`:
   - `id`, `name`, `description`, `image_path`, `is_active`, `timestamps`
2. `product_variants`:
   - `id`, `product_id`, `name` (contoh: "Size M"), `price` (jika berbeda per size), `stock`
3. `orders`:
   - `id`, `user_id`, `siswa_id`, `invoice_id` (relasi ke tabel invoice utama untuk Xendit), `total_amount`, `status_pesanan` (pending, ready, completed)
4. `order_items`:
   - `id`, `order_id`, `product_variant_id`, `quantity`, `price_at_time`

### 2.5 Manajemen Stok & Inventori (Inventory Rules)
Untuk memastikan data stok produk/merchandise selalu akurat, sistem POS akan menerapkan aturan ketat berikut:
- **Pengurangan Stok (Stock Deduction)**: 
  - Stok produk (atau variannya) akan **dikurangi** (*deducted*) secara otomatis pada saat siswa/orang tua berhasil membuat pesanan (*Checkout*), meskipun statusnya masih `Menunggu Pembayaran` (Pending). Ini untuk mencegah masalah "berebut stok" antar pembeli jika stok sisa 1.
- **Pengembalian Stok (Stock Restitution)**:
  - Jika orang tua membatalkan pesanan secara manual, ATAU pesanan otomatis kedaluwarsa (*Expired*) dari Xendit karena batas waktu pembayaran habis (misal 1x24 jam), sistem akan **mengembalikan** (*restock*) jumlah barang ke database inventori agar dapat dibeli oleh orang lain.
- **Peringatan Stok Habis (Out of Stock Handling)**:
  - Sistem tidak mengizinkan pembelian *Pre-Order* tanpa stok (kecuali fitur *Pre-Order* dihidupkan secara spesifik nanti). 
  - Jika `stock` <= 0 pada `product_variants`, maka di halaman portal siswa, tombol "Beli" akan berubah menjadi "Stok Habis" dan dinonaktifkan secara otomatis.
- **Riwayat Stok (Stock Movements) - *Opsi Tambahan***:
  - Untuk kemudahan admin memantau aset, bisa ditambahkan tabel `inventory_movements` yang mencatat kapan barang masuk (restock oleh admin) dan kapan barang keluar (dibeli siswa), lengkap dengan tanggal dan pelakunya.

### 2.6 Distribusi & Keterlibatan Admin Kelas (Centralized vs Decentralized)
Terkait pertanyaan apakah setiap `admin_kelas` memiliki stok sendiri, berikut adalah **dua opsi** pendekatannya beserta rekomendasi:

**Opsi A: Stok Terpusat (Centralized) - *SANGAT DIREKOMENDASIKAN***
- **Konsep**: Hanya ada **1 Inventori Global** yang dipegang oleh Pusat (Pusat mengelola persediaan fisik).
- **Alur**: Saat siswa membeli barang, sistem mengurangi Stok Pusat. Pusat kemudian menyerahkan barang yang sudah dibayar tersebut kepada `admin_kelas` saat jadwal latihan, atau orang tua mengambilnya di kantor pusat.
- **Peran Admin Kelas**: `admin_kelas` tidak memusingkan jumlah stok fisik. Mereka hanya mendapatkan daftar pesanan "Lunas" dari siswanya, menerima barang titipan dari pusat, dan bertugas menekan tombol **"Tandai Sudah Diserahkan"** di aplikasi saat memberikan barang ke siswa.
- **Kelebihan**: Jauh lebih mudah dikelola, meminimalisir barang hilang/selisih stok di lapangan, dan mengurangi beban kerja pelatih/admin kelas.

**Opsi B: Multi-Cabang / Transfer Stok (Decentralized)**
- **Konsep**: Setiap `kelas` dianggap sebagai "Gudang Cabang".
- **Alur**: Admin Pusat harus melakukan "Transfer Stok" ke kelas A (misal: 10 Jersey M). Ketika siswa kelas A membeli, stok cabang kelas A yang berkurang.
- **Kelebihan**: Siswa bisa langsung membeli barang secara tunai di lapangan jika `admin_kelas` membawa stok lebih.
- **Kekurangan**: Sangat rumit. Rentan terjadi selisih (barang hilang di lapangan) dan `admin_kelas` dituntut memiliki keterampilan mengelola inventori fisik. Jika stok di kelas A sisa banyak tapi kelas B kehabisan, barang harus diretur dulu ke pusat, yang membuat sistem pencatatan sangat berat.

**Kesimpulan/Rekomendasi**: Mulailah dengan **Opsi A (Stok Terpusat)**. Sistem e-commerce modern (termasuk untuk sekolah/akademi) lebih mudah menggunakan 1 gudang utama, lalu produk didistribusikan ke kelas spesifik berdasarkan pesanan (order) yang sudah *Fixed* dan dibayar di sistem.

---

## 3. UI/UX & Design Consistency (Rules)
- **Theme**: Mengikuti desain saat ini (Vue 3 + Tailwind CSS + Inertia.js). Desain harus terlihat modern, clean, dan responsif di HP (karena 90% ortu mengakses via HP).
- **Component Reusability**:
  - Gunakan `DataTable` yang sama dengan filter interaktif.
  - Gunakan `Modal` Vue yang sama untuk proses checkout dan penambahan stok.
  - *Badges* warna-warni untuk membedakan status order (Kuning = Pending, Biru = Siap Diambil, Hijau = Selesai).

---

## Open Questions (Pertanyaan untuk Pengembangan Nanti)
1. **Keranjang Belanja (Cart)**: Apakah orang tua perlu bisa menambahkan SPP dan Jersey ke dalam satu "Keranjang" untuk dibayar dalam 1 tagihan Xendit sekaligus, atau pembayarannya dipisah (bayar SPP sendiri, beli Jersey sendiri)?
2. **Pengiriman**: Apakah produk murni hanya "Ambil di Tempat Latihan (Pickup)", atau ada opsi pengiriman kurir ke rumah yang butuh ongkos kirim?
3. **Stok**: Jika ukuran M habis saat orang tua mencoba beli, apakah tombol beli dinonaktifkan secara otomatis (sistem inventori ketat) atau dibiarkan *Pre-Order*? 

*(Dokumen ini akan menjadi acuan saat kita memulai fase pengembangan fitur ini di sesi mendatang).*
