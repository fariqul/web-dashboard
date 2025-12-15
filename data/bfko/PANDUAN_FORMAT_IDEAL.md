# 📋 PANDUAN FORMAT IDEAL - BFKO Dashboard

## 🎯 Format CSV yang Digunakan

### Header (Wajib):
```csv
nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
```

### Penjelasan Kolom:

| Kolom | Wajib? | Tipe | Contoh | Keterangan |
|-------|--------|------|--------|------------|
| **nip** | ✅ Ya | Text | 7195001K | Nomor Induk Pegawai |
| **nama** | ✅ Ya | Text | DARMADI | Nama lengkap pegawai |
| **jabatan** | ⚠️ Opsional | Text | SRM NIAGA DAN MP | Jabatan pegawai |
| **unit** | ⚠️ Opsional | Text | UID SULSELRABAR | Unit kerja |
| **bulan** | ✅ Ya | Text | Januari | Nama bulan (Januari-Desember) |
| **tahun** | ✅ Ya | Number | 2025 | Tahun pembayaran (2024, 2025, 2026, dst) |
| **nilai_angsuran** | ✅ Ya | Number | 3500000 | Nominal angsuran (tanpa titik/koma) |
| **tanggal_bayar** | ⚠️ Opsional | Date | 2025-01-15 | Format: YYYY-MM-DD (kosongkan jika belum bayar) |
| **status_angsuran** | ⚠️ Opsional | Text | Angsuran Ke-24 | Status angsuran (Angsuran Ke-X, SELESAI, LUNAS, dll) |

---

## ✅ Contoh Data yang BENAR

```csv
nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Januari,2025,3500000,2025-01-15,Angsuran Ke-1
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Februari,2025,3500000,2025-02-15,Angsuran Ke-2
7194010G,ALEXANDER J. MANUHUWA,MANAGER UP3,UP3 PALOPO,Januari,2025,2987484,2025-01-03,Angsuran Ke-24
7194010G,ALEXANDER J. MANUHUWA,MANAGER UP3,UP3 PALOPO,Februari,2025,2987484,,Angsuran Ke-24
```

**Poin Penting:**
- ✅ Satu baris = 1 pembayaran untuk 1 pegawai di 1 bulan
- ✅ Pegawai yang sama akan muncul berkali-kali (setiap bulan = 1 baris)
- ✅ Nilai angsuran TANPA titik atau koma (3500000 bukan 3.500.000)
- ✅ Tanggal kosong boleh (untuk pembayaran yang belum dilakukan)

---

## ❌ Kesalahan yang Sering Terjadi

### 1. Format Nilai Angsuran Salah
```csv
❌ SALAH: nilai_angsuran = "3.500.000" atau "Rp 3.500.000"
✅ BENAR: nilai_angsuran = 3500000
```

### 2. Format Tanggal Salah
```csv
❌ SALAH: tanggal_bayar = "15/01/2025" atau "15-01-2025"
✅ BENAR: tanggal_bayar = "2025-01-15"
```

### 3. Nama Bulan Salah Eja
```csv
❌ SALAH: bulan = "january", "Jan", "01"
✅ BENAR: bulan = "Januari"
```

**Nama bulan yang valid:**
- Januari, Februari, Maret, April, Mei, Juni, Juli, Agustus, September, Oktober, November, Desember

### 4. Duplikat Data
```csv
❌ SALAH: NIP yang sama, bulan sama, tahun sama (akan error atau overwrite)
✅ BENAR: Kombinasi NIP + Bulan + Tahun harus UNIK
```

---

## 🚀 Cara Menggunakan

### 1. Untuk Data Baru (Pertama Kali)
1. Download template: `TEMPLATE_BFKO.csv`
2. Isi data sesuai format di atas
3. Save as CSV (UTF-8)
4. Upload via dashboard: **Import Data BFKO**

### 2. Untuk Update Data Bulanan
1. Copy file CSV yang sudah ada
2. Tambah baris baru untuk bulan berikutnya
3. Pastikan data pegawai (nip, nama, jabatan, unit) tetap sama
4. Update ke dashboard

**Contoh: Menambah data Maret 2025**
```csv
... data bulan sebelumnya ...
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Maret,2025,3500000,2025-03-15,Angsuran Ke-3
7194010G,ALEXANDER J. MANUHUWA,MANAGER UP3,UP3 PALOPO,Maret,2025,2987484,2025-03-10,Angsuran Ke-24
```

### 3. Untuk Data Multi-Tahun
Format yang sama berlaku untuk tahun kapanpun:

```csv
nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Januari,2024,3000000,2024-01-15,Angsuran Ke-1
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Februari,2024,3000000,2024-02-15,Angsuran Ke-2
...
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Desember,2024,3000000,2024-12-15,Angsuran Ke-12
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Januari,2025,3500000,2025-01-15,Angsuran Ke-13
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Februari,2025,3500000,2025-02-15,Angsuran Ke-14
```

---

## 💡 Tips & Best Practices

