require(['utils/global', 'jquery', 'bootstrap', 'utils/element', 'utils/pager', 'utils/dragdrop', 'utils/retina', 'utils/require']);

require(['jquery', 'bootbox'], function($, bootbox) {
	var defaults = {};
	var $meta = $('meta[name=gini-locale]');
	if ($meta.length && $meta.attr('content')) {
		defaults.locale = $meta.attr('content');
	}
	if ($.isEmptyObject(defaults)) {
		return;
	}

	bootbox.setDefaults(defaults);

	$('body').on('click', 'a[data-confirm]', function() {
		var $link = $(this);
		var href = $link.attr('href');
		bootbox.confirm({
			message: $link.data('confirm')
			,callback: function(result) {
				if (result) {
					setTimeout(function() {
						window.location.href = href;
					}, 0);
				}
			}
		});
		return false;
	});

});

require(['jquery'], function($) {

	$('body').on('click', 'a[href^="ajax:"]', function(e) {
		var $link = $(this);
		if ($link.data('delegated')) return false;

		e.preventDefault();

		$link.trigger('ajax-before');

		$.ajax({
			type: "GET"
			,url: $link.attr('href').substring(5)
			,success: function(html) {
				$link.trigger('ajax-success', html);
				$('body').append(html);
			}
			,complete: function() {
				$link.trigger('ajax-complete');
			}
		});

		return false;
	});

	$('body').on('submit', 'form[action^="ajax:"]', function(e) {
		if ($(this).data('delegated')) return false;

		e.preventDefault();

		var $form = $(this);

		$form.trigger('ajax-before');

		$.ajax({
			type: $form.attr('method') || "POST"
			,url: $form.attr('action').substring(5)
			,data: $form.serialize()
			,success: function(html) {
				$form.trigger('ajax-success', html);
				$('body').append(html);
			}
			,complete: function() {
				$form.trigger('ajax-complete');
			}
		});

		return false;
	});

	$('body').on('click', 'a[data-toggle="simple-fill"]', function(e) {
		e.preventDefault();
		var $el = $(this);
		$el.trigger('ajax-before');
		$($el.data('target')).load($el.attr('href'), function() {
			$el.trigger('ajax-complete');
		});
		return false;
	});

	$('body').on('submit', 'form[data-toggle="simple-fill"]', function(e) {
		e.preventDefault();

		var $form = $(this);

		$form.trigger('ajax-before');
		$.ajax({
			type: $form.attr('method') || "POST"
			,url: $form.attr('action')
			,data: $form.serialize()
			,success: function(html) {
				$form.trigger('ajax-success');
				$($form.data('target')).html(html);
			}
			,complete: function() {
				$form.trigger('ajax-complete');
			}
		});

		return false;
	});

	// cleanup script with data-ajax
	setInterval(function() {
		$('script[data-ajax]').remove();
	}, 1000);

});

