<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Actividad Reciente
        </x-slot>

        <div class="space-y-3">
            @forelse($this->getActividades() as $actividad)
                <div class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800">
                    <div class="flex-shrink-0">
                        <x-filament::icon
                            :icon="$actividad['icono']"
                            class="w-5 h-5 text-{{ $actividad['color'] }}-500"
                        />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {{ $actividad['texto'] }}
                        </p>
                        <p class="text-xs text-gray-500">
                            {{ $actividad['tiempo'] }}
                        </p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 text-center py-4">
                    Sin actividad reciente
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>