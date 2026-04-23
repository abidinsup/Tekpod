PRODUCT REQUIREMENTS DOCUMENT (PRD)
WEB APP TRACKING PRODUKSI PERCETAKAN

---

1. TUJUAN PRODUK
   Membuat web aplikasi untuk:

* Melacak proses produksi percetakan dari awal hingga QC
* Mencatat hasil tiap proses (BGS, NC, NG)
* Menyediakan monitoring progress secara real-time
* Mengurangi human error dan kehilangan data produksi

---

2. TARGET USER

* Operator Produksi → input hasil proses
* Supervisor → monitoring & validasi
* Admin → manage order & sistem

---

3. RUANG LINGKUP (SCOPE)

Proses yang ditrack:

1. Cetak
2. Laminating (Gloss / Matte)
3. Spot UV
4. Hotstamp
5. Pond
6. Lem Mesin
7. QC / Sortir

---

4. KONSEP SISTEM

Setiap Order Produksi akan:

* Melewati beberapa proses
* Setiap proses memiliki hasil:

  * BGS (Bagus)
  * NC (Not Clean)
  * NG (Reject)

---

5. DATA MODEL (ENTITY)

5.1 Orders

* id
* nama_job
* pelanggan
* qty_order
* tanggal_mulai
* status (draft, progress, selesai)

5.2 Processes

* id
* nama_proses
* urutan

5.3 Production Logs

* id
* order_id
* process_id
* shift (nullable)
* tipe_proses (contoh: gloss/matte)
* hasil_bgs
* hasil_nc
* hasil_ng
* operator
* timestamp

5.4 Users

* id
* nama
* role (admin, operator, supervisor)
* email
* password

---

6. WORKFLOW

7. Admin membuat order

8. Operator memilih order

9. Operator input hasil proses

10. Sistem menyimpan log

11. Supervisor memonitor progress

12. QC finalize hasil

---

7. FITUR UTAMA

7.1 Dashboard

* List semua order
* Status: Belum mulai / On progress / Selesai
* Progress bar per order

7.2 Detail Order

* Menampilkan semua proses
* Hasil per proses
* Shift (jika ada)

7.3 Input Produksi
Form:

* Pilih order
* Pilih proses
* Pilih shift (opsional)
* Input BGS, NC, NG
* Submit

7.4 QC / Final Check

* Input total OK
* Input total Reject
* Keterangan

7.5 User Management

* Admin bisa tambah user
* Assign role

---

8. LOGIC BISNIS

8.1 Perhitungan

* Total output = BGS + NC + NG
* Loss = selisih antar proses

8.2 Validasi

* Input tidak boleh negatif
* Total output tidak boleh melebihi input sebelumnya

8.3 Status Order

* Draft → belum ada input
* Progress → sudah ada proses berjalan
* Selesai → QC selesai

---

9. UI / UX REQUIREMENTS

Prinsip:

* Simple
* Cepat (untuk operator)
* Mobile-friendly

Halaman:

1. Login
2. Dashboard
3. Detail Order
4. Input Produksi
5. Manajemen User

---

10. ROLE & PERMISSION

Admin: Full akses
Operator: Input produksi
Supervisor: View dan validasi

---

11. NON-FUNCTIONAL REQUIREMENTS

* Response cepat (<2 detik)
* Bisa diakses via mobile
* Data tersimpan real-time
* Backup data harian

---

12. FUTURE ENHANCEMENT

* Grafik produksi
* Analisis reject (NG)
* Integrasi barcode
* Notifikasi progress
* TV dashboard produksi

---

13. RISIKO & MITIGASI

Operator salah input → Gunakan dropdown & validasi
Data hilang → Backup otomatis
UI terlalu kompleks → Gunakan form sederhana

---

14. SUCCESS METRICS

* 100% proses tercatat
* Pengurangan error manual
* Monitoring real-time berjalan

---
