document.addEventListener('DOMContentLoaded', function () {
    if (typeof tinymce === 'undefined') {
        return;
    }

    var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

    tinymce.init({
        selector: '#help-content-editor',
        language: 'zh-CN',
        height: 500,
        menubar: 'edit insert view format table',
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
            'preview', 'searchreplace', 'visualblocks', 'code',
            'insertdatetime', 'table', 'wordcount', 'help',
        ],
        toolbar: [
            'undo redo | blocks | bold italic forecolor | alignleft aligncenter alignright | bullist numlist outdent indent',
            'link image table | removeformat code help',
        ],
        images_upload_url: '/admin/help-articles/upload-image',
        images_upload_credentials: true,
        headers: {
            'X-CSRF-TOKEN': csrfToken,
        },
        automatic_uploads: true,
        content_css: '/css/pages/help-article-content.css',
        relative_urls: false,
        remove_script_host: false,
        convert_urls: true,
    });

    var form = document.querySelector('form[data-help-article-form]');
    if (form) {
        form.addEventListener('submit', function () {
            var editor = tinymce.get('help-content-editor');
            if (editor) {
                editor.save();
            }
        });
    }
});
