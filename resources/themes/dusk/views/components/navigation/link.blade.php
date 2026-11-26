@props(['route', 'icon', 'routeGroup'])

<a href="{{ $route }}" @class(['flex flex-col gap-1 lg:items-center transition ease-in-out hover:text-[#ac93da]', 'active' => request()->routeIs($routeGroup)])>
    <img class="icon hidden lg:block" src="{{ asset(sprintf('/assets/images/dusk/%s', $icon)) }}" alt="">
    {{ $slot }}
</a>
