/**
 * STE Leistungen – Interactive Services Section
 *
 * Interaction model:
 *   - Hover on menu item or hotspot icon → immediately previews that service
 *   - Mouse leaves the component → returns to the pinned (clicked) service
 *   - Click on menu item or hotspot icon → pins that service
 *   - Initial state: service 0 is pinned and active
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
		var pinned    = 0;   // index of the clicked/pinned service
		var current   = -1;  // index currently shown (avoid no-op guard)

		function activate(index) {
			if (index === current) return;
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

		// Hover on menu item → preview that service
		$items.on('mouseenter', function () {
			activate(parseInt($(this).data('index'), 10));
		});

		// Hover on hotspot icon → preview that service
		$hotspots.on('mouseenter', function () {
			activate(parseInt($(this).data('index'), 10));
		});

		// Click on menu item → pin that service
		$items.on('click keydown', function (e) {
			if (e.type === 'keydown' && e.which !== 13 && e.which !== 32) return;
			e.preventDefault();
			var idx = parseInt($(this).data('index'), 10);
			pinned = idx;
			current = -1; // force re-activate even if same index
			activate(idx);
		});

		// Click on hotspot icon → pin that service
		$hotspots.on('click', function (e) {
			e.preventDefault();
			var idx = parseInt($(this).data('index'), 10);
			pinned = idx;
			current = -1;
			activate(idx);
		});

		// Mouse leaves the entire component → return to pinned service
		$container.on('mouseleave', function () {
			activate(pinned);
		});

		// Initial state
		activate(0);
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
