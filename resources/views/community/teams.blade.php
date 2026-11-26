<x-app-layout>
    @push('title', __('Teams'))

    <div class="col-span-12 lg:col-span-9 lg:w-[96%]">
        <div class="flex flex-col gap-y-4">
            @forelse ($employees as $employee)
                <x-content.staff-content-section :badge="$employee->badge" :color="$employee->staff_color">
                    <x-slot:title>
                        {{ $employee->rank_name }}
                    </x-slot:title>

                    <x-slot:under-title>
                        {{ $employee->job_description }}
                    </x-slot:under-title>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($employee->users as $staff)
                            <x-community.staff-card :user="$staff" />
                        @endforeach
                    </div>

                    @if (count($employee->users) === 0)
                        <div class="text-center dark:text-gray-400">
                            {{ __('We currently have no members in this team') }}
                        </div>
                    @endif
                </x-content.staff-content-section>
            @empty
                <x-content.content-card icon="chat-icon" classes="border dark:border-gray-900">
                    <x-slot:title>
                        {{ __('No teams found') }}
                    </x-slot:title>

                    <x-slot:under-title>
                        {{ __('Please check back later.') }}
                    </x-slot:under-title>

                    <div class="px-2 text-sm space-y-4 dark:text-gray-200">
                        <p>{{ __('There are no teams to display right now.') }}</p>
                    </div>
                </x-content.content-card>
            @endforelse
        </div>
    </div>

    <div class="col-span-12 lg:col-span-3 lg:w-[110%] space-y-4 lg:-ml-[32px]">
        <x-content.content-card icon="chat-icon" classes="border dark:border-gray-900">
            <x-slot:title>
                {{ __(':hotel teams', ['hotel' => setting('hotel_name')]) }}
            </x-slot:title>

            <x-slot:under-title>
                {{ __('About the :hotel teams', ['hotel' => setting('hotel_name')]) }}
            </x-slot:under-title>

            <div class="px-2 text-sm space-y-4 dark:text-gray-200">
                <p>
                    {{ __('Our community teams bring players together to host events, help others, and make :hotel a fun place to be.', ['hotel' => setting('hotel_name')]) }}
                </p>

                <p>
                    {{ __('Each team has its own role in the community. Meet the members above and discover how they contribute to :hotel!', ['hotel' => setting('hotel_name')]) }}
                </p>
            </div>
        </x-content.content-card>

        <x-content.content-card icon="chat-icon" classes="border dark:border-gray-900">
            <x-slot:title>
                {{ __('Apply for teams') }}
            </x-slot:title>

            <x-slot:under-title>
                {{ __('How to join a team') }}
            </x-slot:under-title>

            <div class="px-2 text-sm space-y-4 dark:text-gray-200">
                <p>
                    {{ __('We open team applications periodically. If you would like to help out at :hotel, keep an eye on the news for opportunities to join a team.', ['hotel' => setting('hotel_name')]) }}
                </p>

                <p>
                    {!! __(
                        'Visit the :startTag Team application page :endTag to see which teams are currently recruiting.',
                        ['startTag' => '<a href="' . route('team-applications.index') . '" class="underline">', 'endTag' => '</a>'],
                    ) !!}
                </p>
            </div>
        </x-content.content-card>
    </div>
</x-app-layout>
