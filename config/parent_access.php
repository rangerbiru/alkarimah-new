<?php

/**
 * Allowlist route untuk akun yang sedang bertindak sebagai orang tua (akun orang tua biasa, atau
 * pegawai-orang-tua pada request ke route khusus orang tua). Route di luar daftar:
 *   mode "log"     -> tetap boleh, dicatat ke log (route, user, waktu)
 *   mode "enforce" -> diarahkan ke dashboard
 *
 * Pola mengikuti Str::is (boleh memakai *). Setiap route ber-middleware role:/access: yang memuat
 * orang-tua WAJIB ada di sini (dijaga test ParentRouteAllowlistTest).
 */
return [
    'mode' => env('PARENT_ROUTE_MODE', 'log'),

    'routes' => [
        // Route khusus orang tua
        'academic.student.index',
        'academic.student.children',
        'academic.student.show',
        'academic.student.edit',
        'academic.student.update.parent',
        'finance.balance.get',
        'finance.balance.get.history',
        'finance.balance.history',
        'finance.balance.index',
        'finance.balance.show',
        'finance.balance.store',
        'finance.balance.waiting',
        'finance.payment.check',
        'finance.payment.destroy',
        'finance.payment.get.bills',
        'finance.payment.get.history',
        'finance.payment.history',
        'finance.payment.index',
        'finance.payment.show',
        'finance.payment.store',
        'finance.payment.waiting',
        'finance.savings.get',
        'finance.savings.get.history',
        'finance.savings.history',
        'finance.savings.index',
        'finance.savings.show',
        'finance.savings.show-withdrawal',
        'finance.savings.store',
        'finance.savings.waiting',

        // Route umum yang dipakai halaman orang tua
        'dashboard.index',
        'dashboard.datatable.bill-not-paid',
        'user.profile',
        'user.profile.update',
        'user.password',
        'user.password.update',
        'attachment.*',
        'service.*',
        'academic.tahfidz.*',
        'academic.student-permit.*',
    ],
];
