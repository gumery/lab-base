define('plugin/edit/gateway-location', ['jquery', 'bootstrap', 'bootbox'], function($, Bootstrap, Bootbox) {
    $('body').on('change', '.app-location-select-courier', function() {
        var $that = $(this);
        var kk = $that.attr('name');
        var kclass= ['.', $that.attr('data-sub-class')].join('');
        var kurl = $that.attr('data-sub-url');
        var loadingHTML = '<div><div class="control-label col-sm-3"></div><div class="col-sm-9"><i class="fa fa-spinner fa-spin fa-2x"></i></div></div>';
        var $myContainer = $that.parents('form').find(kclass);
        if (!$myContainer.length) return;
        $myContainer.html(loadingHTML);
        $.get(kurl, {
            'value': $that.val()
        }, function(result) {
            $myContainer.html(result);
            var $pm = $myContainer.parents('.modal');
            $pm.length && $pm.modal('handleUpdate');
        });
    });
});
