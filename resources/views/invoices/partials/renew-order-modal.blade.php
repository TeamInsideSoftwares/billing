<div class="modal fade" id="renewOrderModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" style="max-width: 420px;">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-white py-2">
                <h5 class="modal-title fw-semibold">Renew Order <span id="renewOrderNumber"
                        class="text-primary small"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-3">
                <form id="renewOrderForm" method="POST" action="" class="mainForm">
                    @csrf
                    @method('PATCH')
                    <div class="bg-white p-2 rounded-3">
                        <div class="small-text">
                            <div class="d-flex justify-content-between"><span class="d-block">Client</span> <strong
                                    id="renewOrderClientName" class="d-block">-</strong></div>
                            <div class="d-flex justify-content-between"><span class="d-block">Item</span> <strong
                                    id="renewOrderItemName" class="d-block">-</strong></div>
                            <div class="d-flex justify-content-between"><span class="d-block">Create Date</span> <strong
                                    class="d-block" id="renewOrderStartDate">-</strong></div>
                            <div class="d-flex justify-content-between"><span class="d-block">Current Expiry</span>
                                <strong id="renewOrderCurrentEndDate" class="d-block">-</strong>
                            </div>
                            <div class="d-flex justify-content-between"><span class="d-block">Status</span> <strong
                                    id="renewOrderStatus" class="d-block text-danger">-</strong></div>
                        </div>
                    </div>
                    <div class="row g-2 mt-2">
                        <div class="col-12 col-md-8">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                for="renew_order_frequency">Frequency</label>
                            <select name="frequency" id="renew_order_frequency" class="form-select">
                                <option value="One-Time">One-Time</option>
                                <option value="Day(s)">Day(s)</option>
                                <option value="Week(s)">Week(s)</option>
                                <option value="Month(s)">Month(s)</option>
                                <option value="Quarter(s)">Quarter(s)</option>
                                <option value="Year(s)">Year(s)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4" id="renew_order_duration_wrapper">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                for="renew_order_duration">Dur</label>
                            <input type="number" name="duration" id="renew_order_duration" class="form-control" min="1"
                                step="1" value="1">
                        </div>
                        <div class="col-12 col-md-12">
                            <label class="form-label small lh-sm fw-semibold text-dark mb-1"
                                for="renew_order_end_date">New
                                Expiry</label>
                            <input type="date" name="end_date" id="renew_order_end_date" class="form-control" required>
                        </div>
                        <div class="col-12 col-md-12">
                            <div class="d-flex align-items-center justify-content-end mt-2">
                                <button type="submit" class="btn btn-outline-primary btn-primary text-white fw-medium">
                                    Renew <i class="fas fa-arrow-right btn-icon ms-1"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="c" id="renew_order_client">
                    <input type="hidden" name="tab" id="renew_order_tab">
                    <input type="hidden" name="from" id="renew_order_from">
                    <input type="hidden" name="to" id="renew_order_to">
                    <input type="hidden" name="next_days" id="renew_order_next_days">
                    <input type="hidden" name="return_to" id="renew_order_return_to">
            </div>

            </form>
        </div>
    </div>
</div>
</div>
