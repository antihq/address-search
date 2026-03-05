<?php

use Illuminate\Support\Facades\Http;
use Livewire\Component;

new class extends Component
{
    public string $searchQuery = '';

    public array $suggestions = [];

    public ?string $selectedAddressId = null;

    public string $selectedAddress = '';

    public ?float $latitude = null;

    public ?float $longitude = null;

    public ?array $bbox = null;

    public function updatedSearchQuery(): void
    {
        if (strlen($this->searchQuery) < 3) {
            $this->suggestions = [];

            return;
        }

        $this->searchMapbox();
    }

    public function updatedSelectedAddressId(): void
    {
        if (! $this->selectedAddressId) {
            return;
        }

        $selected = collect($this->suggestions)
            ->firstWhere('id', $this->selectedAddressId);

        if ($selected) {
            $this->selectedAddress = $selected['full_address'];
            $this->latitude = $selected['latitude'];
            $this->longitude = $selected['longitude'];
            $this->bbox = $selected['bbox'];
        }
    }

    private function searchMapbox(): void
    {
        $response = Http::get('https://api.mapbox.com/search/geocode/v6/forward', [
            'q' => $this->searchQuery,
            'access_token' => config('services.mapbox.access_token'),
            'limit' => 10,
            'autocomplete' => 'true',
        ]);

        if (! $response->successful()) {
            $this->suggestions = [];

            return;
        }

        $data = $response->json();

        $this->suggestions = collect($data['features'] ?? [])
            ->map(function ($feature) {
                return [
                    'id' => $feature['id'],
                    'name' => $feature['properties']['name_preferred'] ?? $feature['properties']['name'],
                    'place_formatted' => $feature['properties']['place_formatted'] ?? '',
                    'full_address' => $feature['properties']['full_address'] ?? '',
                    'longitude' => $feature['geometry']['coordinates'][0],
                    'latitude' => $feature['geometry']['coordinates'][1],
                    'bbox' => $feature['properties']['bbox'] ?? null,
                ];
            })
            ->toArray();
    }

    public function clearSelection(): void
    {
        $this->reset();
    }
};
?>

<div class="min-h-screen flex flex-col items-center justify-center p-8">
    <div class="w-full max-w-md mx-auto">
        <div class="space-y-8">
            <flux:fieldset>
                <flux:legend>Address Search</flux:legend>

                <flux:select
                    label="Address"
                    wire:model="selectedAddressId"
                    variant="combobox"
                    :filter="false"
                    placeholder="Search for an address..."
                    description="Start typing an address (minimum 3 characters)"
                >
                    <x-slot name="input">
                        <flux:select.input
                            wire:model.live="searchQuery"
                            placeholder="Search for an address..."
                        />
                    </x-slot>

                    @foreach ($suggestions as $suggestion)
                        <flux:select.option
                            value="{{ $suggestion['id'] }}"
                            wire:key="{{ $suggestion['id'] }}"
                        >
                            {{ $suggestion['name'] }}@if($suggestion['place_formatted']) - {{ $suggestion['place_formatted'] }}@endif
                        </flux:select.option>
                    @endforeach
                </flux:select>
            </flux:fieldset>

            @if($selectedAddressId)
                <flux:fieldset>
                    <flux:legend>Selected Address</flux:legend>

                    <div class="space-y-6">
                        <flux:input 
                            label="Address" 
                            :value="$selectedAddress" 
                            readonly 
                            variant="filled"
                        />

                        <div class="grid grid-cols-2 gap-4">
                            <flux:input 
                                label="Latitude" 
                                :value="$latitude" 
                                readonly 
                                variant="filled"
                            />
                            <flux:input 
                                label="Longitude" 
                                :value="$longitude" 
                                readonly 
                                variant="filled"
                            />
                        </div>

                        @if($bbox)
                            <flux:fieldset>
                                <flux:legend>Bounding Box</flux:legend>

                                <div class="grid grid-cols-2 gap-4">
                                    <flux:input
                                        label="Min Longitude"
                                        :value="$bbox[0]"
                                        readonly
                                        variant="filled"
                                    />
                                    <flux:input
                                        label="Min Latitude"
                                        :value="$bbox[1]"
                                        readonly
                                        variant="filled"
                                    />
                                    <flux:input
                                        label="Max Longitude"
                                        :value="$bbox[2]"
                                        readonly
                                        variant="filled"
                                    />
                                    <flux:input
                                        label="Max Latitude"
                                        :value="$bbox[3]"
                                        readonly
                                        variant="filled"
                                    />
                                </div>
                            </flux:fieldset>
                        @endif

                        <div class="flex">
                            <flux:spacer />
                            <flux:button
                                wire:click="clearSelection"
                            >
                                Clear
                            </flux:button>
                        </div>
                    </div>
                </flux:fieldset>
            @endif
        </div>
    </div>
</div>
