document.addEventListener('DOMContentLoaded', function () {
    var flashNotification = document.getElementById('flash-success-notification');

    if (!flashNotification) {
        return;
    }

    setTimeout(function () {
        flashNotification.remove();
    }, 5000);
});