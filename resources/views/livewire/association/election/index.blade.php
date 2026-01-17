<x-layouts.app title="{{ __('Wahlen') }}">
    <div>
        <?php if($isAllowed): ?>
            <div class="relative flex h-full">
                <?php foreach($elections as $election): ?>
                    <div class="w-full sm:w-1/3 p-4">
                        <div class="shadow-lg rounded-lg overflow-hidden">
                            <?php echo e($election['year']); ?>
                        </div>
                        <div class="shadow-lg rounded-lg overflow-hidden">
                            <x-textarea wire:model="elections.<?php echo e($loop->index); ?>.candidates" rows="25"
                                        label="candidates" placeholder=""/>
                        </div>
                        <div class="py-2">
                            <x-button label="Speichern" wire:click="saveElection(<?php echo e($loop->index); ?>)"/>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">Einstellungen</h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, die Einstellungen zu bearbeiten.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</x-layouts.app>
