(function($){
    'use strict';

    var editor = null;

    function boot(){
        var container = document.getElementById('bi-sig-grapes');
        if (!container || typeof grapesjs === 'undefined') { return; }

        var $field = $('#' + BISigBuilder.field);
        var initial = $field.length ? $field.val() : (container.getAttribute('data-initial') || '');

        editor = grapesjs.init({
            container: '#bi-sig-grapes',
            fromElement: false,
            height: '640px',
            width: '100%',
            storageManager: false,
            assetManager: {
                upload: false,
                embedAsBase64: true
            },
            plugins: ['grapesjs-preset-newsletter'],
            pluginsOpts: {
                'grapesjs-preset-newsletter': {
                    modalTitleImport: 'Importer du HTML',
                    modalTitleExport: 'Exporter le HTML',
                    modalLabelImport: 'Collez votre HTML ci-dessous et cliquez sur Importer',
                    modalLabelExport: 'Copiez le HTML à coller dans votre client email',
                    importPlaceholder: '<table>...</table>',
                    cellStyle: {
                        'font-size': '13px',
                        'font-weight': '400',
                        'vertical-align': 'top',
                        color: '#333',
                        margin: 0,
                        padding: 0
                    }
                }
            }
        });

        if (initial) {
            editor.setComponents(initial);
        }

        window.biSigEditor = editor;
        syncField();

        editor.on('load', customizeStyleManager);

        editor.on('update', syncField);
        editor.on('component:update', syncField);
        editor.on('component:add', syncField);
        editor.on('component:remove', syncField);
        editor.on('style:update', syncField);

        $('form#post').on('submit', function(){
            syncField(true);
        });
    }

    function customizeStyleManager(){
        if (!editor) { return; }
        var sm = editor.StyleManager;
        var sector = 'decorations';

        try { sm.removeProperty(sector, 'border'); } catch (e) {}

        var styleOptions = [
            { value: 'none',   name: 'None' },
            { value: 'solid',  name: 'Solid' },
            { value: 'dashed', name: 'Dashed' },
            { value: 'dotted', name: 'Dotted' },
            { value: 'double', name: 'Double' },
            { value: 'groove', name: 'Groove' },
            { value: 'ridge',  name: 'Ridge' },
            { value: 'inset',  name: 'Inset' },
            { value: 'outset', name: 'Outset' }
        ];

        var sides = [
            { key: 'top',    label: 'Border top' },
            { key: 'right',  label: 'Border right' },
            { key: 'bottom', label: 'Border bottom' },
            { key: 'left',   label: 'Border left' }
        ];

        sides.forEach(function(s){
            sm.addProperty(sector, {
                name:     s.label,
                property: 'border-' + s.key,
                type:     'composite',
                properties: [
                    {
                        name:     'Width',
                        property: 'border-' + s.key + '-width',
                        type:     'integer',
                        units:    ['px', 'em', 'rem'],
                        defaults: '0'
                    },
                    {
                        name:     'Style',
                        property: 'border-' + s.key + '-style',
                        type:     'select',
                        defaults: 'solid',
                        list:     styleOptions,
                        options:  styleOptions
                    },
                    {
                        name:     'Color',
                        property: 'border-' + s.key + '-color',
                        type:     'color',
                        defaults: 'black'
                    }
                ]
            });
        });
    }

    function syncField(inline){
        if (!editor) { return; }
        var html;
        try {
            if (inline) {
                html = editor.runCommand('gjs-get-inlined-html');
            } else {
                html = editor.getHtml() + '<style>' + editor.getCss() + '</style>';
            }
        } catch(e) {
            html = editor.getHtml();
        }
        if (typeof html !== 'string') { html = ''; }
        $('#' + BISigBuilder.field).val(html);
    }

    function feedback(msg){
        var $fb = $('.bi-sig-var-feedback');
        $fb.text(msg).stop(true, true).fadeIn(100);
        setTimeout(function(){ $fb.fadeOut(400); }, 1800);
    }

    function copyText(text){
        return new Promise(function(resolve, reject){
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(text).then(resolve).catch(reject);
                return;
            }
            try {
                var ta = document.createElement('textarea');
                ta.value = text;
                ta.style.position = 'fixed';
                ta.style.left = '-9999px';
                document.body.appendChild(ta);
                ta.select();
                var ok = document.execCommand('copy');
                document.body.removeChild(ta);
                ok ? resolve() : reject();
            } catch(e) { reject(e); }
        });
    }

    $(document).on('click', '.bi-sig-var-chip', function(e){
        e.preventDefault();
        var token = $(this).data('token');
        copyText(token).then(function(){
            feedback(BISigBuilder.i18n.copied.replace('%s', token));
        });
    });

    $(document).on('click', '.bi-sig-preset-inject', function(e){
        e.preventDefault();
        var id = $(this).data('preset');
        if (!editor || !BISigBuilder.presets || !BISigBuilder.presets[id]) { return; }
        var current = editor.getHtml() || '';
        if (current.replace(/\s|<body>|<\/body>/g, '').length > 0) {
            if (!window.confirm(BISigBuilder.i18n.confirm_replace)) { return; }
        }
        editor.setComponents(BISigBuilder.presets[id].html);
        syncField();
    });

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }

})(jQuery);
