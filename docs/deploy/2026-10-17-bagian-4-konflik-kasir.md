# Bagian 4: konflik kepentingan kasir

Siapa pun yang menjalankan aksi kasir tidak boleh memproses tagihan, pembayaran, atau tabungan **anaknya sendiri**. Berlaku untuk semua akun yang lolos route aksi tersebut (kasir, kasir unit, pegawai pemegang modul Kasir / Kasir Unit, penanggung jawab tabungan, ...). Aturan melekat pada aksinya, bukan pada role.

Tidak ada migration dan tidak ada perubahan `.env`.

## Definisi "anak sendiri"
`App\Services\CashierConflict`:
1. Relasi: baris orang tua dengan `parent.id_user = users.id` (termasuk akun dua peran dan akun hasil penggabungan), lalu santri dengan `id_parent` baris itu (termasuk santri yang sudah dihapus).
2. Cadangan: baris orang tua aktif dengan 10 digit terakhir `parent.phone` (format `08…`, `+62…`, `-`, spasi diabaikan) sama dengan 10 digit terakhir `users.phone`. Dilewati bila HP kosong atau kurang dari 10 digit.

## Aksi yang ditolak di server (HTTP 403, JSON `{status:false,message}`)
| Aksi | Route / method | Kode `action` di log |
|---|---|---|
| Bayar tagihan tunai / saldo | `finance.transaction.store` (santri di request **dan** pemilik tiap tagihan yang dipilih) | `bill-payment` |
| Approve manual transaksi pending | `finance.transaction.update.status` (status `paid`) | `pending-approve` |
| Batal transaksi pending | `finance.transaction.update.status` (selain `paid`) | `pending-cancel` |
| Setor kas | `finance.transaction.store.cash` / `store.unique-code` | `cash-deposit-store`, `unique-code-deposit-store` |
| Edit setoran kas | `update.cash` / `update.unique-code` (transaksi baru **dan** yang sudah ada di setoran) | `cash-deposit-update`, `unique-code-deposit-update` |
| Hapus setoran kas | `destroy.cash` / `destroy.unique-code` | `cash-deposit-destroy`, `unique-code-deposit-destroy` |
| Setoran tabungan tunai oleh kasir | `finance.savings.store` (jalur kasir) | `savings-deposit-cash` |
| Penarikan tabungan | `finance.savings.process.withdrawal` (kasir); `store.withdrawal`, `update.withdrawal`, `destroy.withdrawal` (penanggung jawab tabungan) | `savings-withdrawal-process`, `-store`, `-update`, `-destroy` |
| Topup / pengambilan saldo orang tua | `finance.balance.store.cash`, `finance.balance.store.withdrawal` (baris orang tua pelaku) | `balance-topup-cash`, `balance-withdrawal` |
| Hapus tagihan | `finance.bill.destroy`; `finance.bill.setup.destroy` (ditolak bila setup itu memuat anak pelaku) | `bill-destroy`, `bill-setup-destroy` |

- Setoran kas diperiksa **di dalam `DB::transaction`** setelah baris transaksi dikunci (`lockForUpdate`); satu transaksi anak sendiri membatalkan seluruh setoran.
- Penolakan dilempar sebagai `CashierConflictException`; pencatatan ke `activity_log` dilakukan saat exception dirender, yaitu **setelah** rollback, sehingga catatan tidak ikut terbatalkan.
- Catatan: `log_name = cashier-conflict`, `causer` = pelaku, `properties` = `action`, `student_ids`, `parent_ids`, `route`, `method`, `ip`.
  Cek di server: `SELECT created_at, causer_id, properties FROM activity_log WHERE log_name='cashier-conflict' ORDER BY id DESC;`

## Form bayar hanya memperingatkan
`finance.transaction.get.bill` mengirim `data.conflict`; halaman pembayaran kasir menampilkan peringatan kuning saat santri yang dicari adalah anak sendiri. Form tidak ditolak; server menolak saat disimpan.

## Setoran tabungan oleh orang tua tetap boleh
`finance.savings.store` dipakai bersama. Form orang tua selalu mengirim `payment_method` (form kasir tidak). Kasir yang juga orang tua yang mengirim `payment_method` untuk **anak sendiri** diproses sebagai orang tua (setoran menunggu, kedaluwarsa 2 hari). Untuk santri lain, atau tanpa `payment_method`, dia diperlakukan sebagai kasir. `Transaction::creating` juga disesuaikan: `finance.payment.store` dan `finance.balance.store` (route khusus orang tua) kini selalu memberi masa berlaku untuk akun dua peran, termasuk yang berrole kasir.

## Tidak termasuk (sengaja)
Generate / ubah setup tagihan dan diskon, donasi, verifikasi setoran oleh bendahara (`verify.cash`, `verify.unique-code`), laporan. Bila diinginkan, tambahkan `CashierConflict::assert(...)` pada aksi tersebut. Admin tidak lolos route kasir (`access:kasir…` hanya untuk role/modul terkait); pemeriksaan tetap bebas role bila suatu saat admin diberi akses.

## Dampak data lokal (SELECT saja)
Fallback nomor HP menemukan 12 akun staf dengan nomor sama dengan baris orang tua aktif. Yang relevan untuk aksi kasir: **DIDIK PUJAYANTO** (penanggung-jawab-tabungan, id 1201) memakai nomor yang sama dengan orang tua **HARDOSO** (parent 1151, 1 anak); bila ini orang berbeda, DIDIK akan ditolak mengurus tabungan anak HARDOSO. Selebihnya wali kelas / kepala sekolah yang tidak punya aksi kasir. Periksa nomor yang dipakai bersama sebelum deploy.

## Verifikasi setelah deploy
1. Login kasir yang juga orang tua; cari santri anaknya di Pembayaran Kasir: peringatan tampil. Simpan pembayaran: ditolak dengan pesan "Anda tidak dapat memproses…". Santri lain: berhasil.
2. `activity_log` berisi baris `cashier-conflict` dengan `action` yang sesuai.
3. Setoran tabungan dari menu orang tua untuk anak sendiri tetap membuat setoran menunggu.

## Rollback
Revert commit bagian 4. Tidak ada perubahan skema.
