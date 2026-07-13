/**
 * Gutenberg Document Sidebar integration for individual portal switches
 */

(function(wp) {
    var registerPlugin = wp.plugins.registerPlugin;
    var PluginSidebar  = wp.editPost.PluginSidebar;
    var el             = wp.element.createElement;
    var ToggleControl  = wp.components.ToggleControl;
    var PanelBody      = wp.components.PanelBody;
    var useSelect      = wp.data.useSelect;
    var useDispatch    = wp.data.useDispatch;

    function GppSyncSidebar() {
        // Fetch current meta values from the post editor store
        var meta = useSelect(function(select) {
            return select('core/editor').getEditedPostAttribute('meta') || {};
        });
        
        // Fetch dispatch action to modify post attributes
        var editPost = useDispatch('core/editor').editPost;

        // Extract values (Supporting both Boolean and raw 1/0 representation)
        var syncIdealista = meta._gpp_sync_idealista === true || meta._gpp_sync_idealista === '1' || meta._gpp_sync_idealista === 1;
        var syncFotocasa  = meta._gpp_sync_fotocasa === true || meta._gpp_sync_fotocasa === '1' || meta._gpp_sync_fotocasa === 1;
        
        var idealistaRemoteId = meta._gpp_remote_id_idealista || '';
        var fotocasaRemoteId  = meta._gpp_remote_id_fotocasa || '';

        return el(
            PluginSidebar,
            {
                name: 'gpp-sync-sidebar',
                title: 'Sincronización de Portales',
                icon: 'admin-home'
            },
            el(
                PanelBody,
                {
                    title: 'Canales de Propagación',
                    initialOpen: true
                },
                el(
                    'p',
                    { style: { fontSize: '12px', color: '#64748b', fontStyle: 'italic', marginBottom: '15px' } },
                    'Selecciona los portales inmobiliarios a los que deseas subir o actualizar este inmueble automáticamente al guardar.'
                ),
                el(
                    ToggleControl,
                    {
                        label: 'Publicar en Idealista',
                        checked: syncIdealista,
                        onChange: function(newValue) {
                            editPost({ meta: { _gpp_sync_idealista: newValue } });
                        }
                    }
                ),
                idealistaRemoteId && el(
                    'div',
                    { style: { fontSize: '11px', color: '#10b981', marginTop: '-10px', marginBottom: '15px', paddingLeft: '5px' } },
                    '✓ Conectado (ID: ' + idealistaRemoteId + ')'
                ),
                el(
                    ToggleControl,
                    {
                        label: 'Publicar en Fotocasa',
                        checked: syncFotocasa,
                        onChange: function(newValue) {
                            editPost({ meta: { _gpp_sync_fotocasa: newValue } });
                        }
                    }
                ),
                fotocasaRemoteId && el(
                    'div',
                    { style: { fontSize: '11px', color: '#10b981', marginTop: '-10px', marginBottom: '15px', paddingLeft: '5px' } },
                    '✓ Conectado (ID: ' + fotocasaRemoteId + ')'
                )
            )
        );
    }

    // Register our plugin with Gutenberg
    registerPlugin('gpp-sync-sidebar-plugin', {
        render: GppSyncSidebar
    });
})(window.wp);
