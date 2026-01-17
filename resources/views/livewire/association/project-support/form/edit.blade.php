<x-layouts.app title="<?php echo e($projectProposal->name); ?>">
    <div>
        <?php if($isAllowed): ?>
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <form class="space-y-8 divide-y divide-gray-700 pb-24">
                    <div class="space-y-8 divide-y divide-gray-700 sm:space-y-5">
                        <div class="mt-6 sm:mt-5 space-y-6 sm:space-y-5">

                            <x-input.group :for=" md5('image')" :label="__('Bild')">
                                <div class="py-4">
                                    <?php if ($image && method_exists($image, 'temporaryUrl') && str($image->getMimeType())->contains(['image/jpeg','image/jpg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'])): ?>
                                        <div class="text-gray-200">{{ __('Preview') }}:</div>
                                        <img class="h-48 object-contain" src="<?php echo e($image->temporaryUrl()); ?>">
                                    <?php endif; ?>
                                    <?php if (isset($projectProposal) && $projectProposal->getFirstMediaUrl('main')): ?>
                                        <div class="text-gray-200">{{ __('Current picture') }}:</div>
                                        <img class="h-48 object-contain"
                                             src="<?php echo e($projectProposal->getFirstMediaUrl('main')); ?>">
                                    <?php endif; ?>
                                </div>
                                <input class="text-gray-200" type="file" wire:model="image">
                                <?php $__errorArgs = ['image'];
$__bag = $errors->getBag($__errorProps ?? 'default');
if ($__bag->has($__errorArgs)) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs); ?>
                                    <span class="text-red-500"><?php echo e($message); ?></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </x-input.group>

                            <x-input.group :for="md5('form.name')" :label="__('Name')">
                                <x-input autocomplete="off" wire:model.debounce="form.name"
                                         :placeholder="__('Name')"/>
                            </x-input.group>

                            <x-input.group :for="md5('form.website')" :label="__('Webseite des Projekts')">
                                <x-input autocomplete="off" wire:model.debounce="form.website"
                                         :placeholder="__('Website')"
                                         description="Eine valide URL beginnt immer mit https://"
                                />
                            </x-input.group>

                            <x-input.group :for="md5('form.name')" :label="__('Beabsichtigte Unterstützung in Sats')">
                                <x-input type="number" autocomplete="off" wire:model.debounce="form.support_in_sats"
                                         :placeholder="__('Beabsichtigte Unterstützung in Sats')"/>
                            </x-input.group>

                            <x-input.group :for="md5('form.accepted')" :label="__('Wurde angenommen')">
                                <x-checkbox autocomplete="off" wire:model.debounce="form.accepted"/>
                            </x-input.group>

                            <x-input.group :for="md5('form.sats_paid')" :label="__('Letztendlich bezahlte Satoshis')">
                                <x-input autocomplete="off" wire:model.debounce="form.sats_paid"
                                         :placeholder="__('Satoshi-Anzahl')"/>
                            </x-input.group>

                            <x-input.group :for="md5('form.description')">
                                <x-slot name="label">
                                    <div>
                                        <?php echo e(__('Beschreibung')); ?>
                                    </div>
                                    <div
                                        class="text-amber-500 text-xs py-2"><?php echo e(__('Bitte verfasse einen ausführlichen und verständlichen Antragstext, damit die Abstimmung über eine mögliche Förderung erfolgen kann.')); ?></div>
                                </x-slot>
                                <div
                                    class="text-amber-500 text-xs py-2"><?php echo e(__('Für Bilder in Markdown verwende bitte z.B. Imgur oder einen anderen Anbieter.')); ?></div>
                                <x-input.simple-mde model="form.description"/>
                                <?php $__errorArgs = ['form.description'];
$__bag = $errors->getBag($__errorProps ?? 'default');
if ($__bag->has($__errorArgs)) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs); ?>
                                    <span class="text-red-500 py-2"><?php echo e($message); ?></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </x-input.group>

                            <x-input.group :for="md5('save')" label="">
                                <x-button secondary :href="route('association.projectSupport')">
                                    <i class="fa fa-thin fa-arrow-left"></i>
                                    <?php echo e(__('Zurück')); ?>
                                </x-button>
                                <x-button primary wire:click="save">
                                    <i class="fa fa-thin fa-save"></i>
                                    <?php echo e(__('Speichern')); ?>
                                </x-button>
                            </x-input.group>
                        </div>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                            Projekt-Unterstützung</h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, die Projekt-Unterstützungen zu bearbeiten.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</x-layouts.app>
