<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            {{-- ซ้าย: โลโก้ / หน้าแรก --}}
            <div class="flex items-center">
                <a href="{{ route('stations.map') }}"
                    class="text-lg sm:text-xl font-semibold text-gray-800 hover:text-indigo-600">
                    EV Charging
                </a>
            </div>

            <!-- กลาง: ช่องค้นหาแบบยาว อยู่กึ่งกลาง -->
            {{-- (ภายใน navigation.blade.php เฉพาะส่วนกลางฟอร์มค้นหา) --}}
            <div class="flex-1 flex justify-center px-3">
                <form action="{{ route('stations.map') }}" method="GET"
                    class="relative w-full max-w-full sm:max-w-xl lg:max-w-3xl">
                    <input id="q" type="text" name="q" value="{{ request('q') }}" placeholder="ค้นหา..." class="w-full h-9 rounded-full border border-gray-300/70 bg-white/90 shadow-inner
             pl-4 pr-10 text-sm placeholder-gray-400 outline-none
             focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500 transition" autocomplete="off">

                    {{-- ไอคอนแว่น --}}
                    <button type="submit"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600"
                        aria-label="Search">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M21 21l-4.35-4.35m1.1-5.4a6.75 6.75 0 11-13.5 0 6.75 6.75 0 0113.5 0z" />
                        </svg>
                    </button>

                    {{-- กล่องลิสต์ Suggest (ให้ JS เติม) --}}
                    <div id="qSuggest" class="absolute left-0 right-0 mt-2 bg-white text-gray-800 rounded-xl shadow-lg border
                hidden max-h-80 overflow-auto z-50">
                    </div>
                </form>
            </div>


            {{-- ขวา: dropdown หรือ login/register --}}
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                    <div class="flex items-center gap-4">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4
                                                               font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition">
                                    <div>ยินดีต้อนรับคุณ {{ Auth::user()?->name }}</div>
                                    <div class="ms-1">
                                        <svg class="fill-current h-4 w-4" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd"
                                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                clip-rule="evenodd" />
                                        </svg>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <div class="pt-2 pb-3 space-y-1">
                                    <x-responsive-nav-link :href="route('dashboard')"
                                        :active="request()->routeIs('dashboard')">
                                        {{ __('Dashboard') }}
                                    </x-responsive-nav-link>
                                    <x-responsive-nav-link :href="route('stations.index')"
                                        :active="request()->routeIs('stations.*')">
                                        {{ __('Stations') }}
                                    </x-responsive-nav-link>
                                </div>

                                <div class="px-4">
                                    <div class="font-medium text-base text-gray-800">{{ Auth::user()?->name }}</div>
                                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()?->email }}</div>
                                </div>
                                <x-dropdown-link :href="route('profile.edit')">{{ __('Profile') }}</x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('Log Out') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endauth

                @guest
                    <div class="flex items-center gap-4">
                        <a href="{{ route('login') }}" class="">{{ __('Log in') }}</a>
                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="">
                                {{ __('Register') }}
                            </a>
                        @endif
                    </div>
                @endguest
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500
                 hover:bg-gray-100 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        @auth
            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Dashboard') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('stations.index')" :active="request()->routeIs('stations.*')">
                    {{ __('Stations') }}
                </x-responsive-nav-link>
            </div>

            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()?->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()?->email }}</div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">{{ __('Profile') }}</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('Log Out') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth

        @guest
            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                    {{ __('Log in') }}
                </x-responsive-nav-link>
                @if (Route::has('register'))
                    <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                        {{ __('Register') }}
                    </x-responsive-nav-link>
                @endif
            </div>
        @endguest
    </div>
</nav>