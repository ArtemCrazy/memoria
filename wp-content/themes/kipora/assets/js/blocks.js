/**
 * Editor side of the KIPORA blocks (fields and their names come from
 * blocks/<name>/block.json, the site markup from blocks/<name>/render.php).
 *
 * Plain script without a build step, so any WordPress developer can change it.
 * In the editor a block is drawn with the same classes as on the site, so the
 * theme styles make it look the same; texts are edited in place, photos are
 * replaced by clicking them, links and alt texts are in the sidebar.
 */
(function (wp, cfg) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var registerBlockType = wp.blocks.registerBlockType;
	var be = wp.blockEditor;
	var c = wp.components;
	var __ = wp.i18n.__;

	var CONTAINER = function () {
		return el(be.InnerBlocks.Content);
	};
	var NOTHING = function () {
		return null;
	};

	var WAVE = 'M1 3c5.7-3 11.3 3 17 0s11.3-3 17 0 11.3 3 17 0 11.3-3 16 0';

	function set(props, name) {
		return function (value) {
			var change = {};
			change[name] = value;
			props.setAttributes(change);
		};
	}

	/**
	 * Text edited in place. Italic (Ctrl+I) is the only formatting, and only
	 * where the design has italic accents (headings, quotes).
	 */
	function text(props, name, tag, className, placeholder, italic) {
		return el(be.RichText, {
			tagName: tag,
			className: className,
			value: props.attributes[name],
			onChange: set(props, name),
			placeholder: placeholder,
			allowedFormats: italic ? ['core/italic'] : [],
			withoutInteractiveFormatting: true
		});
	}

	function wave(className) {
		return el('svg', { className: className, viewBox: '0 0 69 6', 'aria-hidden': true },
			el('path', { d: WAVE, fill: 'none', stroke: 'currentColor', strokeWidth: 1.5, strokeLinecap: 'round' }));
	}

	function heading(props, name, modifier, placeholder) {
		return el('header', { className: 'section-head ' + (modifier || '') },
			wave('section-head__divider'),
			text(props, name, 'h2', 'section-head__title', placeholder || __('Heading. Select words and press Ctrl+I for italic', 'kipora'), true));
	}

	function pickImage(props, name) {
		return function (media) {
			var large = media.sizes && media.sizes.large ? media.sizes.large.url : media.url;
			var value = {};
			value[name] = { id: media.id, url: large, alt: media.alt || '' };
			props.setAttributes(value);
		};
	}

	/** Photo of a block: click to choose another one from the media library. */
	function photo(props, name, fallback, className) {
		var image = props.attributes[name] || {};
		var src = image.url || (fallback ? cfg.img + fallback + '.jpg' : '');
		var picture = function (open) {
			return el('picture', {
				className: className + (open ? ' kp-media' : ''),
				onClick: open,
				title: open ? __('Click to replace the photo', 'kipora') : undefined
			}, src ? el('img', { src: src, alt: image.alt || '' }) : null);
		};
		return el(be.MediaUploadCheck, { fallback: picture() },
			el(be.MediaUpload, {
				allowedTypes: ['image'],
				value: image.id,
				onSelect: pickImage(props, name),
				render: function (o) {
					return picture(o.open);
				}
			}));
	}

	/** Sidebar controls of a photo: replace, restore the original, alt text. */
	function photoControls(props, name, label) {
		var image = props.attributes[name] || {};
		return el(c.BaseControl, { label: label, __nextHasNoMarginBottom: true },
			el('div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginBottom: '12px' } },
				el(be.MediaUploadCheck, null,
					el(be.MediaUpload, {
						allowedTypes: ['image'],
						value: image.id,
						onSelect: pickImage(props, name),
						render: function (o) {
							return el(c.Button, { variant: 'secondary', onClick: o.open }, __('Choose photo', 'kipora'));
						}
					})),
				image.id ? el(c.Button, {
					variant: 'tertiary',
					isDestructive: true,
					onClick: function () {
						set(props, name)({ alt: image.alt || '' });
					}
				}, __('Restore the original', 'kipora')) : null),
			el(c.TextControl, {
				label: __('What is in the photo (for screen readers and search)', 'kipora'),
				value: image.alt || '',
				onChange: function (alt) {
					set(props, name)(Object.assign({}, image, { alt: alt }));
				},
				__nextHasNoMarginBottom: true
			}));
	}

	function linkControl(props, name, label, help) {
		return el(c.TextControl, {
			label: label,
			help: help,
			value: props.attributes[name],
			onChange: set(props, name),
			type: 'url',
			__nextHasNoMarginBottom: true
		});
	}

	function sidebar() {
		var panels = Array.prototype.slice.call(arguments);
		return el(be.InspectorControls, null, panels);
	}

	function panel(title) {
		var children = Array.prototype.slice.call(arguments, 1);
		return el(c.PanelBody, { title: title, initialOpen: true }, children);
	}

	var CALC_HELP = __('Leave empty to open the price calculator in the language of the page.', 'kipora');

	// First screen.
	registerBlockType('kipora/hero', {
		edit: function (props) {
			var a = props.attributes;
			var image = a.image || {};
			var video = a.video || {};
			var videoUrl = video.url || (!image.id && a.fallback === 'hero-lantern' ? cfg.video : '');
			var cards = be.useInnerBlocksProps({ className: 'hero__cards' }, {
				allowedBlocks: ['kipora/hero-card'],
				orientation: 'horizontal',
				template: [['kipora/hero-card', { direction: 'grave' }], ['kipora/hero-card', { direction: 'pet' }]]
			});
			return el(Fragment, null,
				sidebar(
					panel(__('Photo and video', 'kipora'),
						photoControls(props, 'image', __('Photo', 'kipora')),
						el(c.BaseControl, { label: __('Video over the photo', 'kipora'), help: __('A short muted loop. Without a video of your own, the original photo shows the candle video.', 'kipora'), __nextHasNoMarginBottom: true },
							el('div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
								el(be.MediaUploadCheck, null, el(be.MediaUpload, {
									allowedTypes: ['video'],
									value: video.id,
									onSelect: function (m) {
										props.setAttributes({ video: { id: m.id, url: m.url } });
									},
									render: function (o) {
										return el(c.Button, { variant: 'secondary', onClick: o.open }, __('Choose video', 'kipora'));
									}
								})),
								video.id ? el(c.Button, { variant: 'tertiary', isDestructive: true, onClick: function () { props.setAttributes({ video: {} }); } }, __('Remove video', 'kipora')) : null))),
					panel(__('Button', 'kipora'), linkControl(props, 'buttonUrl', __('Button link', 'kipora'), CALC_HELP))
				),
				el('section', be.useBlockProps({ className: 'hero kp-block' }),
					el('div', { className: 'hero__head' },
						text(props, 'features', 'p', 'hero__features', __('Line above the headline, e.g. the area you work in', 'kipora')),
						wave('hero__divider'),
						text(props, 'title', 'h1', 'hero__title', __('Headline. Select words and press Ctrl+I for italic', 'kipora'), true),
						text(props, 'lead', 'p', 'hero__lead', __('One or two sentences under the headline', 'kipora')),
						text(props, 'buttonText', 'span', 'kp-button kp-button--primary kp-button--large hero__cta', __('Button text', 'kipora'))),
					el('div', { className: 'hero__stage' },
						el('div', { className: 'hero__figure' },
							el('div', { className: 'hero__photo' },
								photo(props, 'image', a.fallback, 'hero__still'),
								videoUrl ? el('video', { className: 'hero__video', src: videoUrl, autoPlay: true, muted: true, loop: true, playsInline: true }) : null)),
						el('div', cards)))
			);
		},
		save: CONTAINER
	});

	registerBlockType('kipora/hero-card', {
		edit: function (props) {
			var a = props.attributes;
			var direction = a.direction === 'pet' ? 'pet' : 'grave';
			return el(Fragment, null,
				sidebar(panel(__('Service card', 'kipora'),
					el(c.SelectControl, {
						label: __('Opens the calculator with', 'kipora'),
						value: direction,
						options: [
							{ label: __('Grave care', 'kipora'), value: 'grave' },
							{ label: __('Pets', 'kipora'), value: 'pet' }
						],
						onChange: set(props, 'direction'),
						__nextHasNoMarginBottom: true
					}),
					linkControl(props, 'url', __('Own link instead', 'kipora'), __('Leave empty to open the calculator.', 'kipora')),
					photoControls(props, 'image', __('Photo', 'kipora')))),
				el('div', be.useBlockProps({ className: 'hero-card hero-card--' + direction }),
					photo(props, 'image', a.fallback || 'card-' + direction, 'hero-card__photo'),
					el('span', { className: 'hero-card__body' },
						text(props, 'title', 'span', 'hero-card__title', __('Card title', 'kipora')),
						text(props, 'text', 'span', 'hero-card__text', __('Short text', 'kipora'))))
			);
		},
		save: NOTHING
	});

	// One line of a numbered list (inside "Photo and list" and "List and photo with a card").
	registerBlockType('kipora/point', {
		edit: function (props) {
			return el('li', be.useBlockProps({ className: 'numbered__item' }),
				el('span', { className: 'numbered__mark', 'aria-hidden': true }),
				text(props, 'text', 'span', 'numbered__text', __('List item', 'kipora')));
		},
		save: NOTHING
	});

	function points(className, count) {
		var template = [];
		for (var i = 0; i < count; i++) {
			template.push(['kipora/point']);
		}
		return be.useInnerBlocksProps({ className: className, role: 'list' }, { allowedBlocks: ['kipora/point'], template: template });
	}

	registerBlockType('kipora/problem', {
		edit: function (props) {
			var a = props.attributes;
			return el(Fragment, null,
				sidebar(panel(__('Photo', 'kipora'), photoControls(props, 'image', __('Photo', 'kipora')))),
				el('section', be.useBlockProps({ className: 'problem kp-block' }),
					el('div', { className: 'problem__media' }, photo(props, 'image', a.fallback, 'problem__photo')),
					el('div', { className: 'problem__content' },
						heading(props, 'title'),
						el('ol', points('numbered', 3))),
					text(props, 'quote', 'p', 'quote problem__quote', __('Quote under the section. Ctrl+I for italic', 'kipora'), true))
			);
		},
		save: CONTAINER
	});

	registerBlockType('kipora/steps', {
		edit: function (props) {
			var list = be.useInnerBlocksProps({ className: 'steps__list', role: 'list' }, {
				allowedBlocks: ['kipora/step'],
				orientation: 'horizontal',
				template: [['kipora/step'], ['kipora/step'], ['kipora/step']]
			});
			return el('section', be.useBlockProps({ className: 'steps kp-block' }),
				el('div', { className: 'steps__top' }, heading(props, 'title', 'section-head--left')),
				el('p', { className: 'rule-label' }, text(props, 'label', 'span', 'rule-label__text', __('Label between the lines', 'kipora'), true)),
				el('ol', list));
		},
		save: CONTAINER
	});

	// "Step %s" with the number where the translation puts it (Estonian: "1. samm").
	function stepLabel() {
		var parts = __('Step %s', 'kipora').split('%s');
		return [parts[0], el('span', { key: 'n', className: 'step__number' }), parts[1] || ''];
	}

	registerBlockType('kipora/step', {
		edit: function (props) {
			var a = props.attributes;
			return el(Fragment, null,
				sidebar(panel(__('Photo', 'kipora'), photoControls(props, 'image', __('Photo', 'kipora')))),
				el('li', be.useBlockProps({ className: 'step' }),
					photo(props, 'image', a.fallback || 'step-1', 'step__photo'),
					el('p', { className: 'step__label' }, stepLabel()),
					text(props, 'title', 'h3', 'step__title', __('Step title', 'kipora')),
					text(props, 'text', 'p', 'step__text', __('What happens at this step', 'kipora')))
			);
		},
		save: NOTHING
	});

	registerBlockType('kipora/compare', {
		edit: function (props) {
			var rows = be.useInnerBlocksProps({ className: 'kp-compare-rows' }, {
				allowedBlocks: ['kipora/compare-row'],
				template: [['kipora/compare-row'], ['kipora/compare-row'], ['kipora/compare-row']]
			});
			return el(Fragment, null,
				sidebar(panel(__('Button', 'kipora'), linkControl(props, 'buttonUrl', __('Button link', 'kipora'), CALC_HELP))),
				el('section', be.useBlockProps({ className: 'compare kp-block' }),
					heading(props, 'title', 'section-head--center'),
					el('div', { className: 'kp-compare-heads' },
						el('span', null, __('What is compared', 'kipora')),
						text(props, 'baseTitle', 'span', '', __('First column', 'kipora')),
						text(props, 'brandTitle', 'span', 'kp-compare-row__brand', __('Second column', 'kipora'))),
					el('div', rows),
					el('div', { className: 'compare__cta' },
						text(props, 'quote', 'p', 'quote', __('Text under the comparison. Ctrl+I for italic', 'kipora'), true),
						text(props, 'buttonText', 'span', 'kp-button kp-button--primary', __('Button text', 'kipora'))))
			);
		},
		save: CONTAINER
	});

	registerBlockType('kipora/compare-row', {
		edit: function (props) {
			return el('div', be.useBlockProps({ className: 'kp-compare-row' }),
				text(props, 'label', 'span', 'kp-compare-row__label', __('What is compared', 'kipora')),
				text(props, 'base', 'span', 'kp-compare-row__base', __('First column', 'kipora')),
				text(props, 'brand', 'span', 'kp-compare-row__brand', __('Second column', 'kipora')));
		},
		save: NOTHING
	});

	registerBlockType('kipora/memory', {
		edit: function (props) {
			var a = props.attributes;
			return el(Fragment, null,
				sidebar(panel(__('Photos', 'kipora'),
					photoControls(props, 'image', __('Large photo', 'kipora')),
					photoControls(props, 'cardImage', __('Photo on the small card', 'kipora')))),
				el('section', be.useBlockProps({ className: 'memory kp-block' }),
					el('div', { className: 'memory__content' },
						heading(props, 'title'),
						text(props, 'lead', 'p', 'memory__lead', __('Text above the list', 'kipora')),
						el('ol', points('numbered numbered--large', 3))),
					el('div', { className: 'memory__media' },
						photo(props, 'image', a.fallback, 'memory__photo'),
						el('div', { className: 'memory__card' },
							photo(props, 'cardImage', a.cardFallback, 'memory__card-photo'),
							text(props, 'cardText', 'p', 'memory__card-text', __('Text of the small card', 'kipora')))))
			);
		},
		save: CONTAINER
	});

	registerBlockType('kipora/faq', {
		edit: function (props) {
			var list = be.useInnerBlocksProps({ className: 'faq__list' }, {
				allowedBlocks: ['kipora/faq-item'],
				template: [['kipora/faq-item'], ['kipora/faq-item'], ['kipora/faq-item']]
			});
			return el(Fragment, null,
				sidebar(panel(__('Link', 'kipora'), linkControl(props, 'linkUrl', __('Link under the questions', 'kipora'), __('Leave empty to open the contact page in the language of the page.', 'kipora')))),
				el('section', be.useBlockProps({ className: 'faq kp-block' }),
					heading(props, 'title', 'section-head--center'),
					el('div', list),
					el('p', { className: 'faq__more' },
						text(props, 'moreText', 'span', '', __('Text before the link', 'kipora')),
						' ',
						text(props, 'linkText', 'span', 'kp-link', __('Link text', 'kipora'))))
			);
		},
		save: CONTAINER
	});

	// In the editor the answer is always shown, so it can be edited.
	registerBlockType('kipora/faq-item', {
		edit: function (props) {
			return el('div', be.useBlockProps({ className: 'faq__item' }),
				el('div', { className: 'faq__question' },
					text(props, 'question', 'span', '', __('Question', 'kipora')),
					el('span', { className: 'faq__icon', 'aria-hidden': true })),
				text(props, 'answer', 'p', 'faq__answer', __('Answer', 'kipora')));
		},
		save: NOTHING
	});
}(window.wp, window.kiporaBlocks || {}));
