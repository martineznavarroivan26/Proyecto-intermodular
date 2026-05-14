document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('profileModal');
    var avatarInput = document.getElementById('profile-modal-avatar');
    var avatarPreview = document.getElementById('profile-modal-avatar-preview');

    if (avatarInput && avatarPreview) {
        avatarInput.addEventListener('change', function (event) {
            var file = event.target.files && event.target.files[0] ? event.target.files[0] : null;

            if (!file || !file.type || file.type.indexOf('image/') !== 0) {
                return;
            }

            var reader = new FileReader();

            reader.onload = function (loadEvent) {
                var existingImg = avatarPreview.querySelector('img');
                if (existingImg) {
                    existingImg.remove();
                }

                var avatarInitial = document.getElementById('profile-modal-avatar-initial');
                if (avatarInitial && avatarInitial.parentNode === avatarPreview) {
                    avatarInitial.remove();
                }

                var img = document.createElement('img');
                img.id = 'profile-modal-avatar-img';
                img.src = loadEvent.target && loadEvent.target.result ? loadEvent.target.result : '';
                img.alt = 'Vista previa del avatar';
                avatarPreview.appendChild(img);
            };

            reader.readAsDataURL(file);
        });
    }

    if (!modalElement || typeof bootstrap === 'undefined') {
        return;
    }

    document.querySelectorAll('[data-profile-trigger="modal"]').forEach(function (trigger) {
        trigger.addEventListener('click', function (event) {
            event.preventDefault();
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        });
    });

    if (modalElement.getAttribute('data-profile-open') === '1') {
        bootstrap.Modal.getOrCreateInstance(modalElement).show();
    }
});
