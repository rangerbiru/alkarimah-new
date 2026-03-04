<div id="wizard">
    <div class="wizard wizard-bar">
        <ul class="nav nav-tabs nav-fill" id="nav-wizard" role="tablist">
            @foreach ($steps as $index => $s)
                <li class="nav-item">
                    <a class="nav-link {{ ($index == 1) ? 'active' : '' }}" id="tab-step-{{ $index }}" data-toggle="tab" href="#step-{{ $index }}" role="tab" aria-controls="step-{{ $index }}" aria-selected="true">
                        <div class="d-none d-sm-block">
                            <i class="{{ $s->icon }}"></i> &nbsp;{{ $s->name }}
                        </div>
                        <div class="d-block d-sm-none">
                            <i class="{{ $s->icon }}"></i>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <form id="form-wizard">
            <div class="tab-content">
                @foreach ($steps as $index => $s)
                    <div class="tab-pane {{ ($index == 1) ? 'active' : '' }}" id="step-{{ $index }}" role="tabpanel" aria-labelledby="tab-step-{{ $index }}">
                        @include($s->form)
                    </div>
                @endforeach
            </div>
        </form>

        <hr />
        <button type="button" class="btn btn-secondary btn-back">
            <i class="fa-solid fa-chevron-left"></i> BACK
        </button>
        <button type="button" class="btn btn-success btn-continue">
            CONTINUE &nbsp;<i class="fa-solid fa-chevron-right"></i>
        </button>
        <button type="button" class="btn btn-success btn-submit btn-mdi" data-loading="SAVING...">
            <i class="mdi mdi-share-circle"></i>SAVE
        </button>
        <a href="{{ route($cancel_route) }}" class="btn btn-secondary btn-cancel">
            CANCEL
        </a>
    </div>
</div>

@push('scripts')
<script src="{{ asset('js/wizard.js') }}"></script>
<script>
var step_count = parseInt("{{ $step_count }}")
var step_last = step_count - 1

$(document).ready(function() {
    $("#wizard").bootstrapWizard({
        "tabClass": "nav nav-tabs",
        onTabClick: function(tab, navigation, index) {
            return false;
        },
        onTabShow: function(tab, navigation, index) {
            wizardControl(tab, navigation, index)
        }
    });

    $("#wizard .btn-back").click(function(){
        $("#wizard").bootstrapWizard("previous")
    })

    $("#wizard .btn-continue").click(function(){
        $("#wizard").bootstrapWizard("next")
    })
})

// If you need custom wizardControl function,
// please declar it on a form file that you use.
// Don't modify in here!!

if (typeof wizardControl !== 'function') {
    function wizardControl(tab, navigation, index)
    {
        $("#wizard .nav-tabs li:lt(" + index + ")").addClass("done")
        $("#wizard .nav-tabs li:gt(" + index + ")").removeClass("done")

        for (step=0; step<step_count; step++) {
            if (index == 0) { // for first step
                $(".btn-submit, .btn-back").hide()
                $(".btn-continue, .btn-cancel").show()
            } else if (index == step_last) { // for last step
                $(".btn-continue").hide()
                $(".btn-back, .btn-submit, .btn-cancel").show()
            } else {
                $(".btn-submit, .btn-cancel").hide()
                $(".btn-back, .btn-continue").show()
            }
        }
    }
}
</script>
@endpush