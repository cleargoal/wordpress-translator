/**
 * Language Switcher Gutenberg Block
 *
 * @package WP_Smart_Translation_Engine
 */

(function(wp) {
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;
	var el = wp.element.createElement;

	registerBlockType('wpste/language-switcher', {
		title: 'Language Switcher',
		description: 'Display a language switcher for multilingual content',
		icon: 'translation',
		category: 'widgets',
		attributes: {
			style: {
				type: 'string',
				default: 'dropdown'
			},
			showFlags: {
				type: 'boolean',
				default: true
			},
			showNames: {
				type: 'boolean',
				default: true
			}
		},

		edit: function(props) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			return [
				el(
					InspectorControls,
					{key: 'inspector'},
					el(
						PanelBody,
						{title: 'Language Switcher Settings', initialOpen: true},
						el(SelectControl, {
							label: 'Display Style',
							value: attributes.style,
							options: [
								{label: 'Dropdown', value: 'dropdown'},
								{label: 'Flags/Links', value: 'flags'}
							],
							onChange: function(value) {
								setAttributes({style: value});
							}
						}),
						el(ToggleControl, {
							label: 'Show Flags',
							checked: attributes.showFlags,
							onChange: function(value) {
								setAttributes({showFlags: value});
							}
						}),
						el(ToggleControl, {
							label: 'Show Language Names',
							checked: attributes.showNames,
							onChange: function(value) {
								setAttributes({showNames: value});
							}
						})
					)
				),
				el(ServerSideRender, {
					key: 'server-side-render',
					block: 'wpste/language-switcher',
					attributes: attributes
				})
			];
		},

		save: function() {
			// Server-side rendering, no need to save
			return null;
		}
	});

})(window.wp);
