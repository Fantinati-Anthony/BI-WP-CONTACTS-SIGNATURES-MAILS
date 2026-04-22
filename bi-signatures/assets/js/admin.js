(function($){
    'use strict';

    $(document).on('click', '.bi-sig-media-btn', function(e){
        e.preventDefault();
        var $btn = $(this);
        var target = $btn.data('target');
        var frame = wp.media({
            title: BISigAdmin.i18n.pick_logo,
            button: { text: BISigAdmin.i18n.use_image },
            multiple: false,
            library: { type: 'image' }
        });
        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            $(target).val(attachment.url).trigger('change');
        });
        frame.open();
    });

    function setEditorContent(html){
        if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
            tinymce.get('content').setContent(html);
            tinymce.get('content').save();
            return true;
        }
        var $ta = $('#content');
        if ($ta.length) {
            $ta.val(html).trigger('change');
            return true;
        }
        return false;
    }

    $(document).on('click', '.bi-sig-preset-btn', function(e){
        e.preventDefault();
        var id = $(this).data('preset');
        if (!BISigAdmin.presets || !BISigAdmin.presets[id]) { return; }
        var current = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('content') && !tinymce.get('content').isHidden()) {
            current = tinymce.get('content').getContent();
        } else if ($('#content').length) {
            current = $('#content').val();
        }
        if (current && current.replace(/\s/g,'').length > 0) {
            if (!window.confirm(BISigAdmin.i18n.confirm_replace)) { return; }
        }
        setEditorContent(BISigAdmin.presets[id]);
    });

})(jQuery);
