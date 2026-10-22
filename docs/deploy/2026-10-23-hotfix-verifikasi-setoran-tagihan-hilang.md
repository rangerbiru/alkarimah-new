# 2026-10-23: Hotfix verifikasi setoran kas gagal ("Attempt to read property "bill" on null")

**Tanpa migration. Tidak mengubah data.** Satu file kode, satu file lang.

## Masalah
Bendahara tidak bisa membuka halaman verifikasi setoran kas (dan kasir tidak bisa mencetaknya): `Transaction::billsDetail` memanggil
`$transaction->bill->name` padahal baris `transaction_bill` yang ditunjuk `transaction.bills` sudah tidak ada.
Di DB lokal ada dua transaksi seperti itu: id 564 (`bills=[14375]`) dan 565 (`bills=[14376]`), keduanya `TR2026100013`, Rp200.000,
tunai, santri 1438, dibuat 7 Okt 2026.

## Perbaikan
`billsDetail` tidak lagi gagal bila baris tagihan, master tagihan, jenis, atau tahun ajarannya hilang. Barisnya tampil sebagai
"Tagihan #id (data tagihan sudah tidak ada)", jenis "Tidak diketahui", nominal = nominal transaksi bila hanya satu tagihan
(beberapa tagihan: 0 karena pembagiannya tidak diketahui). Kejadiannya dicatat `Log::warning`
("Transaction::billsDetail: data tagihan tidak lengkap", `transaction_id`, `transaction_bill_id`).

## File
| File | Perubahan |
|---|---|
| `app/Models/Transaction.php` | guard di `billsDetail`, `use Log` |
| `lang/{id,en}/string.php` | `bill_missing`, `bill_missing_type` |

## Verifikasi
`MissingBillDetailTest` (3 test): placeholder, tagihan lain tidak terpengaruh, halaman verifikasi bendahara 200 dan PDF setoran kasir 200.
Test gagal bila guard dibuang.

## Yang belum selesai (butuh keputusan dan akses tulis ke DB produksi)
Baris `transaction_bill` 14375 dan 14376 perlu dipulihkan dari backup (atau diganti baris yang sesuai) agar laporan tagihan dan
laporan siswa konsisten. Hotfix ini hanya membuat halamannya tidak error.

## Rollback
Revert commit ini.
