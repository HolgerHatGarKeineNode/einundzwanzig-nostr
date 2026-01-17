<x-layouts.app
    :seo="new SEOData(title: 'Unterstützung für: ' .  $projectProposal->name, description: $projectProposal->accepted ? 'Wurde mit ' . number_format($projectProposal->sats_paid, 0, ',', '.') . ' Satoshis unterstützt!' :str($projectProposal->description)->limit(100, '...', true), image: $projectProposal->getFirstMediaUrl('main'))">
    <div>
        <?php if($projectProposal->accepted || $isAllowed): ?>
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full">

                <!-- Page content -->
                <div class="max-w-5xl mx-auto flex flex-col lg:flex-row lg:space-x-8 xl:space-x-16">

                    <!-- Content -->
                    <div>
                        <div class="mb-6">
                            <a class="btn-sm px-3 bg-white dark:bg-gray-800 border-gray-200 dark:border-gray-700/60 hover:border-gray-300 dark:hover:border-gray-600 text-gray-800 dark:text-gray-300"
                               href="<?php echo e(route('association.projectSupport')); ?>"
                            >
                                <svg class="fill-current text-gray-400 dark:text-gray-500 mr-2" width="7" height="12"
                                     viewBox="0 0 7 12">
                                    <path d="M5.4.6 6.8 2l-4 4 4 4-1.4 1.4L0 6z"></path>
                                </svg>
                                <span>Zurück zur Übersicht</span>
                            </a>
                        </div>
                        <div class="text-sm font-semibold text-violet-500 uppercase mb-2">
                            <?php echo e($projectProposal->created_at->translatedFormat('d.m.Y')); ?>
                        </div>
                        <header class="mb-4">
                            <!-- Title -->
                            <h1 class="text-2xl md:text-3xl text-gray-800 dark:text-gray-100 font-bold mb-2">
                                <?php echo e($projectProposal->name); ?>
                            </h1>
                            <x-markdown>
                                <?php echo $projectProposal->description; ?>
                            </x-markdown>
                        </header>

                        <div class="space-y-3 sm:flex sm:items-center sm:justify-between sm:space-y-0 mb-6">
                            <!-- Author -->
                            <div class="flex items-center sm:mr-4">
                                <a class="block mr-2 shrink-0" href="#0">
                                    <img class="rounded-full"
                                         src="<?php echo e($projectProposal->einundzwanzigPleb->profile?->picture ?? asset('einundzwanzig-alpha.jpg')); ?>"
                                         width="32" height="32" alt="User 04">
                                </a>
                                <div class="text-sm whitespace-nowrap">Eingereicht von
                                    <div
                                        class="font-semibold text-gray-800 dark:text-gray-100"><?php echo e($projectProposal->einundzwanzigPleb?->profile->name ?? str($projectProposal->einundzwanzigPleb->npub)->limit(32)); ?></div>
                                </div>
                            </div>
                            <!-- Right side -->
                            <div class="flex flex-wrap items-center sm:justify-end space-x-2">
                                <!-- Tags -->
                                <div
                                    class="text-xs inline-flex items-center font-medium border border-gray-200 dark:border-gray-700/60 text-gray-600 dark:text-gray-400 rounded-full text-center px-2.5 py-1">
                                    <a target="_blank" href="<?php echo e($projectProposal->website); ?>"><span>Webseite</span></a>
                                </div>
                                <div
                                    class="text-xs inline-flex font-medium uppercase bg-green-500/20 text-green-700 rounded-full text-center px-2.5 py-1">
                                    <?php echo e(number_format($projectProposal->support_in_sats, 0, ',', '.')); ?> Sats
                                </div>
                            </div>
                        </div>

                        <figure class="mb-6">
                            <img class="rounded-sm h-48" src="<?php echo e($projectProposal->getFirstMediaUrl('main')); ?>"
                                 alt="Picture">
                        </figure>

                        <hr class="my-6 border-t border-gray-100 dark:border-gray-700/60">

                    </div>

                    <?php if($isAllowed && !$projectProposal->accepted): ?>
                        <!-- Sidebar -->
                        <div class="space-y-4">

                            <!-- 1st block -->
                            <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                                <?php if(!$ownVoteExists): ?>
                                    <div class="space-y-2">
                                        <button
                                            wire:click="approve"
                                            class="btn w-full bg-gray-900 text-gray-100 hover:bg-gray-800 dark:bg-gray-100 dark:text-gray-800 dark:hover:bg-white">
                                            <i class="fill-current shrink-0 fa-sharp-duotone fa-solid fa-thumbs-up"></i>
                                            <span class="ml-1">Zustimmen</span>
                                        </button>
                                        <button
                                            wire:click="notApprove"
                                            class="btn w-full bg-red-900 text-red-100 hover:bg-red-800 dark:bg-red-100 dark:text-red-800 dark:hover:bg-red-400">
                                            <i class="fill-current shrink-0 fa-sharp-duotone fa-solid fa-thumbs-down"></i>
                                            <span class="ml-1">Ablehnen</span>
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <div class="space-y-2">
                                        <p>Du hast bereits abgestimmt.</p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- 2nd block -->
                            <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                                <div class="flex justify-between space-x-1 mb-5">
                                    <div class="text-sm text-gray-800 dark:text-gray-100 font-semibold">
                                        Zustimmungen des Vorstands (<?php echo e(count($boardVotes->where('value', 1))); ?>)
                                    </div>
                                </div>
                            </div>

                            <!-- 2nd block -->
                            <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                                <div class="flex justify-between space-x-1 mb-5">
                                    <div class="text-sm text-gray-800 dark:text-gray-100 font-semibold">
                                        Ablehnungen des Vorstands (<?php echo e(count($boardVotes->where('value', 0))); ?>)
                                    </div>
                                </div>
                            </div>

                            <!-- 3rd block -->
                            <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                                <div class="flex justify-between space-x-1 mb-5">
                                    <div class="text-sm text-gray-800 dark:text-gray-100 font-semibold">
                                        Zustimmungen der übrigen Mitglieder (<?php echo e(count($otherVotes->where('value', 1))); ?>)
                                    </div>
                                </div>
                            </div>

                            <!-- 3rd block -->
                            <div class="bg-white dark:bg-gray-800 p-5 shadow-sm rounded-xl lg:w-72 xl:w-80">
                                <div class="flex justify-between space-x-1 mb-5">
                                    <div class="text-sm text-gray-800 dark:text-gray-100 font-semibold">
                                        Ablehnungen der übrigen Mitglieder (<?php echo e(count($otherVotes->where('value', 0))); ?>)
                                    </div>
                                </div>
                            </div>

                        </div>
                    <?php endif; ?>

                </div>

            </div>
        <?php else: ?>
            <div class="px-4 sm:px-6 lg:px-8 py-8 w-full max-w-9xl mx-auto">
                <div class="bg-white dark:bg-[#1B1B1B] shadow overflow-hidden sm:rounded-lg">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg font-medium leading-6 text-gray-900 dark:text-gray-200">
                            Projekt-Unterstützung
                        </h3>
                        <p class="mt-1 max-w">
                            Du bist nicht berechtigt, die Projekt-Unterstützungen einzusehen.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</x-layouts.app>
