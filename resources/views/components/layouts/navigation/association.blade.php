<!-- Association group -->
<div>
    <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                        <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6"
                              aria-hidden="true">•••</span>
        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Verein</span>
    </h3>
    <ul class="mt-3">
        <li class="{{ $currentRoute === 'association.profile' ? $isCurrentRouteClass : $isNotCurrentRouteClass }}">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition" href="{{ route('association.profile') }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-id-card-clip h-6 w-6"></i>
                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Meine Mitgliedschaft</span>
                </div>
            </a>
        </li>
        <li class="{{ $currentRoute === 'association.election' ? $isCurrentRouteClass : $isNotCurrentRouteClass }}">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition" href="{{ route('association.election', ['election' => date('Y')]) }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-check-to-slot h-6 w-6"></i>
                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Vorstand</span>
                </div>
            </a>
        </li>
        <li class="{{ $currentRoute === 'association.elections' ? $isCurrentRouteClass : $isNotCurrentRouteClass }}">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition" href="{{ route('association.elections') }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-booth-curtain h-6 w-6"></i>
                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Wahlen</span>
                </div>
            </a>
        </li>
    </ul>
</div>
