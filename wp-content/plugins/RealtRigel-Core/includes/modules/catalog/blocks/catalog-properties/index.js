( function ( blocks, blockEditor, components, element, i18n, serverSideRender ) {
	var el = element.createElement;
	var __ = i18n.__;
	var useRef = element.useRef;
	var useEffect = element.useEffect;
	var useState = element.useState;
	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;
	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var SelectControl = components.SelectControl;
	var TextControl = components.TextControl;
	var Button = components.Button;
	var Notice = components.Notice;
	var ServerSideRender = serverSideRender;

	function getLegacyLayoutRules( attributes ) {
		var rules = [];
		var tabletBreakpoint = attributes.breakpointTablet || 576;
		var desktopBreakpoint = attributes.breakpointDesktop || 960;
		var tabletColumns = attributes.columnsTablet || 2;
		var desktopColumns = attributes.columnsDesktop || attributes.columns || 3;

		if ( tabletColumns > 1 ) {
			rules.push( { minWidth: tabletBreakpoint, columns: tabletColumns } );
		}

		if ( desktopColumns > 1 ) {
			rules.push( { minWidth: desktopBreakpoint, columns: desktopColumns } );
		}

		return rules;
	}

	function normalizeLayoutRules( rules ) {
		if ( ! Array.isArray( rules ) ) {
			return [];
		}

		return rules
			.map( function ( rule ) {
				return {
					minWidth: Math.max( 320, parseInt( rule && rule.minWidth, 10 ) || 320 ),
					columns: Math.min( 4, Math.max( 1, parseInt( rule && rule.columns, 10 ) || 1 ) )
				};
			} )
			.sort( function ( left, right ) {
				return left.minWidth - right.minWidth;
			} );
	}

	blocks.registerBlockType( 'realtrigel/catalog-properties', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var clientId = props.clientId;
			var previewRef = useRef( null );
			var layoutRules = normalizeLayoutRules(
				Array.isArray( attributes.layoutRules ) && attributes.layoutRules.length
					? attributes.layoutRules
					: getLegacyLayoutRules( attributes )
			);
			var hasDuplicateBreakpoints = layoutRules.some( function ( rule, index ) {
				return index > 0 && rule.minWidth === layoutRules[ index - 1 ].minWidth;
			} );
			var layoutRulesSignature = JSON.stringify( layoutRules );
			var state = useState(
				layoutRules.map( function ( rule ) {
					return String( rule.minWidth );
				} )
			);
			var draftMinWidths = state[0];
			var setDraftMinWidths = state[1];

			useEffect( function () {
				if ( attributes.blockId ) {
					return;
				}

				setAttributes( {
					blockId: 'properties_' + String( clientId || '' ).replace( /[^a-z0-9_-]/gi, '_' ).toLowerCase()
				} );
			}, [ attributes.blockId, clientId, setAttributes ] );

			useEffect( function () {
				if ( Array.isArray( attributes.layoutRules ) && attributes.layoutRules.length ) {
					return;
				}

				setAttributes( { layoutRules: layoutRules } );
			}, [ attributes.layoutRules, layoutRules, setAttributes ] );

			useEffect( function () {
				setDraftMinWidths(
					layoutRules.map( function ( rule ) {
						return String( rule.minWidth );
					} )
				);
			}, [ layoutRulesSignature ] );

			useEffect( function () {
				var canvas = previewRef.current;
				var designWidth = 1920;
				var resizeObserver;
				var mutationObserver;

				if ( ! canvas ) {
					return undefined;
				}

				function syncPreviewScale() {
					var availableWidth = canvas.clientWidth;
					var scale = availableWidth > 0 ? Math.min( 1, availableWidth / designWidth ) : 1;
					var stage = canvas.querySelector( '.rr-editor-preview-stage' );
					var heightBuffer = 140;

					canvas.style.setProperty( '--rr-editor-preview-scale', String( scale ) );

					if ( stage ) {
						canvas.style.setProperty( '--rr-editor-preview-height', String( Math.ceil( ( stage.scrollHeight + heightBuffer ) * scale ) ) + 'px' );
					}
				}

				syncPreviewScale();

				if ( window.ResizeObserver ) {
					resizeObserver = new window.ResizeObserver( syncPreviewScale );
					resizeObserver.observe( canvas );

					if ( canvas.querySelector( '.rr-editor-preview-stage' ) ) {
						resizeObserver.observe( canvas.querySelector( '.rr-editor-preview-stage' ) );
					}
				}

				if ( window.MutationObserver ) {
					mutationObserver = new window.MutationObserver( syncPreviewScale );
					mutationObserver.observe( canvas, {
						childList: true,
						subtree: true
					} );
				}

				canvas.addEventListener( 'load', syncPreviewScale, true );

				window.setTimeout( syncPreviewScale, 150 );
				window.setTimeout( syncPreviewScale, 600 );
				window.setTimeout( syncPreviewScale, 1200 );

				return function () {
					canvas.removeEventListener( 'load', syncPreviewScale, true );

					if ( resizeObserver ) {
						resizeObserver.disconnect();
					}

					if ( mutationObserver ) {
						mutationObserver.disconnect();
					}
				};
			}, [ attributes, layoutRulesSignature ] );

			function updateLayoutRule( index, key, value ) {
				if ( value === undefined || value === null || value === '' ) {
					return;
				}

				var nextRules = layoutRules.slice();
				nextRules[ index ] = Object.assign( {}, nextRules[ index ] );
				nextRules[ index ][ key ] = value;
				setAttributes( { layoutRules: normalizeLayoutRules( nextRules ) } );
			}

			function updateDraftMinWidth( index, value ) {
				var nextDrafts = draftMinWidths.slice();
				nextDrafts[ index ] = value;
				setDraftMinWidths( nextDrafts );
			}

			function commitDraftMinWidth( index ) {
				var draftValue = draftMinWidths[ index ];
				var parsedValue = parseInt( draftValue, 10 );

				if ( Number.isNaN( parsedValue ) ) {
					updateDraftMinWidth( index, String( layoutRules[ index ].minWidth ) );
					return;
				}

				updateLayoutRule( index, 'minWidth', parsedValue );
			}

			function addLayoutRule() {
				var lastRule = layoutRules.length ? layoutRules[ layoutRules.length - 1 ] : null;
				var nextRule = lastRule
					? { minWidth: lastRule.minWidth + 120, columns: Math.min( 4, lastRule.columns + 1 ) }
					: { minWidth: 576, columns: 2 };

				setAttributes( { layoutRules: normalizeLayoutRules( layoutRules.concat( [ nextRule ] ) ) } );
			}

			function removeLayoutRule( index ) {
				setAttributes( {
					layoutRules: normalizeLayoutRules(
						layoutRules.filter( function ( rule, ruleIndex ) {
							return ruleIndex !== index;
						} )
					)
				} );
			}

			return el(
				element.Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Основные настройки', 'realtrigel-core' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Заголовок', 'realtrigel-core' ),
							value: attributes.title || '',
							onChange: function ( value ) {
								setAttributes( { title: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Показывать заголовок', 'realtrigel-core' ),
							checked: !! attributes.showTitle,
							onChange: function ( value ) {
								setAttributes( { showTitle: value } );
							}
						} ),
						el( RangeControl, {
							label: __( 'Количество объектов', 'realtrigel-core' ),
							value: attributes.itemsToShow || 6,
							onChange: function ( value ) {
								setAttributes( { itemsToShow: value || 1 } );
							},
							min: 1,
							max: 24
						} ),
						el( SelectControl, {
							label: __( 'Сортировка', 'realtrigel-core' ),
							value: attributes.orderBy || 'date',
							options: [
								{ label: __( 'Сначала новые', 'realtrigel-core' ), value: 'date' },
								{ label: __( 'По заголовку', 'realtrigel-core' ), value: 'title' },
								{ label: __( 'Порядок меню', 'realtrigel-core' ), value: 'menu_order' }
							],
							onChange: function ( value ) {
								setAttributes( { orderBy: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Изображения объектов', 'realtrigel-core' ),
							value: attributes.mediaMode || 'featured',
							options: [
								{ label: __( 'Только первое изображение', 'realtrigel-core' ), value: 'featured' },
								{ label: __( 'Галерея объекта', 'realtrigel-core' ), value: 'gallery' }
							],
							onChange: function ( value ) {
								setAttributes( { mediaMode: value } );
							}
						} ),
						el( ToggleControl, {
							label: __( 'Показывать описание', 'realtrigel-core' ),
							checked: !! attributes.showExcerpt,
							onChange: function ( value ) {
								setAttributes( { showExcerpt: value } );
							}
						} ),
						el( TextControl, {
							label: __( 'Текст кнопки', 'realtrigel-core' ),
							value: attributes.buttonLabel || '',
							onChange: function ( value ) {
								setAttributes( { buttonLabel: value } );
							}
						} ),
						el( SelectControl, {
							label: __( 'Навигация', 'realtrigel-core' ),
							value: attributes.navigationMode || 'none',
							options: [
								{ label: __( 'Без пагинации', 'realtrigel-core' ), value: 'none' },
								{ label: __( 'Пагинация', 'realtrigel-core' ), value: 'pagination' },
								{ label: __( 'Бесконечная прокрутка', 'realtrigel-core' ), value: 'infinite' }
							],
							onChange: function ( value ) {
								setAttributes( { navigationMode: value } );
							}
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Адаптивная сетка', 'realtrigel-core' ), initialOpen: false },
						el(
							'div',
							{ className: 'rr-properties-layout-rules' },
							el( 'p', null, __( 'До первого брейкпоинта используется одна карточка в ряд.', 'realtrigel-core' ) ),
							layoutRules.map( function ( rule, index ) {
								return el(
									'div',
									{
										key: 'layout-rule-' + index,
										className: 'rr-properties-layout-rule'
									},
									el( TextControl, {
										label: __( 'От ширины, px', 'realtrigel-core' ),
										type: 'number',
										value: draftMinWidths[ index ] !== undefined ? draftMinWidths[ index ] : String( rule.minWidth ),
										onChange: function ( value ) {
											updateDraftMinWidth( index, value );
										},
										onBlur: function () {
											commitDraftMinWidth( index );
										},
										onKeyDown: function ( event ) {
											if ( event.key === 'Enter' ) {
												commitDraftMinWidth( index );
											}
										}
									} ),
									el( RangeControl, {
										label: __( 'Карточек в ряд', 'realtrigel-core' ),
										value: rule.columns,
										onChange: function ( value ) {
											updateLayoutRule( index, 'columns', value || 1 );
										},
										min: 1,
										max: 4
									} ),
									el(
										Button,
										{
											isDestructive: true,
											variant: 'secondary',
											onClick: function () {
												removeLayoutRule( index );
											}
										},
										__( 'Удалить правило', 'realtrigel-core' )
									)
								);
							} ),
							el(
								Button,
								{
									variant: 'primary',
									onClick: addLayoutRule
								},
								__( 'Добавить правило', 'realtrigel-core' )
							),
							hasDuplicateBreakpoints
								? el(
									Notice,
									{
										status: 'warning',
										isDismissible: false
									},
									__( 'Есть одинаковые брейкпоинты. Оставьте уникальные значения, чтобы сетка была предсказуемой.', 'realtrigel-core' )
								)
								: null
						)
					)
				),
				el(
					'div',
					useBlockProps(),
					el(
						'div',
						{
							className: 'rr-editor-preview-canvas',
							ref: previewRef
						},
						el(
							'div',
							{ className: 'rr-editor-preview-stage' },
							el( ServerSideRender, {
								block: 'realtrigel/catalog-properties',
								attributes: Object.assign( {}, attributes, {
									navigationMode: 'none'
								} )
							} )
						)
					)
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
