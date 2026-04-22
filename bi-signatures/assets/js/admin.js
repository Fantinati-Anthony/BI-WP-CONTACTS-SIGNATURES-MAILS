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

})(jQuery);
