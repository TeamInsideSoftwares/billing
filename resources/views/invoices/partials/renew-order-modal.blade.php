<div class="modal fade" id="renewOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content rounded-panel">
            <div class="modal-header modal-header-custom">
                <h5 class="modal-title service-modal-title">Renew Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="renewOrderForm" method="POST" action="">
                @csrf
                @method('PATCH')
                <div class="modal-body service-modal-body">
                    <div class="mb-3 small-text">
                        <div><strong>Client:</strong> <span id="renewOrderClientName">-</span></div>
                        <div><strong>Order #:</strong> <span id="renewOrderNumber">-</span></div>
                    </div>
                    <div class="mb-3 small-text">
                        <div><strong>Item:</strong> <span id="renewOrderItemName">-</span></div>
                    </div>
                    <div class="mb-3 small-text">
                        <div><strong>Create Date:</strong> <span id="renewOrderStartDate">-</span></div>
                        <div><strong>Current Expiry Date:</strong> <span id="renewOrderCurrentEndDate">-</span></div>
                        <div><strong>Status:</strong> <span id="renewOrderStatus">-</span></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="renew_order_frequency">Frequency</label>
                        <select name="frequency" id="renew_order_frequency" class="form-control">
                            <option value="">None</option>
                            <option value="One-Time">One-Time</option>
                            <option value="Day(s)">Day(s)</option>
                            <option value="Week(s)">Week(s)</option>
                            <option value="Month(s)">Month(s)</option>
                            <option value="Quarter(s)">Quarter(s)</option>
                            <option value="Year(s)">Year(s)</option>
                        </select>
                    </div>
                    <div class="mb-3" id="renew_order_duration_wrapper">
                        <label class="form-label" for="renew_order_duration">Duration</label>
                        <input type="number" name="duration" id="renew_order_duration" class="form-control" min="1" step="1" value="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="renew_order_end_date">New Expiry</label>
                        <input type="date" name="end_date" id="renew_order_end_date" class="form-control" required>
                    </div>

                    <input type="hidden" name="c" id="renew_order_client">
                    <input type="hidden" name="tab" id="renew_order_tab">
                    <input type="hidden" name="from" id="renew_order_from">
                    <input type="hidden" name="to" id="renew_order_to">
                    <input type="hidden" name="next_days" id="renew_order_next_days">
                    <input type="hidden" name="return_to" id="renew_order_return_to">
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="text-link small" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="primary-button small">Renew</button>
                </div>
            </form>
        </div>
    </div>
</div>
