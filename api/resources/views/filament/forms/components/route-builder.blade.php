<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @include('filament.forms.components.partials.map-assets')

    <div x-data="tuRouteBuilder({ state: $wire.$entangle(@js($getStatePath())), places: @js($getPlaces()) })">
        <div class="tu-toolbar">
            <x-filament::input.wrapper>
                <x-filament::input.select x-model="newMode" aria-label="Как добавлять новые отрезки">
                    <option value="drive">Новые отрезки: авто (по дорогам)</option>
                    <option value="hike">Новые отрезки: пешком (по тропам)</option>
                    <option value="horse">Новые отрезки: верхом</option>
                    <option value="line">Новые отрезки: по прямой</option>
                </x-filament::input.select>
            </x-filament::input.wrapper>
            <x-filament::button size="sm" color="gray" x-on:click="setAllModes(newMode)" x-bind:disabled="modes.length === 0">
                Применить ко всем
            </x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-list-bullet" x-on:click="fromItinerary()">
                Собрать из дней программы
            </x-filament::button>
            <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-up-tray" x-on:click="$refs.gpx.click()">
                Загрузить GPX
            </x-filament::button>
            <input type="file" accept=".gpx,application/gpx+xml" x-ref="gpx" x-on:change="importGpx($event)" style="display: none" />
            <x-filament::button size="sm" color="danger" outlined icon="heroicon-o-trash" x-on:click="clear()" x-show="waypoints.length > 0">
                Очистить
            </x-filament::button>
            <span x-show="busy" class="tu-legend"><x-filament::loading-indicator style="width: 1rem; height: 1rem" /> Считаю маршрут…</span>
        </div>

        <div wire:ignore x-ref="map" class="tu-map"></div>
        <p class="tu-hint">
            Клик по карте добавляет точку в конец маршрута, серые кружки — места из справочника. Точки можно перетаскивать.
            «Авто» прокладывает путь по дорогам, «пешком» и «верхом» — по тропам OpenStreetMap; где тропы нет, отрезок рисуется прямой (пунктир).
        </p>
        <p class="tu-error" x-show="error" x-text="error"></p>

        <template x-if="props">
            <div>
                <div class="tu-stats">
                    <div>Всего <b x-text="props.distanceKm + ' км'"></b></div>
                    <template x-for="(km, mode) in props.distanceByMode" :key="mode">
                        <div><span class="tu-legend"><i :style="`background: ${modeStyle[mode]?.color}`"></i><span x-text="modeStyle[mode]?.label"></span></span><b x-text="km + ' км'"></b></div>
                    </template>
                    <div x-show="props.elevationGainM !== null">Набор высоты <b x-text="'+' + props.elevationGainM + ' м'"></b></div>
                    <div x-show="props.maxAltitudeM !== null">Макс. высота <b x-text="props.maxAltitudeM + ' м'"></b></div>
                    <div x-show="props.source === 'gpx'"><b>Трек из GPX</b>при изменении точек будет перестроен</div>
                </div>
                <p class="tu-hint" x-show="props.approximate">Часть отрезков не удалось проложить по дорогам или тропам: они нарисованы прямыми. Добавьте промежуточные точки или загрузите GPX.</p>
                <svg class="tu-profile" viewBox="0 0 1000 100" preserveAspectRatio="none" x-show="props.elevationProfile">
                    <path :d="profilePath()" fill="rgba(45, 93, 140, 0.18)" stroke="#2d5d8c" stroke-width="2" vector-effect="non-scaling-stroke" />
                </svg>
            </div>
        </template>

        <div class="tu-list">
            <template x-for="(w, i) in waypoints" :key="i">
                <div>
                    <div class="tu-row">
                        <span class="tu-num" x-text="i + 1"></span>
                        <x-filament::input.wrapper>
                            <x-filament::input type="text" x-model.lazy="w.name" x-on:change="renamed()" placeholder="Название точки (EN), например Ala-Kul pass" />
                        </x-filament::input.wrapper>
                        <div style="display: flex; gap: 0.25rem">
                            <x-filament::icon-button icon="heroicon-m-arrow-up" color="gray" label="Выше" x-on:click="move(i, -1)" />
                            <x-filament::icon-button icon="heroicon-m-arrow-down" color="gray" label="Ниже" x-on:click="move(i, 1)" />
                            <x-filament::icon-button icon="heroicon-m-x-mark" color="danger" label="Удалить точку" x-on:click="remove(i)" />
                        </div>
                    </div>
                    <div class="tu-seg" x-show="i < waypoints.length - 1">
                        <span>↓</span>
                        <select x-model="modes[i]" x-on:change="changed()" style="font-size: 0.8125rem; border-radius: 0.375rem; padding: 0.125rem 1.75rem 0.125rem 0.5rem; background-color: transparent">
                            <template x-for="(style, mode) in modeStyle" :key="mode">
                                <option :value="mode" x-text="style.label" :selected="modes[i] === mode"></option>
                            </template>
                        </select>
                        <span x-text="state?.features?.[i] ? state.features[i].properties.distanceKm + ' км' : ''"></span>
                    </div>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
