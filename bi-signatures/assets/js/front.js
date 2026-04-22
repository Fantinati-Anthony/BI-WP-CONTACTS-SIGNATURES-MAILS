(function($){
    'use strict';

    function feedback($el, msg, isError){
        $el.text(msg);
        if(isError){ $el.addClass('error'); } else { $el.removeClass('error'); }
        setTimeout(function(){ $el.text(''); }, 4000);
    }

    function copyHtmlToClipboard(html){
        return new Promise(function(resolve, reject){
            try{
                var container = document.createElement('div');
                container.contentEditable = 'true';
                container.style.position = 'fixed';
                container.style.left = '-9999px';
                container.innerHTML = html;
                document.body.appendChild(container);

                var range = document.createRange();
                range.selectNodeContents(container);
                var sel = window.getSelection();
                sel.removeAllRanges();
                sel.addRange(range);

                var ok = document.execCommand('copy');
                sel.removeAllRanges();
                document.body.removeChild(container);
                ok ? resolve() : reject();
            }catch(e){ reject(e); }
        });
    }

    $(document).on('click', '.bi-sig-copy', function(){
        var $actions = $(this).closest('.bi-sig-actions');
        var $sig = $actions.prev('.bi-sig-signature');
        if(!$sig.length){ $sig = $actions.siblings('.bi-sig-signature').first(); }
        var $feedback = $actions.find('.bi-sig-feedback');
        if(!$sig.length){ return; }
        copyHtmlToClipboard($sig.html()).then(function(){
            feedback($feedback, BISigFront.i18n.copied, false);
        }).catch(function(){
            feedback($feedback, BISigFront.i18n.copy_failed, true);
        });
    });

    $(document).on('click', '.bi-sig-send', function(){
        var $actions = $(this).closest('.bi-sig-actions');
        var $feedback = $actions.find('.bi-sig-feedback');
        var contactId = $actions.data('contact');
        var to = $actions.find('.bi-sig-send-to').val() || '';
        $.post(BISigFront.ajax_url, {
            action: 'bi_sig_send_email',
            nonce: BISigFront.nonce,
            contact_id: contactId,
            to: to
        }).done(function(res){
            if(res && res.success){
                feedback($feedback, res.data.message || BISigFront.i18n.send_success, false);
            } else {
                feedback($feedback, (res && res.data && res.data.message) || BISigFront.i18n.send_failed, true);
            }
        }).fail(function(){
            feedback($feedback, BISigFront.i18n.send_failed, true);
        });
    });

    $(document).on('click', '.bi-sig-template-form .bi-sig-preset-btn', function(e){
        e.preventDefault();
        var html = $(this).data('html');
        var $ta = $(this).closest('form').find('[data-bi-sig-template-content]');
        if (!$ta.length) { return; }
        if ($ta.val().replace(/\s/g,'').length > 0) {
            if (!window.confirm(BISigFront.i18n.confirm_replace || 'Remplacer le contenu actuel ?')) { return; }
        }
        $ta.val(html).trigger('change');
    });

    var previewTimer = null;
    $(document).on('input change', '.bi-sig-form input, .bi-sig-form select', function(){
        var $form = $(this).closest('.bi-sig-form');
        var $preview = $form.find('[data-preview]');
        if(!$preview.length){ return; }
        clearTimeout(previewTimer);
        previewTimer = setTimeout(function(){
            var data = {};
            $form.find('input, select').each(function(){
                var name = $(this).attr('name');
                if(name){ data[name] = $(this).val(); }
            });
            $.post(BISigFront.ajax_url, {
                action: 'bi_sig_preview',
                nonce: BISigFront.nonce,
                data: data,
                template_id: data.template_id || 0
            }).done(function(res){
                if(res && res.success){
                    $preview.html(res.data.html);
                }
            });
        }, 300);
    });

})(jQuery);
