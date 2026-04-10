/**
 * Centralized Location Picker Logic
 * Handles Country -> State -> City dependent dropdowns
 */

const LocationPicker = (function() {
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
                    citySelect.innerHTML = '<option value="">Select City</option>';

                    if (!country || !state) return;

                    const cities = await fetchCities(country, state);
                    cities.forEach(c => {
                        const opt = new Option(c, c);
                        if (isInitial && c === citySelect.dataset.selected) opt.selected = true;
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

    return {
        init: init
    };
})();

document.addEventListener('DOMContentLoaded', () => {
    LocationPicker.init();
});
