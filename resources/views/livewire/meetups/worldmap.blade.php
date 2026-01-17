@push('scripts')
    <script src="{{ asset('dist/jquery.js') }}"></script>
    <script src="{{ asset('vendor/jvector/jquery-jvectormap-2.0.5.min.js') }}"></script>
    <script src="{{ asset('vendor/jvector/maps/world-mill.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('vendor/jvector/jquery-jvectormap-2.0.5.css') }}" type="text/css"
          media="screen"/>
@endpush

<x-layouts.app title="{{ __('Worldmap') }}">
    <div
        wire:ignore
        class="w-full flex justify-center"
        x-data="{
                init() {
                    let markers = {{ Js::from($markers) }};

                    $('#mapworld').vectorMap({
                        zoomButtons : true,
                        zoomOnScroll: true,
                        map: 'world_mill',
                        backgroundColor: 'transparent',
                        markers: markers.map(function(h){ return {name: h.name, latLng: h.coords} }),
                        onMarkerClick: function(event, index) {
                            $wire.call('filterByMarker', markers[index].id)
                        },
                        markerStyle: {
                            initial: {
                                image: '{{ asset('img/btc.png') }}',
                            }
                        },
                        regionStyle: {
                            initial: {
                                fill: '#a4a4a4'
                            },
                            hover: {
                                'fill-opacity': 1,
                                cursor: 'default'
                            },
                        }
                    });
                }
            }"
    >
        <div id="mapworld" style="width: 100%;" class="h-[200px] sm:h-[400px]"></div>
    </div>
</x-layouts.app>
