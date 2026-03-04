<div class="row">
    @foreach ($class as $c)
        <div class="col-sm-4">
            <div class="form-box-class mb-3">
                <div class="form-check form-check-md">
                    <input type="checkbox" class="form-check-input" name="class[]" id="class-{{ $c->id }}" value="{{ $c->id }}" />
                    <div class="form-check-label form-check-class d-flex" data-target="class-{{ $c->id }}">
                        <div class="ps-2 me-2"><i class="bx bx-chalkboard bx-md text-muted"></i></div>
                        <div class="form-check-text">{{ $c->name }}</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
