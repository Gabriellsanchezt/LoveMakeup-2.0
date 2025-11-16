document.addEventListener("DOMContentLoaded", function () {

    const progressBar = document.getElementById("progress-bar-pasos");
    const steps = document.querySelectorAll(".step");

    function setStep(currentStep) {
        const totalSteps = steps.length;
        const percentage = ((currentStep - 1) / (totalSteps - 1)) * 100;
        progressBar.style.width = percentage + "%";

        steps.forEach((step, index) => {
            if (index < currentStep - 1) {
                step.classList.add("completed");
                step.classList.remove("active");
            } else if (index === currentStep - 1) {
                step.classList.add("active");
                step.classList.remove("completed");
            } else {
                step.classList.remove("active", "completed");
            }
        });
    }

    // Exponer globalmente
    window.setStep = setStep;

});
