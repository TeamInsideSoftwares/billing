<!-- Order Timeline Modal -->
<div class="modal fade" id="orderTimelineModal" tabindex="-1" aria-labelledby="orderTimelineModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-0 bg-white py-2">
                <h5 class="modal-title fw-semibold" id="orderTimelineModalLabel">
                    Order History Timeline <span class="text-primary small" id="timelineOrderNumber"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-DarkLight p-3" style="max-height: 480px; overflow-y: auto;">
                <!-- Loading Spinner -->
                <div id="timelineLoading" class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="timelineEmpty" class="text-center py-4 d-none">
                    <i class="fas fa-history text-muted mb-2 fs-3 opacity-50"></i>
                    <p class="text-muted small mb-0">No timeline events recorded for this order.</p>
                </div>

                <!-- Timeline wrapper -->
                <div id="timelineContent" class="table-responsive border rounded bg-white d-none">
                    <table class="table table-striped mainTable align-middle mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th class="px-2 py-2" width="20%">Event</th>
                                <th class="px-2 py-2">Description</th>
                                <th class="px-2 py-2 text-center" width="20%">Performed By</th>
                                <th class="px-2 py-2 text-center" width="20%">Date & Time</th>
                            </tr>
                        </thead>
                        <tbody id="timelineTableBody">
                            <!-- Javascript will populate timeline rows here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const timelineModalEl = document.getElementById('orderTimelineModal');
        if (!timelineModalEl || typeof bootstrap === 'undefined') return;

        const timelineModal = new bootstrap.Modal(timelineModalEl);
        const orderNumberEl = document.getElementById('timelineOrderNumber');
        const loadingEl = document.getElementById('timelineLoading');
        const emptyEl = document.getElementById('timelineEmpty');
        const contentEl = document.getElementById('timelineContent');

        function formatEventName(actionType) {
            return actionType.split('_').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ');
        }

        function formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            if (isNaN(d.getTime())) return dateStr;
            const day = d.getDate();
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const month = months[d.getMonth()];
            const year = d.getFullYear();
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            return `${day} ${month} ${year}, ${hours}:${minutes}`;
        }

        document.addEventListener('click', function (e) {
            const btn = e.target.closest('.js-view-timeline-btn');
            if (!btn) return;

            e.preventDefault();
            const orderId = btn.dataset.orderId || '';
            const orderNumber = btn.dataset.orderNumber || orderId;

            orderNumberEl.textContent = '#' + orderNumber;

            // Show modal and loading state
            loadingEl.classList.remove('d-none');
            emptyEl.classList.add('d-none');
            contentEl.classList.add('d-none');
            const tableBodyEl = document.getElementById('timelineTableBody');
            if (tableBodyEl) {
                tableBodyEl.innerHTML = '';
            }
            timelineModal.show();

            const url = "{{ route('orders.timeline', ['order' => '__ORDER__']) }}".replace('__ORDER__', orderId);

            fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(res => res.json())
                .then(data => {
                    loadingEl.classList.add('d-none');
                    if (!data || data.length === 0) {
                        emptyEl.classList.remove('d-none');
                        return;
                    }

                    contentEl.classList.remove('d-none');
                    if (tableBodyEl) {
                        data.forEach(item => {
                            const actorName = item.creator ? item.creator.name : (item.created_by || 'SYSTEM');
                            const rowHtml = `
                                <tr>
                                    <td class="px-2 py-2 small fw-semibold text-dark">${formatEventName(item.action_type)}</td>
                                    <td class="px-2 py-2 small text-dark">${item.description}</td>
                                    <td class="px-2 py-2 small text-center text-dark">
                                        <span class="d-inline-flex align-items-center gap-1">
                                            ${actorName}
                                        </span>
                                    </td>
                                    <td class="px-2 py-2 small text-center text-dark">${formatDate(item.created_at)}</td>
                                </tr>
                            `;
                            tableBodyEl.insertAdjacentHTML('beforeend', rowHtml);
                        });
                    }
                })
                .catch(err => {
                    console.error(err);
                    loadingEl.classList.add('d-none');
                    emptyEl.classList.remove('d-none');
                    emptyEl.querySelector('p').textContent = 'An error occurred while loading order history.';
                });
        });
    });
</script>
