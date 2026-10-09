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