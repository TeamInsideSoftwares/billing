<div id="renewOrderModal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
    <!-- Backdrop overlay -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm modal-close-overlay" onclick="closeModal('renewOrderModal')"></div>
    
    <!-- Dialog container -->
    <div class="relative bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden z-10 flex flex-col max-h-[90vh]">
        <!-- Header -->
        <div class="flex items-center justify-between p-4 border-b border-slate-100 bg-slate-50">
            <h3 class="text-base font-bold text-slate-800">Renew Order</h3>
            <button type="button" class="text-slate-400 hover:text-slate-600 text-lg font-bold" onclick="closeModal('renewOrderModal')">&times;</button>
        </div>
        <!-- Form -->
        <form id="renewOrderForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <!-- Body -->
            <div class="p-6 overflow-y-auto flex-1 text-left space-y-4">
                <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 bg-slate-50 p-3 rounded-lg border border-slate-100">
                    <div><strong>Client:</strong> <span id="renewOrderClientName" class="text-slate-700 font-semibold">-</span></div>
                    <div><strong>Order #:</strong> <span id="renewOrderNumber" class="text-slate-700 font-semibold">-</span></div>
                    <div class="col-span-2"><strong>Item:</strong> <span id="renewOrderItemName" class="text-slate-700 font-semibold">-</span></div>
                    <div><strong>Create Date:</strong> <span id="renewOrderStartDate" class="text-slate-700 font-semibold">-</span></div>
                    <div><strong>Current Expiry Date:</strong> <span id="renewOrderCurrentEndDate" class="text-slate-700 font-semibold">-</span></div>
                    <div class="col-span-2"><strong>Status:</strong> <span id="renewOrderStatus" class="text-slate-700 font-semibold">-</span></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1" for="renew_order_frequency">Frequency</label>
                    <select name="frequency" id="renew_order_frequency" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                        <option value="">None</option>
                        <option value="One-Time">One-Time</option>
                        <option value="Day(s)">Day(s)</option>
                        <option value="Week(s)">Week(s)</option>
                        <option value="Month(s)">Month(s)</option>
                        <option value="Quarter(s)">Quarter(s)</option>
                        <option value="Year(s)">Year(s)</option>
                    </select>
                </div>

                <div id="renew_order_duration_wrapper">
                    <label class="block text-xs font-semibold text-slate-500 mb-1" for="renew_order_duration">Duration</label>
                    <input type="number" name="duration" id="renew_order_duration" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" min="1" step="1" value="1">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-500 mb-1" for="renew_order_end_date">New Expiry</label>
                    <input type="date" name="end_date" id="renew_order_end_date" class="w-full bg-white border border-slate-300 rounded px-3 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500" required>
                </div>

                <input type="hidden" name="c" id="renew_order_client">
                <input type="hidden" name="tab" id="renew_order_tab">
                <input type="hidden" name="from" id="renew_order_from">
                <input type="hidden" name="to" id="renew_order_to">
                <input type="hidden" name="next_days" id="renew_order_next_days">
                <input type="hidden" name="return_to" id="renew_order_return_to">
            </div>
            <!-- Footer -->
            <div class="flex justify-end items-center gap-2 p-4 border-t border-slate-100 bg-slate-50">
                <button type="button" class="px-4 py-2 text-slate-500 hover:text-slate-700 text-xs font-semibold" onclick="closeModal('renewOrderModal')">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded text-xs font-semibold shadow-sm transition-colors">Renew</button>
            </div>
        </form>
    </div>
</div>