### 1. Konsistensi Data Pegawai
Pastikan untuk pegawai yang sama, data tetap konsisten:
```csv
✅ BENAR:
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Januari,2025,3500000,...
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Februari,2025,3500000,...

❌ SALAH (nama berbeda):
7195001K,DARMADI,SRM NIAGA DAN MP,UID SULSELRABAR,Januari,2025,3500000,...
7195001K,Darmadi,SRM NIAGA,UID SULSELRABAR,Februari,2025,3500000,...
```

### 2. Backup Data
Simpan backup CSV setiap kali import berhasil

### 3. Validasi Sebelum Import
- Cek tidak ada baris kosong
- Cek format tanggal konsisten
- Cek nilai angsuran adalah angka murni

### 4. Urutan Baris
Tidak harus urut, tapi akan lebih mudah dibaca jika diurutkan:
- Pertama: NIP
- Kedua: Tahun (ascending)
- Ketiga: Bulan (Januari → Desember)

---

## 🔄 Proses Import

### Yang Terjadi Saat Import:
1. ✅ Sistem membaca file CSV baris per baris
2. ✅ Cek apakah kombinasi NIP + Bulan + Tahun sudah ada
   - Jika **BELUM ADA**: Insert data baru
   - Jika **SUDAH ADA**: Update data yang lama
3. ✅ Validasi format data
4. ✅ Tampilkan hasil: "X data baru, Y data diupdate"

### Error yang Mungkin Muncul:
- **"Validation failed"**: Format kolom wajib tidak terisi
- **"Duplicate entry"**: Ada duplikat di file CSV yang sama
- **"Import gagal"**: File corrupt atau format salah

---

## 📊 Hasil di Dashboard

Setelah import berhasil, data akan tampil di:
1. **Summary Cards**: Total Pembayaran, Total Transaksi, Total Pegawai
2. **Chart Bulanan**: Agregasi per bulan
3. **Top 10 Pegawai**: Ranking berdasarkan total pembayaran
4. **Filter**: Bisa filter by Bulan & Tahun

---

## 🎓 Contoh Kasus Penggunaan

### Kasus 1: Upload Data Tahun 2025 Pertama Kali
```csv
nip,nama,jabatan,unit,bulan,tahun,nilai_angsuran,tanggal_bayar,status_angsuran
7195001K,DARMADI,SRM,UID SULSELRABAR,Januari,2025,3500000,2025-01-15,Angsuran Ke-1
7195001K,DARMADI,SRM,UID SULSELRABAR,Februari,2025,3500000,2025-02-15,Angsuran Ke-2
7194010G,ALEXANDER,MANAGER,UP3 PALOPO,Januari,2025,3000000,2025-01-20,Angsuran Ke-1
7194010G,ALEXANDER,MANAGER,UP3 PALOPO,Februari,2025,3000000,2025-02-20,Angsuran Ke-2
```

### Kasus 2: Update Data Maret (File Lama + Tambahan)
Buka file CSV lama, tambah baris baru:
```csv
... semua data Januari & Februari ...
7195001K,DARMADI,SRM,UID SULSELRABAR,Maret,2025,3500000,2025-03-15,Angsuran Ke-3
7194010G,ALEXANDER,MANAGER,UP3 PALOPO,Maret,2025,3000000,,Angsuran Ke-3
```

### Kasus 3: Koreksi Data yang Salah
Ubah nilai di file CSV, lalu import ulang. Sistem akan update otomatis:
```csv
7195001K,DARMADI,SRM,UID SULSELRABAR,Januari,2025,3600000,2025-01-15,Angsuran Ke-1
```
*Nilai berubah dari 3.500.000 → 3.600.000*

---

## ✅ Checklist Sebelum Import

- [ ] Header CSV sudah benar (9 kolom)
- [ ] Tidak ada baris kosong
- [ ] NIP tidak kosong
- [ ] Nama tidak kosong
- [ ] Bulan tidak kosong (nama bulan Indonesia)
- [ ] Tahun tidak kosong (4 digit)
- [ ] Nilai angsuran tidak kosong (angka murni tanpa format)
- [ ] Tanggal format YYYY-MM-DD (jika diisi)
- [ ] Tidak ada duplikat NIP + Bulan + Tahun
- [ ] File disave sebagai CSV (UTF-8)

---

## 📞 Troubleshooting

### Problem: "Import gagal: Validation failed"
**Solusi:** Cek kolom wajib (nip, nama, bulan, tahun, nilai_angsuran) sudah terisi semua

### Problem: "Duplicate entry"
**Solusi:** Cek ada duplikat NIP + Bulan + Tahun di file CSV

### Problem: Angka tidak terbaca dengan benar
**Solusi:** Hapus format ribuan (titik/koma) dari nilai_angsuran

### Problem: Tanggal tidak valid
**Solusi:** Ubah format ke YYYY-MM-DD atau kosongkan kolom

---

**Format ini dirancang untuk:**
- ✅ Mudah dimengerti
- ✅ Mudah diisi manual di Excel/Google Sheets
- ✅ Mudah di-generate dari sistem lain
- ✅ Support multi-tahun tanpa batas
- ✅ Fleksibel untuk update berkala

**Gunakan template:** `TEMPLATE_BFKO.csv` sebagai starting point! 🚀
