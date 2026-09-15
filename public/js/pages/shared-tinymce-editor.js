(function () {
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof tinymce === 'undefined') {
            return;
        }

        var csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        var editors = document.querySelectorAll('textarea[data-tinymce]');

        editors.forEach(function (textarea) {
            var id = textarea.id;
            var height = parseInt(textarea.dataset.tinymceHeight) || 400;
            var imageUpload = textarea.dataset.tinymceUpload === 'true';

            var config = {
                selector: '#' + id,
                language: 'zh-CN',
                height: height,
                menubar: 'edit insert view format table',
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap',
                    'preview', 'searchreplace', 'visualblocks', 'code',
                    'insertdatetime', 'table', 'wordcount', 'help',
                ],
                toolbar: [
                    'undo redo | blocks | bold italic underline strikethrough forecolor backcolor | alignleft aligncenter alignright alignjustify',
                    'bullist numlist outdent indent | link image table charmap | removeformat code preview help',
                ],
                relative_urls: false,
                remove_script_host: false,
                convert_urls: true,
                promotion: false,
                license_key: 'gpl',
            };

            if (imageUpload) {
                config.images_upload_url = '/admin/help-articles/upload-image';
                config.images_upload_credentials = true;
                config.headers = { 'X-CSRF-TOKEN': csrfToken };
                config.automatic_uploads = true;
            }

            tinymce.init(config);
        });

        // Auto-save TinyMCE content before form submit
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                tinymce.editors.forEach(function (editor) {
                    editor.save();
                });
            });
        });

        // Bridge: if a page already uses Summernote-style events, adapt them
        if (typeof window.bindTinyMceChange === 'undefined') {
            window.bindTinyMceChange = function (editorId, callback) {
                var interval = setInterval(function () {
                    var editor = tinymce.get(editorId);
                    if (editor) {
                        clearInterval(interval);
                        editor.on('change input undo redo', function () {
                            callback(editor.getContent());
                        });
                    }
                }, 200);
            };
        }
    });
})();
