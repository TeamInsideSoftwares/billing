<!-- Step Navigation Indicator (Optional) -->
<!-- This can be included at the top of the form to show progress -->
<div class="step-indicator-container">
    <div class="step-indicator-track">
        <!-- Progress Bar Background -->
        <div class="step-indicator-bar-bg"></div>
        
        <!-- Progress Bar Active -->
        <div id="progressBarActive" class="step-indicator-bar-active"></div>
        
        @for($i = 1; $i <= ($totalSteps ?? 3); $i++)
        <div class="step-item" data-step="{{ $i }}">
            <div class="step-circle">
                {{ $i }}
            </div>
            <div class="step-label">
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
