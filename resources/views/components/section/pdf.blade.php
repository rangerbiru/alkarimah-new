@if ($orientation == 'landscape')
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="padding-left: 30px;width: 180px;">
                <img src="{{ public_path('images/logo-text.png') }}" style="height: 50px;">
            </td>
            <td style="width: 830px;text-align: right;">
                <h2 style="margin-bottom: 5px;margin-top: 0;font-weight: bold;color: rgb(67, 72, 78);">
                    {{ $label }}
                </h2>
                <small>{{ Common::dateFormat(date('Y-m-d H:i:s'), 'dd mmmm yyyy, hh:ii WIB') }}</small>
            </td>
        </tr>
    </table>
    <div style="background: rgb(252, 171, 21);padding-top: 5px;margin-bottom: 15px;">&nbsp;</div>
@else
    <table style="margin-bottom: 15px;">
        <tr>
            <td style="padding-left: 30px;width: 180px;">
                <img src="{{ public_path('images/logo-text.png') }}" style="height: 50px;">
            </td>
            <td style="width: 495px;text-align: right;">
                <h2 style="margin-bottom: 5px;margin-top: 0;font-weight: bold;color: rgb(67, 72, 78);">
                    {{ $label }}
                </h2>
                <small>{{ Common::dateFormat(date('Y-m-d H:i:s'), 'dd mmmm yyyy, hh:ii WIB') }}</small>
            </td>
        </tr>
    </table>
    <div style="background: rgb(252, 171, 21);padding-top: 5px;margin-bottom: 15px;">&nbsp;</div>
@endif
