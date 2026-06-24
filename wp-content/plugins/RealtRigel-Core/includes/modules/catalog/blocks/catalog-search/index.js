( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var TextControl = components.TextControl;
	var ToggleControl = components.ToggleControl;
	var ServerSideRender = serverSideRender;

	blocks.registerBlockType( 'realtrigel/catalog-search', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Настройки поиска', 'realtrigel-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Заголовок', 'realtrigel-core' ),
							value: attributes.title || '',
							onChange: function ( value ) {
								setAttributes( { title: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Подсказка быстрого поиска', 'realtrigel-core' ),
							value: attributes.searchPlaceholder || '',
							onChange: function ( value ) {
								setAttributes( { searchPlaceholder: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Текст кнопки', 'realtrigel-core' ),
							value: attributes.buttonLabel || '',
							onChange: function ( value ) {
								setAttributes( { buttonLabel: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Текст "Больше фильтров"', 'realtrigel-core' ),
							value: attributes.moreFiltersLabel || '',
							onChange: function ( value ) {
								setAttributes( { moreFiltersLabel: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Текст "Меньше фильтров"', 'realtrigel-core' ),
							value: attributes.lessFiltersLabel || '',
							onChange: function ( value ) {
								setAttributes( { lessFiltersLabel: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'URL страницы каталога', 'realtrigel-core' ),
							help: __( 'Если оставить пустым, форма отправится на текущую страницу.', 'realtrigel-core' ),
							value: attributes.targetUrl || '',
							onChange: function ( value ) {
								setAttributes( { targetUrl: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Показывать кнопку сброса', 'realtrigel-core' ),
							checked: !! attributes.showResetButton,
							onChange: function ( value ) {
								setAttributes( { showResetButton: value } );
							}
						} )
					)
				),
				el(
					'div',
					useBlockProps(),
					el( ServerSideRender, {
						block: 'realtrigel/catalog-search',
						attributes: attributes
					} )
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )(
	window.wp.blocks,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.element,
	window.wp.i18n,
	window.wp.serverSideRender
);
