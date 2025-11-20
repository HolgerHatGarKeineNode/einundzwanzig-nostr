<!-- Admin group -->
<div>
    <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                        <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6"
                              aria-hidden="true">•••</span>
        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Admin-Bereich</span>
    </h3>
    <ul class="mt-3">
        {{--<li class="{{ $currentRoute === 'association.elections' ? $isCurrentRouteClass : $isNotCurrentRouteClass }}">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition"
               href="{{ route('association.elections') }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-booth-curtain h-4 w-4"></i>
                    <span
                        class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Einstellungen</span>
                </div>
            </a>
        </li>--}}
        <li class="{{ $currentRoute === 'association.members.admin' ? $isCurrentRouteClass : $isNotCurrentRouteClass }}">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition"
               href="{{ route('association.members.admin') }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-users h-4 w-4"></i>
                    <span
                        class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Mitglieder</span>
                </div>
            </a>
        </li>
    </ul>
</div>
