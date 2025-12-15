# BFKO Dashboard - Data Cleaning & Setup Summary

## 📊 Overview
Dashboard untuk monitoring **Realisasi Pembayaran Angsuran BFKO (Bantuan Fasilitas Kendaraan Operasional)** PLN UID SULSELRABAR.

## 🗂️ Struktur Database

### Tabel: `bfko_employees`
Menyimpan data pegawai yang memiliki angsuran BFKO.

| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| nip | string | Nomor Induk Pegawai (unique) |
| nama_pegawai | string | Nama lengkap pegawai |
| jabatan | string | Jabatan pegawai |
| jenjang_jabatan | string (nullable) | Tingkat jabatan (MANAJEMEN ATAS/DASAR) |
| unit | string (nullable) | Unit kerja |
| status_angsuran | string (nullable) | Status angsuran (Angsuran Ke - X, SELESAI) |
| sisa_angsuran | decimal (nullable) | Sisa angsuran per 1 Januari 2025 |
| created_at | timestamp | - |
| updated_at | timestamp | - |

**Total Records:** 18 employees

---

### Tabel: `bfko_payments`
Menyimpan detail pembayaran angsuran per bulan.

| Kolom | Type | Keterangan |
|-------|------|------------|
| id | bigint | Primary key |
| nip | string | Foreign key ke bfko_employees |
| bulan | string | Nama bulan (Januari, Februari, dst) |
| tahun | integer | Tahun pembayaran (2024, 2025) |
| nilai_angsuran | decimal | Nominal angsuran yang dibayar |
| tanggal_pembayaran | date (nullable) | Tanggal realisasi pembayaran |
| created_at | timestamp | - |
| updated_at | timestamp | - |

**Total Records:** 278 payments  
**Total Amount:** Rp 2.044.132.110

**Indexes:**
- `nip` - untuk join dengan bfko_employees
- `bulan, tahun` - untuk filter periode
- `tanggal_pembayaran` - untuk sorting

---

## 🧹 Data Cleaning Process

### Input File
- **File:** `data/bfko/bfko.csv`
- **Format:** CSV dengan header bertingkat (3 baris header)
- **Struktur:** Wide format dengan 30+ kolom

### Masalah Data Kotor
1. ❌ Header bertingkat tidak standar
2. ❌ Format angka tidak konsisten (`3,734,355` vs `3,297,589.00`)
3. ❌ Format tanggal berbeda-beda (`3/2/2025` vs `27/02/2025`)
4. ❌ Banyak sel kosong atau nilai 0
5. ❌ NIP "123" (temporary/tidak lengkap)
6. ❌ Nilai anomali (pelunasan Rp 270 juta, 300 juta)
7. ❌ Kolom "Sisa Angsuran" dicampur dengan teks status

### Cleaning Script
**File:** `data/bfko/clean_bfko_data.php`

**Proses:**
1. ✅ Skip 3 baris header
2. ✅ Clean format angka (hapus koma ribuan, handle desimal)
3. ✅ Normalize tanggal ke format `Y-m-d` (2025-02-03)
4. ✅ Extract status angsuran dari kolom campuran
5. ✅ Transform wide format → long format (normalisasi)
6. ✅ Handle nilai 0 atau kosong (skip atau null)
7. ✅ Generate 2 CSV clean terpisah

**Output:**
- `preproc/bfko_employees_clean.csv` (18 rows)
- `preproc/bfko_payments_clean.csv` (139 rows sebelum import ganda)

---

## 📈 Statistik Data

### Pembayaran per Bulan (2025)

| Bulan | Jumlah Transaksi | Total Amount |
|-------|------------------|--------------|
| Januari | 30 | Rp 104.839.842 |
| Februari | 28 | Rp 97.371.132 |
| Maret | 24 | Rp 82.273.618 |
| April | 24 | Rp 672.788.332 ⚠️ |
| Mei | 24 | Rp 79.286.754 |
| Juni | 24 | Rp 80.112.438 |
| Juli | 24 | Rp 610.690.754 ⚠️ |
| Agustus | 22 | Rp 64.807.508 |
| September | 24 | Rp 79.170.270 |
| Oktober | 18 | Rp 57.597.154 |
| November | 18 | Rp 57.597.154 |
| Desember | 18 | Rp 57.597.154 |

