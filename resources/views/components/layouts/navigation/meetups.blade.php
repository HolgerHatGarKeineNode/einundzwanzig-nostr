<!-- Meetups group -->
<div>
    <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                        <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6"
                              aria-hidden="true">•••</span>
        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Meetups</span>
    </h3>
    <ul class="mt-3">
        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition" href="{{ route('meetups.worldmap') }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-map h-6 w-6"></i>
                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">World Map</span>
                </div>
            </a>
        </li>
        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition" href="{{ route('meetups.grid') }}">
                <div class="flex items-center">
                    <i class="fa-sharp-duotone fa-solid fa-handshake-angle h-6 w-6"></i>
                    <span class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Alle Meetups</span>
                </div>
            </a>
        </li>
    </ul>
</div>
