/**
 * Gutenberg blocks registration for GPP
 */

(function(wp) {
    var registerBlockType = wp.blocks.registerBlockType;
    var el = wp.element.createElement;
    var ServerSideRender = wp.serverSideRender;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody = wp.components.PanelBody;
    var SelectControl = wp.components.SelectControl;
    var ColorPalette = wp.components.ColorPalette;
    var TextControl = wp.components.TextControl;

    registerBlockType('gpp/property-details', {
        apiVersion: 2,
        title: 'Ficha Técnica del Inmueble (GPP)',
        icon: 'admin-home',
        category: 'widgets',
        description: 'Muestra los detalles del inmueble (precio, superficie, habitaciones...) con diseño personalizable.',
        attributes: {
            layout: { type: 'string', default: 'grid' },
            columns: { type: 'string', default: '3' },
            fields: { type: 'string', default: '' },
            icon_color: { type: 'string', default: '' },
            text_color: { type: 'string', default: '' },
            label_color: { type: 'string', default: '' },
            card_background: { type: 'string', default: '' },
            card_border_radius: { type: 'string', default: '' }
        },
        edit: function(props) {
            var attributes = props.attributes;
            var setAttributes = props.setAttributes;

            return [
                el(InspectorControls, { key: 'controls' },
                    el(PanelBody, { title: 'Disposición (Layout)', initialOpen: true },
                        el(SelectControl, {
                            label: 'Estilo de Diseño',
                            value: attributes.layout,
                            options: [
                                { label: 'Cuadrícula (Grid)', value: 'grid' },
                                { label: 'Lista (List)', value: 'list' }
                            ],
                            onChange: function(value) {
                                setAttributes({ layout: value });
                            }
                        }),
                        attributes.layout === 'grid' && el(SelectControl, {
                            label: 'Columnas',
                            value: attributes.columns,
                            options: [
                                { label: '2 Columnas', value: '2' },
                                { label: '3 Columnas', value: '3' },
                                { label: '4 Columnas', value: '4' }
                            ],
                            onChange: function(value) {
                                setAttributes({ columns: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Filtrar Campos (por defecto todos)',
                            value: attributes.fields,
                            placeholder: 'precio,metros,habitaciones',
                            help: 'Nombres en español separados por coma: precio, metros, habitaciones, banos, certificacion, direccion',
                            onChange: function(value) {
                                setAttributes({ fields: value });
                            }
                        })
                    ),
                    el(PanelBody, { title: 'Estilos y Colores', initialOpen: false },
                        el('p', { style: { fontWeight: '600', marginBottom: '5px' } }, 'Color de Iconos'),
                        el(ColorPalette, {
                            value: attributes.icon_color,
                            onChange: function(value) {
                                setAttributes({ icon_color: value });
                            }
                        }),
                        el('p', { style: { fontWeight: '600', marginBottom: '5px' } }, 'Color de Etiquetas'),
                        el(ColorPalette, {
                            value: attributes.label_color,
                            onChange: function(value) {
                                setAttributes({ label_color: value });
                            }
                        }),
                        el('p', { style: { fontWeight: '600', marginBottom: '5px' } }, 'Color del Texto'),
                        el(ColorPalette, {
                            value: attributes.text_color,
                            onChange: function(value) {
                                setAttributes({ text_color: value });
                            }
                        }),
                        el('p', { style: { fontWeight: '600', marginBottom: '5px' } }, 'Fondo del Item'),
                        el(ColorPalette, {
                            value: attributes.card_background,
                            onChange: function(value) {
                                setAttributes({ card_background: value });
                            }
                        }),
                        el(TextControl, {
                            label: 'Radio de Borde (px)',
                            value: attributes.card_border_radius,
                            placeholder: '8',
                            onChange: function(value) {
                                setAttributes({ card_border_radius: value });
                            }
                        })
                    )
                ),
                el('div', { key: 'preview', className: 'gpp-gutenberg-block-preview' },
                    el(ServerSideRender, {
                        block: 'gpp/property-details',
                        attributes: attributes
                    })
                )
            ];
        },
        save: function() {
            // Server-side rendering, save is handled in PHP dynamically
            return null;
        }
    });
})(window.wp);
