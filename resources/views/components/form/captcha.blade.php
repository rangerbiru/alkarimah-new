<div class="form-group">
    <label>Captcha</label>
    <div id="captcha" style="margin-top: 5px;margin-bottom: 5px;margin-right: 5px;">
        <span>{!! Captcha::img('flat') !!}</span>
        <a href="javascript:void(0)" id="captcha-refresh" class="text-success ml-1 set-tooltip" title="Refresh Code">
            <i class="fas fa-sync-alt"></i>
        </a>
    </div>

    <div class="input-group">
        <div class="input-group-prepend">
            <span class="input-group-text">
                <small><i class="fa-solid fa-lock"></i></small>
            </span>
        </div>
        <input type="text" name="captcha" class="form-control" placeholder="....." autocomplete="off">
    </div>
    <small class="text-muted">
        Masukan kode diatas untuk verifikasi.
    </small>
</div>

@push('scripts')
<script>
$("#captcha-refresh").click(function () {
    refreshCaptcha()
})

function refreshCaptcha()
{
    $.ajax({
        type: "GET",
        url: "{{ route('captcha.refresh') }}",
        success: function (response) {
            $("#captcha span").html(response.captcha)
        }
    })
}
</script>
@endpush