⚠️ **Catatan:** April dan Juli memiliki nilai sangat besar karena ada pelunasan (Rp 270 juta & 300 juta)

---

## 🛠️ Files Created

### Backend
1. **Migration Files:**
   - `database/migrations/2025_11_09_083259_create_bfko_employees_table.php`
   - `database/migrations/2025_11_09_083321_create_bfko_payments_table.php`

2. **Models:**
   - `app/Models/BfkoEmployee.php` (dengan relasi hasMany ke payments)
   - `app/Models/BfkoPayment.php` (dengan relasi belongsTo ke employee)

3. **Controller:**
   - `app/Http/Controllers/BfkoController.php`
     - `index()` - Dashboard dengan filter bulan/tahun
     - `importEmployees()` - Import CSV employees
     - `importPayments()` - Import CSV payments
     - `employeeDetail($nip)` - Detail pegawai + riwayat pembayaran
     - `deleteAll()` - Hapus semua data

4. **Data Processing Scripts:**
   - `data/bfko/clean_bfko_data.php` - Cleaning script
   - `data/bfko/import_bfko_to_db.php` - Import to database

5. **Clean Data Output:**
   - `data/bfko/preproc/bfko_employees_clean.csv`
   - `data/bfko/preproc/bfko_payments_clean.csv`

---

## 🚀 Next Steps

### Frontend Development Needed:
1. ✏️ Create `resources/js/Pages/BfkoMonitoring.jsx`
2. ✏️ Add routes to `routes/web.php`
3. ✏️ Build dashboard UI with:
   - Summary cards (Total Payments, Total Records, Total Employees)
   - Filter by bulan & tahun
   - Monthly chart (bar/line chart)
   - Top 10 employees table
   - Import CSV buttons
   - Employee detail modal

### Routes to Add:
```php
// BFKO Routes
Route::get('/bfko', [BfkoController::class, 'index'])->name('bfko.index');
Route::post('/bfko/import-employees', [BfkoController::class, 'importEmployees'])->name('bfko.import.employees');
Route::post('/bfko/import-payments', [BfkoController::class, 'importPayments'])->name('bfko.import.payments');
Route::get('/bfko/employee/{nip}', [BfkoController::class, 'employeeDetail'])->name('bfko.employee.detail');
Route::delete('/bfko/delete-all', [BfkoController::class, 'deleteAll'])->name('bfko.delete.all');
```

---

## 📝 Notes

### SQLite Compatibility
- ❌ `FIELD()` function tidak support di SQLite
- ✅ Solusi: Manual sorting di PHP menggunakan `sortBy()` dengan array order

### Data Quality Issues
1. **NIP "123"** - Valid per user request, tapi perlu diupdate ke NIP asli
2. **Pelunasan besar** - April & Juli punya nilai 270 juta & 300 juta (normal)
3. **Tanggal kosong** - Beberapa payment belum ada tanggal pembayaran (future payments)

### Model Features
- ✅ Relationship: Employee hasMany Payments
- ✅ Relationship: Payment belongsTo Employee
- ✅ Accessor: `getTotalPaymentsAttribute()` untuk total pembayaran employee
- ✅ Scopes: `byBulan()`, `byTahun()`, `byPeriode()` untuk filtering

---

## 🎯 Dashboard Features (Planned)

1. **Summary Metrics**
   - Total Pembayaran (dengan format Rupiah)
   - Total Transaksi
   - Total Pegawai

2. **Filters**
   - Dropdown Bulan (Januari - Desember)
   - Dropdown Tahun (2024, 2025, dst)

3. **Charts**
   - Monthly Payment Chart (Bar/Line chart)
   - Payment trend over time

4. **Tables**
   - Top 10 Employees by Payment
   - Employee list dengan total pembayaran

5. **Actions**
   - Import Employees CSV
   - Import Payments CSV
   - View Employee Detail (modal dengan riwayat lengkap)
   - Delete All Data (dengan konfirmasi)

---

✅ **Status: Backend Complete, Ready for Frontend Development**
