/**
 * STE Leistungen – Interactive Services Section
 *
 * Bidirectional hover/click sync between service list and hotspot icons.
 * Detail panel is overlaid inside the locomotive graphic area.
 */
(function ($) {
	'use strict';

	function initLeistungen($container) {
		if (!$container.length || $container.data('ste-init')) return;
		$container.data('ste-init', true);

		var data      = window.steLeistungenData || {};
		var services  = data.services || [];
		var $items    = $container.find('.ste-leistungen__item');
		var $hotspots = $container.find('.ste-leistungen__hotspot');
		var $detail   = $container.find('.ste-leistungen__detail');
		var iconUrl   = $container.attr('data-icon-url') || '';
		var locked    = true;
		var current   = 0;

		function activate(index) {
			if (index === current && locked) return;
			current = index;

			$items.removeClass('is-active');
			$hotspots.removeClass('is-active');

			$items.filter('[data-index="' + index + '"]').addClass('is-active');
			$hotspots.filter('[data-index="' + index + '"]').addClass('is-active');

			updateDetail(index);
		}

		function updateDetail(index) {
			var s = services[index];
			if (!s) return;

			var html = '<div class="ste-leistungen__detail-main">';
			html += '<h3 class="ste-leistungen__detail-title">' + escHtml(s.title) + '</h3>';
			html += '<p class="ste-leistungen__detail-desc">' + escHtml(s.description) + '</p>';
			html += '</div>';

			if (s.tools && s.tools.length) {
				html += '<div class="ste-leistungen__detail-tools">';
				html += '<strong>Tools</strong>';
				html += '<span>' + escHtml(s.tools.join(', ')) + '</span>';
				html += '</div>';
			}

			if (s.references && s.references.length) {
				html += '<div class="ste-leistungen__detail-refs">';
				html += '<span class="ste-leistungen__detail-refs-label">Referenzen</span>';
				for (var i = 0; i < s.references.length; i++) {
					html += '<span class="ste-leistungen__ref">';
					html += '<span class="ste-leistungen__ref-icon-wrap">';
					if (iconUrl) {
						html += '<img src="' + iconUrl + '" alt="" class="ste-leistungen__ref-icon" width="24" height="24" />';
					}
					html += '</span>';
					html += '<span class="ste-leistungen__ref-label">' + escHtml(s.references[i]) + '</span>';
					html += '</span>';
				}
				html += '</div>';
			}

			$detail.html(html);
		}

		function escHtml(str) {
			var div = document.createElement('div');
			div.appendChild(document.createTextNode(str));
			return div.innerHTML;
		}

		// --- Event handlers ---

		$items.on('mouseenter', function () {
			if (locked) return;
			activate(parseInt($(this).data('index'), 10));
		});

		$hotspots.on('mouseenter', function () {
			if (locked) return;
			activate(parseInt($(this).data('index'), 10));
		});

		$items.on('click keydown', function (e) {
			if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) return;
			e.preventDefault();
			var idx = parseInt($(this).data('index'), 10);
			if (locked && idx === current) {
				locked = false;
			} else {
				locked = true;
				activate(idx);
			}
		});

		$hotspots.on('click', function (e) {
			e.preventDefault();
			var idx = parseInt($(this).data('index'), 10);
			locked = true;
			activate(idx);
		});

		$container.on('mouseleave', function () {
			if (!locked) {
				activate(0);
				locked = true;
			}
		});

		// Initial state
		activate(0);
		locked = true;
	}

	$(function () {
		$('.ste-leistungen').each(function () {
			initLeistungen($(this));
		});
	});

	$(document).on('action.init_hidden_elements', function (e, container) {
		var $el = $(container).find('.ste-leistungen');
		if ($el.length) {
			initLeistungen($el);
		}
	});

})(jQuery);
