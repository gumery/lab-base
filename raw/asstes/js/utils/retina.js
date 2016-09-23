define('utils/retina', ['jquery'], function($) {

    if (window.devicePixelRatio >= 2) {
        
        $.fn.enableRetina = function() {

            return this.each(function(){
                var $img = $(this);

                $img.addClass('retina-ready');
       
                var image = new Image();

                var src = $img.data('retina-src');
                if (src) {
                    image.src = src;
                } else {
                    var pos = this.src.lastIndexOf('.');
                    if (pos >= 0) {
                        image.src = this.src.substr(0, pos) + '@2x' + this.src.substr(pos);
                    }
                    else {
                        image.src = this.src + '@2x';
                    }
                }
                
                function _img_loaded(img) {
                    if (!img.complete) return false;
                    if (typeof img.naturalWidth != "undefined" && img.naturalWidth == 0) {
                        return false;
                    }
                    return true;
                }
           
                $(image).load(function() {
               
                    function _replace_image() {
                        var tmpW = $img.width();
                        var tmpH = $img.height();
                        $img.attr('width', tmpW);
                        $img.attr('height', tmpH);
                        $img.attr('src', image.src);
                    }
               
                    if (_img_loaded($img[0])) {
                        _replace_image();
                    }
                    else {
                        $img.load(_replace_image);
                    }

                });

            });
        };
        
        $(document).ajaxSuccess(function () {
            $('img[data-retina]:not(.retina-ready), img[data-retina-src]:not(.retina-ready)').enableRetina();
        });

        $('img[data-retina]:not(.retina-ready), img[data-retina-src]:not(.retina-ready)').enableRetina();
    }
    
});
