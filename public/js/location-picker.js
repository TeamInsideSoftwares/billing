/**
 * Centralized Location Picker Logic
 * Handles Country -> State -> City dependent dropdowns
 */

const LocationPicker = (function () {
    const apiBase = 'https://countriesnow.space/api/v0.1/countries';

    async function fetchCountries() {
        try {
            const response = await fetch(`${apiBase}`);
            const data = await response.json();
            return data.data.map(c => c.country);
        } catch (e) { console.error('Error fetching countries:', e); return []; }
    }

    async function fetchStates(country) {
        if (!country) return [];
        try {
            const response = await fetch(`${apiBase}/states`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country })
            });
            const data = await response.json();
            return data.data.states.map(s => s.name);
        } catch (e) { console.error('Error fetching states:', e); return []; }
    }

    async function fetchCities(country, state) {
        if (!country || !state) return [];
        try {
            const response = await fetch(`${apiBase}/state/cities`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ country, state })
            });
            const data = await response.json();
            return data.data;
        } catch (e) { console.error('Error fetching cities:', e); return []; }
    }

    function normalizeLocationLabel(value) {
        return String(value || '')
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }

    function cityDedupeKey(value) {
        return normalizeLocationLabel(value).replace(/[\s-]+/g, '');
    }

    function toDisplayCityLabel(value) {
        const cleaned = String(value || '')
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^\w\s-]/g, '')
            .replace(/\s+/g, ' ')
            .trim();

        if (!cleaned) return '';

        return cleaned
            .split(' ')
            .filter(Boolean)
            .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
            .join(' ');
    }

    function scoreCityLabel(value) {
        const raw = String(value || '').trim();
        if (!raw) return -Infinity;

        let score = 0;

        // Prefer plain ASCII labels (e.g. "Dehradun") over accented variants.
        if (/^[\x20-\x7E]+$/.test(raw)) score += 5;

        // Prefer compact names when equivalent (e.g. "Dehradun" over "Dehra Dun").
        if (!/\s/.test(raw)) score += 2;

        // Mild penalty for punctuation-heavy labels.
        if (/[-,./]/.test(raw)) score -= 1;

        return score;
    }

    function uniqueCities(cities) {
        const chosenByKey = new Map();

        (cities || []).forEach(city => {
            const raw = String(city || '').trim();
            if (!raw) return;
            const key = cityDedupeKey(raw);
            if (!key) return;

            const existing = chosenByKey.get(key);
            if (!existing || scoreCityLabel(raw) > scoreCityLabel(existing)) {
                chosenByKey.set(key, raw);
            }
        });

        return Array.from(chosenByKey.values())
            .map(city => toDisplayCityLabel(city))
            .filter(Boolean)
            .sort((a, b) => a.localeCompare(b));
    }

    async function init(container = document) {
        const countries = await fetchCountries();
        const forms = container.querySelectorAll('form');

        forms.forEach(async (form) => {
            const countrySelects = form.querySelectorAll('.country-select');

            countrySelects.forEach(async (countrySelect) => {
                // Find nearest siblings within the same form context
                const parent = countrySelect.closest('.form-grid') || form;
                const stateSelect = parent.querySelector('.state-select');
                const citySelect = parent.querySelector('.city-select');

                if (!stateSelect || !citySelect) return;

                // Populate Countries
                countrySelect.innerHTML = '<option value="">Select Country</option>';
                countries.forEach(c => {
                    const opt = new Option(c, c);
                    if (c === countrySelect.dataset.selected) opt.selected = true;
                    countrySelect.add(opt);
                });

                const handleCountryChange = async (isInitial = false) => {
                    const country = countrySelect.value;
                    const savedState = stateSelect.dataset.selected;
                    const savedCity = citySelect.dataset.selected;

                    stateSelect.innerHTML = '<option value="">Select State</option>';
                    citySelect.innerHTML = '<option value="">Select City</option>';

                    if (!country) return;

                    const states = await fetchStates(country);
                    states.forEach(s => {
                        const opt = new Option(s, s);
                        if (isInitial && s === savedState) opt.selected = true;
                        stateSelect.add(opt);
                    });

                    if (isInitial && stateSelect.value) handleStateChange(true);
                };

                const handleStateChange = async (isInitial = false) => {
                    const country = countrySelect.value;
                    const state = stateSelect.value;
                    const savedCity = citySelect.dataset.selected;
                    citySelect.innerHTML = '<option value="">Select City</option>';

                    if (!country || !state) return;

                    const cities = uniqueCities(await fetchCities(country, state));
                    cities.forEach(c => {
                        const opt = new Option(c, c);
                        if (isInitial && normalizeLocationLabel(c) === normalizeLocationLabel(savedCity)) opt.selected = true;
                        citySelect.add(opt);
                    });
                };

                countrySelect.addEventListener('change', () => handleCountryChange());
                stateSelect.addEventListener('change', () => handleStateChange());

                // Initial Load
                if (countrySelect.value) handleCountryChange(true);
            });
        });
    }

    /**
     * Re-populate states and cities for a given country select element using
     * the dataset.selected values on the country/state/city selects.
     * Use this when programmatically setting data-selected and needing
     * the dropdowns to reflect those values immediately (e.g. edit forms).
     *
     * @param {HTMLSelectElement} countrySelect
     */
    async function loadSelection(countrySelect) {
        const parent = countrySelect.closest('.form-grid') || countrySelect.closest('form');
        if (!parent) return;

        const stateSelect = parent.querySelector('.state-select');
        const citySelect = parent.querySelector('.city-select');
        if (!stateSelect || !citySelect) return;

        const country = countrySelect.dataset.selected || '';
        const savedState = stateSelect.dataset.selected || '';
        const savedCity = citySelect.dataset.selected || '';

        // Set the country dropdown value
        countrySelect.value = country;
        if (!countrySelect.value && country) {
            // Country not yet in list — wait for countries to be populated then retry
            await new Promise(resolve => setTimeout(resolve, 500));
            countrySelect.value = country;
        }

        stateSelect.innerHTML = '<option value="">Select State</option>';
        citySelect.innerHTML = '<option value="">Select City</option>';

        if (!country) return;

        const states = await fetchStates(country);
        states.forEach(s => {
            const opt = new Option(s, s);
            if (s === savedState) opt.selected = true;
            stateSelect.add(opt);
        });

        if (!stateSelect.value) return;

        const cities = uniqueCities(await fetchCities(country, stateSelect.value));
        cities.forEach(c => {
            const opt = new Option(c, c);
            if (normalizeLocationLabel(c) === normalizeLocationLabel(savedCity)) opt.selected = true;
            citySelect.add(opt);
        });
    }

    return {
        init: init,
        loadSelection: loadSelection,
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    LocationPicker.init();
});
