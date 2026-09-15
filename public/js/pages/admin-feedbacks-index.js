// 搜索防抖
var searchTimer;
document.getElementById('searchInput').addEventListener('input', function() {
    clearTimeout(searchTimer);
    var form = document.getElementById('filterForm');
    searchTimer = setTimeout(function() { form.submit(); }, 500);
});
