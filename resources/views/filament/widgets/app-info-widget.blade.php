<x-filament-widgets::widget class="fi-filament-info-widget">
    <x-filament::section>
        <div class="flex items-center gap-x-3 py-1">
            <img src="{{ asset('images/i2c-logo.png') }}" width="65" title="InCloudCodile15"
                alt="InCloudCodile15" />

            <div class="flex-1">
                <h2 class="grid flex-1 text-base font-semibold leading-6 text-gray-500 dark:text-white">
                    InCloudCodile15
                </h2>
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
