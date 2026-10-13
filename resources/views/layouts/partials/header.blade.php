{{-- App header: mobile menu, global search, notifications, profile --}}
{{-- Uses body Alpine state: openDropdown, openDrawer; expects $authUser, $can --}}
<header class="app-header sticky top-0 z-40 shrink-0 flex items-center gap-2 sm:gap-3 px-3 sm:px-4 lg:px-6 py-1.5 border-b border-white/10 shadow-sm">
    <button type="button"
            @click="openDrawer($event)"
            class="lg:hidden shrink-0 p-2.5 rounded-lg hover:bg-white/10 transition-colors text-white/90"
            aria-label="Open menu"
            :aria-expanded="sidebarOpen.toString()">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
    </button>

    @if ($authUser)
        @php
            $roleName = $authUser->role?->role_name ?? 'User';
            $username = (string) $authUser->username;
            $initials = mb_strtoupper(mb_substr($username, 0, 1));
            $notifications = Cache::remember(
                "header_notifications_{$authUser->id}",
                now()->addMinute(),
                fn () => $authUser->notifications()->latest()->take(5)->get(),
            );
            $unreadCount = Cache::remember(
                "header_unread_count_{$authUser->id}",
                now()->addMinute(),
                fn () => $authUser->notifications()->whereNull('read_at')->count(),
            );
        @endphp

        {{-- Global Search (sm+; mobile search lives in the drawer) --}}
        <div x-data="globalSearch({ allowHouseholds: {{ $can('household') ? 'true' : 'false' }} })"
             @focus="openDropdown = null"
             class="relative hidden sm:block min-w-0">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-white/50" aria-hidden="true"></i>
                <input type="search" x-model="query" @input.debounce.300ms="search()" @click.away="close()"
                       @keydown.escape="close()" @keydown.arrow-down.prevent="focusNext()" @keydown.arrow-up.prevent="focusPrev()" @keydown.enter.prevent="selectActive()"
                       placeholder="Search patients, households..."
                       class="w-52 lg:w-72 rounded-lg border border-white/20 bg-white/10 pl-9 pr-3 py-2 text-sm text-white placeholder-white/50 focus:outline-none focus:ring-2 focus:ring-white/30 transition-all"
                       role="combobox" aria-autocomplete="list" aria-haspopup="listbox" :aria-expanded="(results.length > 0).toString()"
                       aria-label="Search patients and households">
            </div>
            <div x-show="results.length > 0" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 translate-y-1" x-transition:enter-end="opacity-100 translate-y-0"
                 class="absolute top-full left-0 mt-2 w-full rounded-xl border border-border shadow-lg bg-surface-elevated z-50 max-h-80 overflow-y-auto"
                 role="listbox">
                    <template x-for="(item, index) in results" :key="item.url">
                        <a :href="item.url" class="flex items-center gap-3 px-4 py-2.5 text-sm text-ink hover:bg-black/5 transition-colors border-b border-border last:border-0"
                           :class="{ 'bg-teal-soft': index === activeIndex }" role="option" :aria-selected="(index === activeIndex).toString()">
                            <span class="flex-1 min-w-0">
                                <span x-html="highlightMatch(item.label, query)" class="block truncate"></span>
                                <span x-show="item.subtext" x-text="item.subtext" class="block text-xs text-ink-muted truncate"></span>
                            </span>
                        </a>
                    </template>
            </div>
        </div>

        <div class="ml-auto flex items-center gap-1.5 sm:gap-2">
            {{-- Theme toggle: light/dark, persisted per browser (bhcis-theme) --}}
            <button type="button"
                    @click="$store.theme.toggle()"
                    class="p-2.5 rounded-lg hover:bg-white/10 transition-colors text-white/90 hover:text-white"
                    :aria-label="$store.theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                    :aria-pressed="$store.theme.isDark.toString()"
                    :title="$store.theme.isDark ? 'Switch to light mode' : 'Switch to dark mode'">
                <i class="fa-solid fa-moon text-lg" x-show="!$store.theme.isDark" x-cloak aria-hidden="true"></i>
                <i class="fa-solid fa-sun text-lg" x-show="$store.theme.isDark" x-cloak aria-hidden="true"></i>
            </button>

            {{-- Notifications --}}
            <div class="relative">
                <button type="button"
                        @click="openDropdown = openDropdown === 'notifications' ? null : 'notifications'"
                        @click.away="openDropdown = openDropdown === 'notifications' ? null : openDropdown"
                        :aria-expanded="(openDropdown === 'notifications').toString()"
                        aria-haspopup="true"
                        aria-controls="headerNotificationsMenu"
                        class="relative p-2.5 rounded-lg hover:bg-white/10 transition-colors text-white/90 hover:text-white">
                    <i class="fa-solid fa-bell text-lg" aria-hidden="true"></i>
                    <span class="sr-only">Notifications</span>
                    @if ($unreadCount > 0)
                        <span class="absolute top-1 right-1 inline-flex items-center justify-center h-5 w-5 text-[10px] font-bold rounded-full bg-danger text-white" aria-hidden="true">
                            {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                        </span>
                    @endif
                </button>

                <div x-show="openDropdown === 'notifications'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform translate-y-1"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform translate-y-1"
                     id="headerNotificationsMenu"
                     role="menu"
                     class="absolute right-0 mt-3 w-80 rounded-xl border border-border shadow-md bg-surface-elevated z-50"
                     style="display: none;">

                    <div class="px-4 py-3 border-b border-border">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-ink">Notifications</h3>
                            @if ($unreadCount > 0)
                                <form action="{{ route('notifications.mark-all-read') }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs font-medium text-primary hover:opacity-70 transition-opacity">
                                        Mark all as read
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="max-h-96 overflow-y-auto">
                        @forelse ($notifications as $notification)
                            <div class="px-4 py-3 border-b border-border hover:bg-black/5 transition-colors {{ is_null($notification->read_at) ? 'bg-teal-soft' : '' }}">
                                <div class="flex gap-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-ink">
                                            @if (! empty($notification->data['url']))
                                                <a href="{{ $notification->data['url'] }}" @click="openDropdown = null" class="hover:text-primary transition-colors">{{ $notification->data['title'] ?? 'Notification' }}</a>
                                            @else
                                                {{ $notification->data['title'] ?? 'Notification' }}
                                            @endif
                                        </p>
                                        <p class="text-xs text-ink-muted mt-1">{{ $notification->data['message'] ?? '' }}</p>
                                        <p class="text-xs text-ink-subtle mt-2">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                    @if (is_null($notification->read_at))
                                        <form action="{{ route('notifications.mark-read', $notification->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-1.5 rounded hover:bg-black/10 transition-colors" title="Mark as read">
                                                <i class="fa-solid fa-check text-xs text-primary" aria-hidden="true"></i>
                                                <span class="sr-only">Mark as read</span>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="px-4 py-8 text-center">
                                <i class="fa-solid fa-bell-slash text-2xl text-ink-subtle opacity-50 mb-2" aria-hidden="true"></i>
                                <p class="text-sm text-ink-muted">No notifications yet</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="px-4 py-3 border-t border-border">
                        <a href="{{ route('notifications.index') }}" @click="openDropdown = null" class="block text-center text-sm font-medium text-primary hover:opacity-75 transition-opacity">
                            View all notifications
                        </a>
                    </div>
                </div>
            </div>

            {{-- Profile --}}
            <div class="relative">
                <button type="button"
                        @click="openDropdown = openDropdown === 'profile' ? null : 'profile'"
                        @click.away="openDropdown = openDropdown === 'profile' ? null : openDropdown"
                        :aria-expanded="(openDropdown === 'profile').toString()"
                        aria-haspopup="true"
                        aria-controls="headerProfileMenu"
                        class="flex items-center gap-1.5 rounded-lg p-1.5 pr-2 hover:bg-white/10 transition-colors text-white">
                    @if ($authUser->profile_photo_path)
                        <img src="/storage/{{ $authUser->profile_photo_path }}" alt="" class="h-8 w-8 rounded-full object-cover ring-1 ring-white/25">
                    @else
                        <span class="h-8 w-8 rounded-full flex items-center justify-center text-xs font-semibold bg-white/20 text-white ring-1 ring-white/25" aria-hidden="true">
                            {{ $initials }}
                        </span>
                    @endif>
                    <span class="sr-only">{{ ucwords($username) }} - {{ $roleName }}</span>
                    <svg class="w-3.5 h-3.5 text-white/70" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M6 9l6 6 6-6"></path>
                    </svg>
                </button>

                <div x-show="openDropdown === 'profile'"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 transform translate-y-1"
                     x-transition:enter-end="opacity-100 transform translate-y-0"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 transform translate-y-0"
                     x-transition:leave-end="opacity-0 transform translate-y-1"
                     id="headerProfileMenu"
                     role="menu"
                     class="absolute right-0 mt-3 w-56 rounded-xl border border-border shadow-md bg-surface-elevated z-50"
                     style="display: none;">

                    <div class="px-4 py-3 text-xs border-b border-border text-ink-muted">
                        <div class="font-semibold text-ink text-sm">{{ ucwords($username) }}</div>
                        <div class="mt-0.5">{{ $roleName }}</div>
                    </div>

                    <div class="p-2 space-y-1">
                        <a href="{{ route('profile.show') }}" role="menuitem" class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-black/5 text-ink">
                            My Profile
                        </a>
                        @if ($authUser->hasPermission('users'))
                            <a href="{{ route('profile.settings') }}" role="menuitem" class="block px-3 py-2.5 rounded-lg text-sm font-medium transition-colors duration-200 hover:bg-black/5 text-ink">
                                Session Settings
                            </a>
                        @endif
                    </div>

                    <div class="p-3 border-t border-border">
                        <form id="headerLogoutForm" action="{{ route('logout') }}" method="POST" class="w-full">
                            @csrf
                            <button type="submit" class="w-full inline-flex items-center justify-center rounded-lg text-sm font-semibold transition-all duration-200 hover:bg-black/5 active:scale-[0.98] border border-border text-ink py-2 bg-transparent">
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</header>
