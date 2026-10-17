<x-app-layout>
    <x-slot name="header">Operations overview</x-slot>

    <x-page-header :title="'Good day, '.auth()->user()->name" sub="A clear view of today’s hotel operations." />

    <div class="grid stats-grid">
        <x-stat-card label="Active hotels" :value="$hotels" :note="$locations ?: 'No hotel yet'" mark="H" />
        <x-stat-card label="Registered rooms" :value="$rooms" :note="$beds.' beds in total'" mark="R" />
        <x-stat-card label="Arrivals today" :value="$arrivals" note="Rooms checking in" mark="→" />
        <x-stat-card label="Departures today" :value="$departures" note="Rooms checking out" mark="←" />
    </div>

    <x-panel title="Hotel occupancy">
        <x-empty-state title="Nothing to show yet" icon="▥">
            Occupancy of every hotel appears on the Hotels page. More charts come after rooms and bookings are added in the next steps.
        </x-empty-state>
    </x-panel>
</x-app-layout>
