<?php

namespace App\Http\Middleware;

use App\Services\TransactionCancellation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Symfony\Component\HttpFoundation\Response;

/**
 * Pemisahan tugas pembatalan transaksi: pegawai pemegang modul Kasir / Kasir Unit tidak boleh membukanya
 * walaupun juga memegang modul Pembatalan Transaksi (aturan yang sama dengan TransactionCancellation::allowed).
 */
class CancellationAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app(TransactionCancellation::class)->allowed(Auth::user())) {
            return Redirect::route('base');
        }

        return $next($request);
    }
}
