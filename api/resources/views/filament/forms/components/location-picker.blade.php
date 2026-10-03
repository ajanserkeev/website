<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @include('filament.forms.components.partials.map-assets')

    <div x-data="tuLocationPicker({
        lat: $wire.$entangle(@js($getSiblingStatePath('latitude')), true),
        lng: $wire.$entangle(@js($getSiblingStatePath('longitude')), true),
        alt: $wire.$entangle(@js($getSiblingStatePath('altitude_m')), true),
    })">
        <div wire:ignore x-ref="map" class="tu-map tu-map-small"></div>
        <p class="tu-hint">Кликните по карте или перетащите маркер. Если высота не заполнена, она подставится по модели рельефа.</p>
    </div>
</x-dynamic-component>
