<nav x-data="{ open: false }" style="background: linear-gradient(to right, #7c3aed, #8b5cf6, #a78bfa);
            border-bottom: 1px solid #6d28d9;
            box-shadow: 0 3px 10px rgba(109, 40, 217, 0.3);">

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">

            <div class="flex items-center">
                <a href="
    @auth
        {{ auth()->user()->role->name === 'admin'
        ? route('admin.dashboard')
        : route('user.dashboard') }}
    @else
        {{ route('welcome') }}
    @endauth
" class="flex items-center gap-2 hover:opacity-90 transition">

                    <img src="{{ asset('images/ev-logo.png') }}" alt="EV Logo"
                        class="h-9 w-auto transition-transform duration-300 group-hover:scale-105]" />
                    <span >
                                
                    </span>
                </a>
            </div>




            <!-- กลาง: ช่องค้นหาแบบยาว อยู่กึ่งกลาง -->
            {{-- (ภายใน navigation.blade.php เฉพาะส่วนกลางฟอร์มค้นหา) --}}
            <div class="flex-1 flex justify-center px-3">
                <div
                    class="relative w-full max-w-full sm:max-w-xl lg:max-w-3xl rounded-2xl bg-slate-900/95 shadow-lg shadow-indigo-900/30 border border-slate-700/70">
                    <form action="{{ route('stations.map') }}" method="GET" class="relative flex w-full">
                        <input id="q" type="text" name="q" value="{{ request('q') }}"
                            placeholder="ค้นหาหรือเตะในช่องเพื่อดูลิสรายการสถานีบริการ"
                            class="w-full h-11 rounded-l-2xl bg-slate-900/95 border border-slate-700/70 text-sm text-slate-100 placeholder-slate-400 pl-4 pr-4 outline-none transition focus:border-indigo-400 focus:ring-2 focus:ring-indigo-500/40"
                            autocomplete="off">
                        <button type="submit"
                            class="h-11 px-4 rounded-r-2xl bg-gradient-to-br from-violet-500 to-indigo-500 text-white font-medium text-sm flex items-center justify-center transition hover:from-violet-600 hover:to-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500/60 focus:ring-offset-2 focus:ring-offset-slate-900">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M21 21l-4.35-4.35M16.65 9.75a6.9 6.9 0 11-13.8 0 6.9 6.9 0 0113.8 0z" />
                            </svg>
                        </button>
                    </form>

                    {{-- กล่องลิสต์ Suggest (ให้ JS เติม) --}}
                    <div id="qSuggest"
                        class="absolute left-0 right-0 mt-2 bg-white text-gray-800 rounded-xl shadow-lg border hidden max-h-80 overflow-auto z-50">
                    </div>
                </div>
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

                                <div class="px-4">
                                    <div class="font-medium text-base text-gray-800">{{ Auth::user()?->name }}</div>
                                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()?->email }}</div>
                                </div>
                                <div class="border-t border-gray-200 mt-3 pt-3"></div>
                                <x-dropdown-link :href="route('profile.edit')">{{ __('โปรไฟล์ผู้ใช้') }}</x-dropdown-link>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();">
                                        {{ __('ออกจากระบบ') }}
                                    </x-dropdown-link>
                                </form>
                            </x-slot>
                        </x-dropdown>
                    </div>
                @endauth

                @guest
                    <div class="flex items-center gap-3">
                        <a href="{{ route('login') }}"
                            class="px-5 py-2.5 rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 text-white font-medium shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-purple-600 transition-all duration-300">
                            🔐 เข้าสู่ระบบ
                        </a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}"
                                class="px-5 py-2.5 rounded-full bg-gradient-to-r from-indigo-600 via-purple-600 to-indigo-700 text-white font-medium shadow-md hover:shadow-lg hover:from-indigo-500 hover:to-purple-600 transition-all duration-300">
                                ✨ ลงทะเบียน
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

            <div class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800">{{ Auth::user()?->name }}</div>
                    <div class="font-medium text-sm text-gray-500">{{ Auth::user()?->email }}</div>
                </div>
                <div class="border-t border-gray-200 mt-3 pt-3"></div>
                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">{{ __('โปรไฟล์ผู้ใช้') }}</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('ออกจากระบบ') }}
                        </x-responsive-nav-link>
                    </form>
                </div>
            </div>
        @endauth

        @guest
            <div class="pt-2 pb-3 space-y-1">
                <x-responsive-nav-link :href="route('login')" :active="request()->routeIs('login')">
                    {{ __('เข้าสู่ระบบ') }}
                </x-responsive-nav-link>
                @if (Route::has('register'))
                    <x-responsive-nav-link :href="route('register')" :active="request()->routeIs('register')">
                        {{ __('ลงทะเบียน') }}
                    </x-responsive-nav-link>
                @endif
            </div>
        @endguest
    </div>
</nav>