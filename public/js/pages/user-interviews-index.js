(function() {
    'use strict';

    var currentQrCode = null;

    function showQrCode(interviewId) {
        fetch('/user/interviews/' + interviewId + '/qr-code', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                var container = document.getElementById('qrcode');
                container.innerHTML = '';

                currentQrCode = new QRCode(container, {
                    text: data.qr_url,
                    width: 200,
                    height: 200,
                    colorDark: '#000000',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M
                });

                var modalEl = document.getElementById('qrCodeModal');
                modalEl.classList.add('show');
                modalEl.style.display = 'block';
                modalEl.removeAttribute('aria-hidden');
                document.body.classList.add('modal-open');
                var backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                backdrop.id = 'qrCodeBackdrop';
                backdrop.addEventListener('click', hideQrCodeModal);
                document.body.appendChild(backdrop);
            } else {
                if (typeof window.appNotify === 'function') window.appNotify('生成二维码失败，请重试', 'error');
            }
        })
        .catch(function() {
            if (typeof window.appNotify === 'function') window.appNotify('生成二维码失败，请重试', 'error');
        });
    }

    function hideQrCodeModal() {
        var modalEl = document.getElementById('qrCodeModal');
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        var backdrop = document.getElementById('qrCodeBackdrop');
        if (backdrop) backdrop.remove();
    }

    // 事件委托：QR码相关按钮
    document.addEventListener('click', function(e) {
        var showBtn = e.target.closest('[data-show-qrcode]');
        if (showBtn) {
            showQrCode(showBtn.dataset.showQrcode);
            return;
        }
        var hideBtn = e.target.closest('[data-hide-qrcode]');
        if (hideBtn) {
            hideQrCodeModal();
        }
    });
})();
