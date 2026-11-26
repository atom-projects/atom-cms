<nav
    class="nav-header relative z-30"
    x-data="{
        menuOpen: false,
        closeMenu() {
            if (!this.menuOpen || this.$refs.menuToggle.offsetParent === null) {
                return
            }

            this.menuOpen = false

            if (this.$refs.menu.contains(document.activeElement)) {
                this.$refs.menuToggle.focus()
            }
        }
    }"
    x-on:keydown.escape.prevent="closeMenu()"
    x-on:click.outside="closeMenu()"
    x-on:focusin.window="!$el.contains($event.target) && closeMenu()"
    x-on:resize.window="if (!menuOpen && $refs.menuToggle.offsetParent !== null && $refs.menu.contains(document.activeElement)) $refs.menuToggle.focus()"
>
    <div class="max-w-7xl w-full flex flex-wrap justify-between items-center min-h-[60px] px-5 lg:h-[120px] xl:px-0">
        <a href="{{ route('welcome') }}" class="hidden lg:block transition duration-300 ease-in-out hover:scale-105">
            <img src="{{ setting('cms_logo') }}" alt="">
        </a>

        <button
            x-ref="menuToggle"
            x-on:click="menuOpen ? closeMenu() : menuOpen = true"
            :aria-expanded="menuOpen"
            aria-expanded="false"
            aria-controls="dusk-navigation-menu"
            aria-label="{{ __('Open main menu') }}"
            type="button"
            class="ml-auto py-4 text-white lg:hidden"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>

        <div id="dusk-navigation-menu" x-ref="menu" x-show="menuOpen" style="display: none;" class="flex w-full flex-col gap-3 pb-4 text-white lg:flex! lg:w-auto lg:flex-row lg:items-center lg:gap-x-14 lg:pb-0">
            <x-navigation.dropdown icon="community_icon.png" :route-group="['staff.*', 'teams.*', 'team-applications.*', 'staff-applications.*', 'values.*', 'help-center.*', 'photos.*']" :uppercase="true" :responsive="true">
                {{ __('Community') }}

                <x-slot:children>
                    <x-navigation.dropdown-child :route="route('staff.index')">
                        {{ __('Staff') }}
                    </x-navigation.dropdown-child>

                    <x-navigation.dropdown-child :route="route('teams.index')">
                        {{ __('Teams') }}
                    </x-navigation.dropdown-child>

                    <x-navigation.dropdown-child :route="route('team-applications.index')">
                        {{ __('Team applications') }}
                    </x-navigation.dropdown-child>

                    <x-navigation.dropdown-child :route="route('staff-applications.index')">
                        {{ __('Staff applications') }}
                    </x-navigation.dropdown-child>

                    <x-navigation.dropdown-child :route="route('values.index')">
                        {{ __('Rare values') }}
                    </x-navigation.dropdown-child>

                    <x-navigation.dropdown-child :route="route('help-center.index')">
                        {{ __('Help center') }}
                    </x-navigation.dropdown-child>

                    <x-navigation.dropdown-child :route="route('photos.index')">
                        {{ __('Photos') }}
                    </x-navigation.dropdown-child>
                </x-slot:children>
            </x-navigation.dropdown>

            <x-navigation.link :route="route('leaderboard.index')" icon="leaderboard_icon.png" route-group="leaderboard.*">
                {{ __('Leaderboard') }}
            </x-navigation.link>

            <x-navigation.link :route="route('article.index')" icon="news_icon.png" route-group="article.*">
                {{ __('News') }}
            </x-navigation.link>

            <x-navigation.link :route="route('shop.index')" icon="store_icon.png" route-group="shop.*">
                {{ __('Store') }}
            </x-navigation.link>

            <x-navigation.dropdown icon="home_icon.png" :href="auth()->check() ? route('me.show') : route('welcome')" :route-group="['me.*', 'home.*', 'draw-badge', 'settings.*', 'welcome', 'register*']" child-classes="min-w-[150px] lg:left-auto lg:right-0" :uppercase="true" :responsive="true">
                {{ __('Home') }}

                <x-slot:children>
                    @auth
                        <x-navigation.dropdown-child :route="route('home.show', Auth::user()->username)">
                            {{ __('My Home') }}
                        </x-navigation.dropdown-child>

                        <x-navigation.dropdown-child :route="route('draw-badge')">
                            {{ __('Badge Drawer') }}
                        </x-navigation.dropdown-child>

                        <x-navigation.dropdown-child :route="route('settings.account.show')">
                            {{ __('Account settings') }}
                        </x-navigation.dropdown-child>

                        <button type="submit" form="dusk-logout-form" class="dropdown-item">
                            {{ __('Logout') }}
                        </button>

                        <form id="dusk-logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                            @csrf
                        </form>
                    @endauth

                    @guest
                        <x-navigation.dropdown-child :route="route('login')">
                            {{ __('Login') }}
                        </x-navigation.dropdown-child>

                        <x-navigation.dropdown-child :route="route('register')">
                            {{ __('Register') }}
                        </x-navigation.dropdown-child>
                    @endguest
                </x-slot:children>
            </x-navigation.dropdown>
        </div>
    </div>
</nav>
