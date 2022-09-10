function toggleShowHidePasswordField(selector) {
    if (typeof selector != 'undefined') {
        if ($(selector).attr('type') == 'text') {
            $(selector).attr('type', 'password');
        } else if ($(selector).attr('type') == 'password') {
            $(selector).attr('type', 'text');
        }
    }
}
