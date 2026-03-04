<div class="modal fade" id="itemDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Detail Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>{{ __('label.asset_category') }}</h6>
                        <p>{{ $item->category->code }} - {{ $item->category->name }}</p>

                        <h6>{{ __('label.item_name') }}</h6>
                        <p>{{ $item->name ?? '-' }}</p>

                        <h6>{{ __('label.type') }}</h6>
                        <p>{{ $item->type ?? '-' }}</p>

                        <h6>{{ __('label.merk') }}</h6>
                        <p>{{ $item->merk ?? '-' }}</p>

                        <h6>{{ __('label.unit') }}</h6>
                        <p>{{ $item->unit ?? '-' }}</p>

                        <h6>{{ __('label.price') }}</h6>
                        <p>
                            @if ($item->price)
                                Rp {{ number_format($item->price, 0, ',', '.') }}
                            @else
                                -
                            @endif
                        </p>

                        <h6>{{ __('label.barcode') }}</h6>
                        <p>{{ $item->barcode ?? '-' }}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Foto Barang</h6>
                        @if ($item->photo)
                            <img src="{{ asset('storage/items/' . $item->photo) }}" alt="Foto Barang"
                                class="img-fluid">
                        @endif
                    </div>
                </div>

                <hr class="my-4">

                <h6>Keterangan</h6>
                @if ($item->description)
                    <div class="bg-light p-3 rounded">
                        {!! nl2br(e($item->description)) !!}
                    </div>
                @else
                    <p class="text-muted">Tidak ada keterangan</p>
                @endif

                <hr class="my-4">

                <div class="small text-muted">
                    <p>Dibuat: {{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d F Y, H:i') }}</p>
                    @if ($item->updated_at && $item->updated_at != $item->created_at)
                        <p>Diubah: {{ \Carbon\Carbon::parse($item->updated_at)->translatedFormat('d F Y, H:i') }}</p>
                    @endif
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
