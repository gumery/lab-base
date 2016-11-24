define('plugin/edit/gateway-location', ['jquery', 'bootstrap', 'bootbox'], function($, Bootstrap, Bootbox) {
    $('body').on('change', '.app-location-select-courier', function() {
        var $that = $(this);
        var kk = $that.attr('name');
		var pk = /\[([a-zA-Z0-9]*)\]/.exec(kk);
		if (pk) {
			var ki = pk[1];
		}
        var kclass= ['.', $that.attr('data-sub-class')].join('');
        var kurl = $that.attr('data-sub-url');
        var loadingHTML = '<div><div class="control-label col-sm-3"></div><div class="col-sm-9"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div>';
        var $myContainer = $that.parents('form').find(kclass);
        if (!$myContainer.length) return;
        $myContainer.html(loadingHTML);
		var data = {
            'value': $that.val()
        };
		if (undefined!==ki) {
			data['multiKey'] = ki;
		}
        $.get(kurl, data, function(result) {
            $myContainer.html(result);
            var $pm = $myContainer.parents('.modal');
            $pm.length && $pm.modal('handleUpdate');
        });
    });
});
