<!-- Step 1: Client & Source Selection -->
<div id="step1" class="invoice-step">
    <div class="invoice-meta-card" style="margin-bottom: 1.5rem;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem; align-items: end;">
            <div>
                <label for="clientid" class="field-label">Client</label>
                <select id="clientid" name="clientid" required class="form-input">
                    <option value="">Choose a client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->clientid }}" data-currency="{{ $client->currency ?? 'INR' }}" {{ old('clientid') == $client->clientid ? 'selected' : '' }}>
                            {{ $client->business_name ?? $client->contact_name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div id="existingInvoicesSection" style="display: none; margin-bottom: 1.5rem;">
        <h4 style="margin: 0 0 0.8rem 0; font-size: 1rem; color: #334155;">Existing Invoices</h4>
        <div id="clientInvoicesAccordion" class="services-accordion-container">
            <!-- Accordion items will be injected here -->
        </div>
        <div id="noInvoicesMessage" class="empty-state" style="display: none;">No invoices found for this client yet.</div>
    </div>

    <div id="sourceSelectionSection" style="display: none; margin-bottom: 1.5rem;">
        <div style="margin-bottom: 1rem; padding: 1rem 1.1rem; border: 1px solid #dbeafe; background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%); border-radius: 12px;">
            <h4 style="margin: 0; font-size: 1rem; color: #1e293b;">Choose Invoice Source</h4>
        </div>

        <div class="source-grid">
            <label class="invoice-source-card">
                <input type="radio" name="invoice_for" value="orders" {{ old('invoice_for') === 'orders' ? 'checked' : '' }}>
                <span class="source-icon"><i class="fas fa-shopping-cart"></i></span>
                <strong>From Orders</strong>
            </label>
            <label class="invoice-source-card">
                <input type="radio" name="invoice_for" value="renewal" {{ old('invoice_for') === 'renewal' ? 'checked' : '' }}>
                <span class="source-icon"><i class="fas fa-sync-alt"></i></span>
                <strong>Renewal</strong>
            </label>
            <label class="invoice-source-card">
                <input type="radio" name="invoice_for" value="without_orders" {{ old('invoice_for') === 'without_orders' ? 'checked' : '' }}>
                <span class="source-icon"><i class="fas fa-pen-ruler"></i></span>
                <strong>Without Orders</strong>
            </label>
        </div>

        <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
            <button type="button" id="btnNextToStep2" class="primary-button" style="padding: 0.8rem 2.5rem; font-size: 1rem;">Next Step &rarr;</button>
        </div>
    </div>
</div>
