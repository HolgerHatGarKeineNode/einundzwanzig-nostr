<div>
    @php
        $positions = [
            'presidency' => ['icon' => 'fa-crown', 'title' => 'Präsidium'],
            'board' => ['icon' => 'fa-users', 'title' => 'Vorstandsmitglieder'],
        ];
    @endphp

    @if($isAllowed)

        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto" x-data="electionAdminCharts()">

        <!-- Dashboard actions -->
        <div class="sm:flex sm:justify-between sm:items-center mb-8">

            <!-- Left: Title -->
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold">
                    Wahl des Vorstands {{ $election->year }}
                </h1>
            </div>

        </div>

        @php
            $president = $positions['presidency'];
            $board = $positions['board'];
        @endphp

            <!-- Cards -->
        <div class="grid gap-y-4">
            <div wire:key="presidency" wire:ignore
                 class="flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100"><i
                            class="fa-sharp-duotone fa-solid {{ $president['icon'] }} w-5 h-5 fill-current text-white mr-4"></i>{{ $president['title'] }}
                    </h2>
                </header>
                <div class="grow">
                    <!-- Change| height attribute to adjust chart height -->
                    <canvas x-ref="chart_presidency" width="724" height="288"
                            style="display: block; box-sizing: border-box; height: 288px; width: 724px;"></canvas>
                </div>
            </div>
            <div wire:key="board" wire:ignore
                 class="flex flex-col bg-white dark:bg-gray-800 shadow-sm rounded-xl">
                <header class="px-5 py-4 border-b border-gray-100 dark:border-gray-700/60">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100"><i
                            class="fa-sharp-duotone fa-solid {{ $board['icon'] }} w-5 h-5 fill-current text-white mr-4"></i>{{ $board['title'] }}
                    </h2>
                </header>
                <div class="grow">
                    <!-- Change| height attribute to adjust chart height -->
                    <canvas x-ref="chart_board" width="724" height="288"
                            style="display: block; box-sizing: border-box; height: 288px; width: 724px;"></canvas>
                </div>
            </div>
        </div>

    </div>

    @else
        <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
            <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">Mitglieder</h3>
                    <p class="mt-1 max-w">
                        Du bist nicht berechtigt, Mitglieder zu bearbeiten.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
