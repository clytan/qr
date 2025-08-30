function setAllFieldsReadOnly() {
    var inputs = document.querySelectorAll('input, textarea, select');
    inputs.forEach(function(el) {
        if (el.type === 'file' || el.type === 'hidden' || el.type === 'button' || el.type === 'submit') {
            el.disabled = true;
        } else {
            el.readOnly = true;
        }
    });
}
eventHandler={
    init: function() {}
}

indexFunction ={
    init: function() {}
}
$(document).ready(function() {
    // If PUBLIC_PROFILE is set, make all fields readonly/disabled
    if (window.PUBLIC_PROFILE) {
        setAllFieldsReadOnly();
    }
    // Initial state
    eventHandler.init();
    indexFunction.init();
});