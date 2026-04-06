{{-- Tax selector dropdown + quick-add modal --}}
{{-- Used in creating/editing services costing rows. --}}
{{-- $taxes should be passed as $taxes from parent view. --}}
@php
    $taxesJson = json_encode($taxes->map(fn($t) => ['id' => $t->taxid, 'name' => $t->tax_name ?? $t->type, 'type' => $t->type, 'rate' => $t->rate])->values()->all());
    $taxRateName = $taxRateName ?? 'tax_rate';
    $taxIncludeName = $taxIncludeName ?? 'tax_included';
@endphp

<select name="{{ $includeName ?? $taxIncludeName }}" style="min-width: 80px; padding: 0.3rem; font-size: 0.82rem;" required>
    <option value="no" {{ (($includeValue ?? $costing['tax_included'] ?? 'no') === 'no') ? 'selected' : '' }}>Excl.</option>
    <option value="yes" {{ (($includeValue ?? $costing['tax_included'] ?? 'no') === 'yes') ? 'selected' : '' }}>Incl.</option>
</select>

<select name="{{ $rateName ?? $taxRateName }}" class="tax-select-dropdown" data-taxes='{{ $taxesJson }}' style="min-width: 130px; padding: 0.3rem; font-size: 0.82rem;">
    <option value="" data-rate="">-- Select tax --</option>
    @foreach($groupedTaxes ?? $taxes->groupBy('type') as $type => $typeTaxes)
        <optgroup label="{{ $type }}">
            @foreach($typeTaxes as $tax)
                <option value="{{ $tax->taxid }}" data-rate="{{ $tax->rate }}">
                    {{ $tax->tax_name ?? $tax->type }} ({{ $tax->rate }}%) - {{ $tax->tax_name ?? '—' }}
                </option>
            @endforeach
        </optgroup>
    @endforeach
</select>
