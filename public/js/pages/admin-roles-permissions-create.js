document.querySelectorAll('.group-check-all').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const group = this.dataset.group;
        const checked = this.checked;
        document.querySelectorAll(`.permission-check[data-group="${group}"]`).forEach(cb => {
            cb.checked = checked;
        });
    });
});

document.querySelectorAll('.permission-check').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
        const group = this.dataset.group;
        const allChecked = document.querySelectorAll(`.permission-check[data-group="${group}"]:checked`).length;
        const total = document.querySelectorAll(`.permission-check[data-group="${group}"]`).length;
        const groupCheck = document.querySelector(`.group-check-all[data-group="${group}"]`);
        if (groupCheck) {
            groupCheck.checked = allChecked === total;
            groupCheck.indeterminate = allChecked > 0 && allChecked < total;
        }
    });
});
