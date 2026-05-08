<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Acciones Rápidas
        </x-slot>

        <x-slot name="description">
            Ejecute tareas administrativas comunes
        </x-slot>

        <div class="flex flex-wrap gap-3">
            {{ $this->recalcularConfianzaAction }}
            {{ $this->procesarSuscripcionesAction }}
            {{ $this->limpiarCacheAction }}
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
