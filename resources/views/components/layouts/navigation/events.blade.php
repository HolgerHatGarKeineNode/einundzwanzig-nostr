<!-- Events group -->
<div>
    <h3 class="text-xs uppercase text-gray-400 dark:text-gray-500 font-semibold pl-3">
                        <span class="hidden lg:block lg:sidebar-expanded:hidden 2xl:hidden text-center w-6"
                              aria-hidden="true">•••</span>
        <span class="lg:hidden lg:sidebar-expanded:block 2xl:block">Events</span>
    </h3>
    <ul class="mt-3">
        <!-- Browse -->
        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0"
            x-data="{ open: false }">
            <a class="block text-gray-800 dark:text-gray-100 truncate transition" href="#0"
               @click.prevent="open = !open; sidebarExpanded = true">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fa-sharp-duotone fa-solid fa-calendar h-4 w-4"></i>
                        <span
                            class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Browse</span>
                    </div>
                    <!-- Icon -->
                    <div
                        class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                        <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500"
                             :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z"/>
                        </svg>
                    </div>
                </div>
            </a>
            <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                <ul class="pl-8 mt-1" :class="open ? 'block!' : 'hidden'">
                    <li class="mb-1 last:mb-0">
                        <a class="block text-amber-500 transition truncate" href="index.html">
                                            <span
                                                class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Main</span>
                        </a>
                    </li>
                    <li class="mb-1 last:mb-0">
                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate"
                           href="analytics.html">
                                            <span
                                                class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Analytics</span>
                        </a>
                    </li>
                    <li class="mb-1 last:mb-0">
                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate"
                           href="fintech.html">
                                            <span
                                                class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Fintech</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <!-- Manage -->
        <li class="pl-4 pr-3 py-2 rounded-lg mb-0.5 last:mb-0" x-data="{ open: false }">
            <a class="block text-gray-800 dark:text-gray-100 hover:text-gray-900 dark:hover:text-white truncate transition"
               href="#0" @click.prevent="open = !open; sidebarExpanded = true">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fa-sharp-duotone fa-solid fa-pencil h-4 w-4"></i>
                        <span
                            class="text-sm font-medium ml-4 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Manage</span>
                    </div>
                    <!-- Icon -->
                    <div
                        class="flex shrink-0 ml-2 lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">
                        <svg class="w-3 h-3 shrink-0 ml-1 fill-current text-gray-400 dark:text-gray-500"
                             :class="open ? 'rotate-180' : 'rotate-0'" viewBox="0 0 12 12">
                            <path d="M5.9 11.4L.5 6l1.4-1.4 4 4 4-4L11.3 6z"/>
                        </svg>
                    </div>
                </div>
            </a>
            <div class="lg:hidden lg:sidebar-expanded:block 2xl:block">
                <ul class="pl-8 mt-1 hidden" :class="open ? 'block!' : 'hidden'">
                    <li class="mb-1 last:mb-0">
                        <a class="block text-gray-500/90 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition truncate"
                           href="customers.html">
                                            <span
                                                class="text-sm font-medium lg:opacity-0 lg:sidebar-expanded:opacity-100 2xl:opacity-100 duration-200">Manage cities</span>
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</div>
