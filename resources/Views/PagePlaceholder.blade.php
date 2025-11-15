{{--
/**
 * @fileoverview Plantilla genérica para páginas en construcción dentro del dashboard.
 */
--}}

<x-layouts.dashboard-layout :meta="$meta ?? []">
    <x-ui.breadcrumb :title="$title" :items="$breadcrumbItems" />
</x-layouts.dashboard-layout>
