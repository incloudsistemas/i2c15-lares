<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section>
        <div class="flex items-center justify-between gap-x-3 py-0">
            <div class="flex items-center gap-x-2">
                <img src="{{ asset('images/app-logo.png') }}" width="45" title="Lares" alt="Lares" />
                <span class="text-sm font-medium tracking-wider text-gray-500 dark:text-gray-400">
                    Lares <br />
                    <span class="text-xs">Site e CRM Imobiliário</span>
                </span>
            </div>

            <div class="flex flex-col items-end gap-y-1">
                <p class="text-xs text-gray-500 text-right dark:text-gray-400">
                    <a href="https://incloudsistemas.com.br" rel="noopener noreferrer" target="_blank">
                        <img src="{{ asset('images/desenvolvido-por-incloud.png') }}" alt="desenvolvido por InCloud." />
                    </a>
                    <span>
                        {{ config('app.i2c_pretty_version') }}
                    </span>
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
