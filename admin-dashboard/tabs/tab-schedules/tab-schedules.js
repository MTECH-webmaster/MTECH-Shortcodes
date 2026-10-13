document.addEventListener('DOMContentLoaded', function () {
    var togglers = document.querySelectorAll('.mtech-caret');

    togglers.forEach(function (toggler) {
        toggler.addEventListener('click', function () {
            var listItem = this.closest('li');
            if (!listItem) return;

            var nested = listItem.querySelector(':scope > .mtech-nested');
            if (nested) {
                nested.classList.toggle('mtech-active');
                this.classList.toggle('mtech-caret-open');
            }
        });
    });
});

/* ---------- Add / remove unsaved schedule rows ---------- */
document.addEventListener('DOMContentLoaded', function () {
    var template = document.getElementById('mtech-new-row-template');
    if (!template) return;

    document.addEventListener('click', function (e) {
        // Add a new blank row to this program's form
        var addBtn = e.target.closest('.mtech-add-row');
        if (addBtn) {
            var form = addBtn.closest('form.mtech-shortcode-bulk-form');
            if (!form) return;

            var container = form.querySelector('.mtech-new-rows');
            var index = parseInt(container.dataset.nextIndex, 10) || 0;
            container.dataset.nextIndex = index + 1;

            container.insertAdjacentHTML(
                'beforeend',
                template.innerHTML.replace(/__INDEX__/g, index)
            );

            var firstInput = container.lastElementChild.querySelector('input');
            if (firstInput) firstInput.focus();
            return;
        }

        // Remove a not-yet-saved row (DOM only, nothing touches the server)
        var removeBtn = e.target.closest('.mtech-remove-new-row');
        if (removeBtn) {
            var row = removeBtn.closest('.mtech-shortcode-row-new');
            if (row) row.remove();
        }
    });
});

// START marking items to be deleted
/* ---------- Mark-for-deletion styling + submit confirmation ---------- */
document.addEventListener('DOMContentLoaded', function () {
    // Toggle a class on the row when its Delete checkbox changes
    document.addEventListener('change', function (e) {
        var checkbox = e.target.closest('input[name="delete_schedules[]"]');
        if (!checkbox) return;

        var row = checkbox.closest('.mtech-shortcode-row');
        if (row) row.classList.toggle('mtech-row-marked-delete', checkbox.checked);
    });

    // Confirm before saving if any rows are marked for deletion
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches || !form.matches('form.mtech-shortcode-bulk-form')) return;

        var count = form.querySelectorAll('input[name="delete_schedules[]"]:checked').length;
        if (!count) return;

        var noun = count === 1 ? 'schedule item' : 'schedule items';
        if (!confirm('This will permanently delete ' + count + ' ' + noun + '. Continue?')) {
            e.preventDefault();
        }
    });

    // Browsers can restore checkbox state on back/refresh, so sync the styling on load
    document.querySelectorAll('input[name="delete_schedules[]"]:checked').forEach(function (checkbox) {
        var row = checkbox.closest('.mtech-shortcode-row');
        if (row) row.classList.add('mtech-row-marked-delete');
    });
});
// END marking items to be deleted