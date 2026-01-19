<div class="p-4">
    @if($row->application_text )
        <flux:callout icon="information-circle" variant="info">
            {{ $row->application_text }}
        </flux:callout>
    @endif

    <section>
        <flux:heading size="lg" class="mb-4">bisherige Zahlungen</flux:heading>

        <table class="table-auto w-full">
            <thead class="text-xs font-semibold uppercase border-b">
            <tr>
                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                    <div class="font-semibold">Satoshis</div>
                </th>
                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                    <div class="font-semibold">Jahr</div>
                </th>
                <th class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap text-left">
                    <div class="font-semibold">Event-ID</div>
                </th>
            </tr>
            </thead>
            <tbody class="text-sm divide-y">
            @foreach($row->payment_events as $payment)
                <tr>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="font-medium">{{ $payment->amount }}</div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div>{{ $payment->year }}</div>
                    </td>
                    <td class="px-2 first:pl-5 last:pr-5 py-3 whitespace-nowrap">
                        <div class="font-medium">{{ $payment->event_id }}</div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

</div>
