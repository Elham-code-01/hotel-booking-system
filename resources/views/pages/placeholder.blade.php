<x-app-layout>
    <x-slot name="header">{{ $title }}</x-slot>

    <x-page-header :title="$title" sub="This page is built in one of the next steps." />

    <x-panel title="Coming soon">
        <x-empty-state title="Not built yet" icon="…">
            The menu, access rules and layout already work. The content of this page comes in its own step.
        </x-empty-state>
    </x-panel>
</x-app-layout>
