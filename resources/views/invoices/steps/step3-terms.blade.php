<!-- Step 3: Terms & Preview -->
<div id="step3" class="invoice-step" style="display: none;">
    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
        <button type="button" id="btnBackToStep2" class="secondary-button" style="padding: 0.5rem 1rem;">&larr; Back to Step 2</button>
        <h4 style="margin: 0; font-size: 1.1rem; color: #334155;">Final Review</h4>
    </div>

    <!-- Proforma Invoice Preview -->
    <div class="panel-card" style="padding: 0; border: 1px solid #e2e8f0; overflow: hidden; background: #fff; margin-bottom: 1.5rem;">
        <div style="background: #f8fafc; padding: 0.75rem 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <h5 style="margin: 0; font-size: 0.95rem; color: #1e293b;">
                <i class="fas fa-file-pdf" style="color: #ef4444; margin-right: 0.5rem;"></i> 
                Proforma Invoice Preview
            </h5>
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">
                <i class="fas fa-circle" style="color: #f59e0b; font-size: 0.5rem; margin-right: 0.3rem;"></i>
                Live Preview
            </span>
        </div>
        <div id="invoicePreviewContainer" style="padding: 2rem; background: #94a3b8; max-height: 750px; overflow-y: auto;">
            <div id="previewContent" style="background: white; padding: 2.5rem; width: 100%; min-height: 842px; box-shadow: 0 10px 25px rgba(0,0,0,0.15); border-radius: 4px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #1e293b;">
                <!-- Preview will be dynamically injected here -->
                <div style="text-align: center; color: #64748b; padding-top: 100px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 2rem; margin-bottom: 1rem;"></i>
                    <p>Generating preview...</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Terms & Conditions Below -->
    <div class="panel-card" style="padding: 1rem; border: 1px solid #e2e8f0; background: #fff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; padding-bottom: 0.4rem; border-bottom: 1px solid #e2e8f0;">
            <h5 style="margin: 0; font-size: 0.9rem; color: #1e293b;">Terms & Conditions</h5>
            <button type="button" id="btnAddTC" class="text-link" style="font-size: 0.75rem; font-weight: 600;">+ Add</button>
        </div>
        <div id="termsList" style="max-height: 400px; overflow-y: auto; padding-right: 0.25rem;">
            @foreach($billingTerms as $term)
            <div style="margin-bottom: 0.4rem; padding: 0.5rem; border-radius: 6px; border: 1px solid #e2e8f0; background: #f8fafc; transition: all 0.2s;" class="term-item-row">
                <label class="custom-checkbox" style="display: flex; align-items: flex-start; gap: 0.5rem; cursor: pointer;">
                    <input type="checkbox" class="term-checkbox" data-tc-id="{{ $term->tc_id }}" data-content="{{ $term->content }}" value="{{ $term->content }}" style="margin-top: 0.15rem; width: 14px; height: 14px; cursor: pointer; flex-shrink: 0;">
                    <div style="flex: 1;">
                        <p style="margin: 0; font-size: 0.78rem; color: #475569; line-height: 1.4;">{{ $term->content }}</p>
                    </div>
                </label>
            </div>
            @endforeach
        </div>
    </div>

    <div style="margin-top: 2rem; display: flex; justify-content: flex-end;">
        <button type="submit" class="primary-button create-submit-btn" id="finalSubmitBtnStep3" disabled style="padding: 1rem 4rem; font-size: 1.1rem; box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.4);">
            <i class="fas fa-file-invoice" style="margin-right: 0.5rem;"></i>Create Proforma Invoice
        </button>
    </div>
</div>
