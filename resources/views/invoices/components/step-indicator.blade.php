<!-- Step Navigation Indicator (Optional) -->
<!-- This can be included at the top of the form to show progress -->
<div class="step-indicator-container" style="margin-bottom: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; position: relative;">
        <!-- Progress Bar Background -->
        <div style="position: absolute; top: 50%; left: 0; right: 0; height: 4px; background: #e2e8f0; transform: translateY(-50%); border-radius: 2px;"></div>
        
        <!-- Progress Bar Active -->
        <div id="progressBarActive" style="position: absolute; top: 50%; left: 0; height: 4px; background: linear-gradient(90deg, #3b82f6 0%, #2563eb 100%); transform: translateY(-50%); border-radius: 2px; transition: width 0.3s ease;"></div>
        
        @for($i = 1; $i <= ($totalSteps ?? 3); $i++)
        <div class="step-item" data-step="{{ $i }}" style="position: relative; z-index: 1; display: flex; flex-direction: column; align-items: center;">
            <div class="step-circle" style="width: 40px; height: 40px; border-radius: 50%; background: #fff; border: 3px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #64748b; transition: all 0.3s ease;">
                {{ $i }}
            </div>
            <div class="step-label" style="margin-top: 0.5rem; font-size: 0.75rem; color: #64748b; font-weight: 500; text-align: center;">
                @if($i === 1) Client & Source
                @elseif($i === 2) Items & Details
                @elseif($i === 3) Terms & Preview
                @else Step {{ $i }}
                @endif
            </div>
        </div>
        @endfor
    </div>
</div>

<style>
.step-item.completed .step-circle {
    background: #10b981;
    border-color: #10b981;
    color: #fff;
}

.step-item.active .step-circle {
    background: #2563eb;
    border-color: #2563eb;
    color: #fff;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.2);
}

.step-item.active .step-label {
    color: #2563eb;
    font-weight: 700;
}
</style>

<script>
(function() {
    // Update step indicator based on current step
    window.updateStepIndicator = function(currentStep) {
        const totalSteps = {{ $totalSteps ?? 3 }};
        const progressPercentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        
        // Update progress bar
        const progressBar = document.getElementById('progressBarActive');
        if (progressBar) {
            progressBar.style.width = `${progressPercentage}%`;
        }
        
        // Update step items
        document.querySelectorAll('.step-item').forEach((item, index) => {
            const stepNum = index + 1;
            item.classList.remove('completed', 'active');
            
            if (stepNum < currentStep) {
                item.classList.add('completed');
                item.querySelector('.step-circle').innerHTML = '<i class="fas fa-check"></i>';
            } else if (stepNum === currentStep) {
                item.classList.add('active');
                item.querySelector('.step-circle').textContent = stepNum;
            } else {
                item.querySelector('.step-circle').textContent = stepNum;
            }
        });
    };
})();
</script>
