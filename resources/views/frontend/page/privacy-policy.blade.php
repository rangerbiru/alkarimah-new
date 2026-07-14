@extends('layouts.frontend.index')

@section('content')
    <div class="card custom-card rectangle2 shadow-sm border-0 mb-5">
        <div class="card-body p-4 p-md-5">
            <div class="text-center mb-5 border-bottom pb-4">
                <a href="{{ route('base') }}">
                    <img src="{{ asset('images/logo.png') }}" style="width: 60px;" alt="logo" class="desktop-dark mb-3">
                </a>
                <h3 class="fw-bold text-dark mb-1">Kebijakan Privasi Aplikasi SI-Alka</h3>
                <span class="text-muted small"><i class="fa-solid fa-clock me-1"></i> Terakhir diperbarui: 14 Juli
                    2026</span>
            </div>

            <div class="privacy-content" style="line-height: 1.8; color: #4a5568;">
                <p class="mb-4">
                    <strong>SI-Alka</strong> adalah aplikasi Sistem Informasi Al Karimah yang digunakan untuk membantu
                    santri dan wali santri mengakses informasi administrasi, akademik, pembayaran, tabungan, dan layanan
                    pesantren.
                    Dengan menggunakan aplikasi SI-Alka, pengguna dianggap telah membaca dan memahami Kebijakan Privasi ini.
                </p>

                <h5 class="fw-bold mt-5 text-dark">1. Data yang Kami Kumpulkan</h5>
                <p>Aplikasi SI-Alka dapat menampilkan dan/atau memproses data berikut:</p>
                <ul style="list-style-type: disc; margin-left: 20px;" class="mb-3">
                    <li>Nama santri</li>
                    <li>Nomor induk santri atau NIS</li>
                    <li>NISN jika tersedia</li>
                    <li>Jenis kelamin</li>
                    <li>Agama</li>
                    <li>Tempat dan tanggal lahir</li>
                    <li>Nama orang tua atau wali</li>
                    <li>Alamat</li>
                    <li>Informasi akademik</li>
                    <li>Informasi tagihan pembayaran</li>
                    <li>Riwayat pembayaran</li>
                    <li>Informasi tabungan santri</li>
                    <li>Nomor telepon atau email yang digunakan untuk login</li>
                    <li>Data perangkat tertentu yang diperlukan untuk keamanan, notifikasi, dan kelancaran layanan aplikasi
                    </li>
                </ul>
                <p>Data tersebut digunakan hanya untuk keperluan layanan administrasi dan informasi Pondok Pesantren Al
                    Karimah.</p>

                <h5 class="fw-bold mt-5 text-dark">2. Tujuan Penggunaan Data</h5>
                <p>Data pengguna digunakan untuk:</p>
                <ul style="list-style-type: disc; margin-left: 20px;" class="mb-3">
                    <li>Menampilkan profil santri</li>
                    <li>Menampilkan informasi akademik</li>
                    <li>Menampilkan tagihan dan rincian pembayaran</li>
                    <li>Menampilkan riwayat pembayaran</li>
                    <li>Menampilkan informasi tabungan</li>
                    <li>Memberikan layanan informasi kepada santri dan wali santri</li>
                    <li>Mengamankan akses akun pengguna</li>
                    <li>Memperbaiki dan meningkatkan kualitas aplikasi</li>
                </ul>

                <h5 class="fw-bold mt-5 text-dark">3. Pembagian Data kepada Pihak Lain</h5>
                <p>Kami tidak menjual data pribadi pengguna kepada pihak mana pun.</p>
                <p>Data hanya dapat digunakan oleh pihak internal Pondok Pesantren Al Karimah atau pihak penyedia layanan
                    yang membantu operasional aplikasi, seperti layanan server, sistem pembayaran, perbankan, atau
                    notifikasi, sesuai kebutuhan layanan.</p>

                <h5 class="fw-bold mt-5 text-dark">4. Keamanan Data</h5>
                <p>Kami berupaya menjaga keamanan data pengguna dengan membatasi akses hanya kepada pihak yang berwenang dan
                    menggunakan sistem keamanan yang sesuai untuk melindungi data dari akses yang tidak sah.</p>
                <p>Namun, pengguna juga bertanggung jawab untuk menjaga kerahasiaan akun, email, nomor telepon, dan password
                    yang digunakan untuk masuk ke aplikasi.</p>

                <h5 class="fw-bold mt-5 text-dark">5. Penyimpanan Data</h5>
                <p>Data pengguna disimpan selama masih diperlukan untuk keperluan administrasi pesantren, layanan
                    pendidikan, pembayaran, dan kewajiban pencatatan internal.</p>

                <h5 class="fw-bold mt-5 text-dark">6. Penghapusan Akun dan Data</h5>
                <p>Pengguna dapat mengajukan permintaan penghapusan akun atau data pribadi dengan menghubungi pihak
                    pengelola aplikasi melalui kontak resmi berikut:</p>
                <p class="mb-3"><strong>Email:</strong> <a href="mailto:admin@alkarimah.org"
                        class="text-primary text-decoration-none">admin@alkarimah.org</a></p>
                <p>Permintaan akan diproses sesuai ketentuan administrasi Pondok Pesantren Al Karimah dan kewajiban
                    penyimpanan data yang berlaku.</p>

                <h5 class="fw-bold mt-5 text-dark">7. Privasi Anak</h5>
                <p>Aplikasi ini dapat digunakan untuk menampilkan data santri. Akses aplikasi diberikan kepada pihak yang
                    berwenang, seperti santri, orang tua, wali santri, atau pihak pesantren.</p>
                <p>Kami tidak menggunakan data anak untuk iklan atau penjualan data kepada pihak lain.</p>

                <h5 class="fw-bold mt-5 text-dark">8. Perubahan Kebijakan Privasi</h5>
                <p>Kebijakan Privasi ini dapat diperbarui sewaktu-waktu apabila terdapat perubahan fitur, layanan, atau
                    ketentuan yang berlaku. Perubahan akan ditampilkan pada halaman ini.</p>

                <h5 class="fw-bold mt-5 text-dark">9. Kontak</h5>
                <p>Jika ada pertanyaan terkait Kebijakan Privasi ini, pengguna dapat menghubungi:</p>

                <div class="bg-light p-4 rounded-3 border mt-3">
                    <h6 class="fw-bold mb-2">Pondok Pesantren Al Karimah</h6>
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><i class="fa-solid fa-map-marker-alt text-muted me-2"></i> Mandungan,
                            Karanganyar, Jawa Tengah 57713</li>
                        <li><i class="fa-solid fa-envelope text-muted me-2"></i> <a href="mailto:admin@alkarimah.org"
                                class="text-primary text-decoration-none">admin@alkarimah.org</a></li>
                    </ul>
                </div>

            </div>
        </div>
    </div>
@endsection
