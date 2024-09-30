<div class="p-4">

    <div class="px-4 py-2 rounded-lg text-sm bg-violet-100 text-gray-700">
        @if($row->application_text )
            <div class="flex w-full justify-between items-start">
                <div class="flex">
                    <svg class="shrink-0 fill-current text-violet-500 mt-[3px] mr-3" width="16" height="16" viewBox="0 0 16 16">
                        <path d="M8 0C3.6 0 0 3.6 0 8s3.6 8 8 8 8-3.6 8-8-3.6-8-8-8zm1 12H7V7h2v5zM8 6c-.6 0-1-.4-1-1s.4-1 1-1 1 .4 1 1-.4 1-1 1z"></path>
                    </svg>
                    <div>{{ $row->application_text }}</div>
                </div>
            </div>
        @else
            keine Bewerbung vorhanden
        @endif
    </div>

    <section class="mt-4">
        <h3 class="text-xl leading-snug text-gray-800 dark:text-gray-100 font-bold mb-1">
            bisherige Zahlungen</h3>
        <!-- Table -->
        <table class="table-auto w-full dark:text-gray-400">
            <!-- Table header -->
            <thead
                class="text-xs uppercase text-gray-400 dark:text-gray-500">
            <tr class="flex flex-wrap md:table-row md:flex-no-wrap">
                <th class="w-full block md:w-auto md:table-cell py-2">
                    <div class="font-semibold text-left">Satoshis</div>
                </th>
                <th class="w-full hidden md:w-auto md:table-cell py-2">
                    <div class="font-semibold text-left">Jahr</div>
                </th>
                <th class="w-full hidden md:w-auto md:table-cell py-2">
                    <div class="font-semibold text-left">Event-ID</div>
                </th>
            </tr>
            </thead>
            <!-- Table body -->
            <tbody class="text-sm">
            @foreach($row->paymentEvents as $payment)
                <tr class="flex flex-wrap md:table-row md:flex-no-wrap border-b border-gray-200 dark:border-gray-700/60 py-2 md:py-0">
                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                        <div
                            class="text-left font-medium text-gray-800 dark:text-gray-100">{{ $payment->amount }}</div>
                    </td>
                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                        <div
                            class="text-left">{{ $payment->year }}</div>
                    </td>
                    <td class="w-full block md:w-auto md:table-cell py-0.5 md:py-2">
                        <div
                            class="text-left font-medium">{{ $payment->event_id }}</div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

</div>
