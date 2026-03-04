<tbody>
    <tr>
        <td class="ps-0">1</td>
        <td>{{ __('label.cash') }}</td>
        <td class="text-end">{{ number_format($recipient->cash, 0, '', '.') }}</td>
    </tr>
    <tr>
        <td class="ps-0">2</td>
        <td>{{ __('label.bank_bni') }}</td>
        <td class="text-end">{{ number_format($recipient->bni, 0, '', '.') }}</td>
    </tr>
    <tr>
        <td class="ps-0">3</td>
        <td>{{ __('label.bank_bsi') }}</td>
        <td class="text-end">{{ number_format($recipient->bsi, 0, '', '.') }}</td>
    </tr>
    <tr>
        <td class="ps-0">4</td>
        <td>{{ __('label.balance_topup') }}</td>
        <td class="text-end">{{ number_format($recipient->topup, 0, '', '.') }}</td>
    </tr>
</tbody>
<tfoot>
    <tr>
        <th class="ps-0 fw-bold" colspan="2">{{ __('label.total') }}</th>
        <th class="text-end fw-bold">{{ number_format($recipient->cash + $recipient->bni + $recipient->bsi + $recipient->topup, 0, '', '.') }}</th>
    </tr>
</tfoot>
