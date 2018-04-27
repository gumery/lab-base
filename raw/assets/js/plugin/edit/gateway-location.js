define('plugin/edit/gateway-location', ['jquery', 'bootstrap', 'bootbox', 'bootstrap-select', 'css!../../../css/mobile-terminal.css'], function($, Bootstrap, Bootbox, BootSelect) {
    var checkMobile;
    if (typeof window.orientation == 'undefined') {
        checkMobile = false;
    }
    else {
        checkMobile = true;
    }

    var isWeixin = false;  //判断是否是微信
    var ua = navigator.userAgent.toLowerCase();
    if (ua.match(/MicroMessenger/i) == "micromessenger") {
 	isWeixin = true;
    }

    $('body').on('change', '.app-location-select-courier', function() {
        var $that = $(this);
        var kk = $that.attr('name');
        var pk = /\[([a-zA-Z0-9]*)\]/.exec(kk);
        if (pk) {
            var ki = pk[1];
        }
        var kclass = $that.attr('data-sub-class');
        if (!kclass) return;
        kclass= ['.', kclass].join('');
        var kurl = $that.attr('data-sub-url');
        var across = $that.attr('data-is-across');
        var loadingHTML = '<div><div class="control-label col-sm-3"></div><div class="col-sm-9"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div>';
        var $myContainer = $that.parents('form').find(kclass);
        if (!$myContainer.length) return;
        $myContainer.html(loadingHTML);
        var data = {
            'value': $that.val(),
            'across': across
        };
        if (undefined!==ki) {
            data['multiKey'] = ki;
        }
        $.get(kurl, data, function(result) {
            $myContainer.html(result);
            var $pm = $myContainer.parents('.modal');
            $pm.length && $pm.modal('handleUpdate');
            if (!checkMobile) {
                $('.selectpicker').selectpicker('refresh');
            }
        });
    });

    if (!checkMobile) {
        $('.selectpicker').selectpicker();
    } else if (isWeixin) {
        var myPa = $('select.app-location-select-courier').parent();
        myPa.addClass('position-tip');
        myPa.append('<i class="fa fa-chevron-right arrows-tip"></i>');
        $('div.form-group').after('<hr class="line-tip"/>');
    }

    return {
		loopMe: function() {
			if (!checkMobile) {
				$('.selectpicker').selectpicker();
			}
		}
    }
});
