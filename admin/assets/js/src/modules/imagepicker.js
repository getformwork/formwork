var ImagePicker = (function() {
    $(function() {
        $('.image-input').click(function() {
            var $this = $(this);
            var value = $this.val();
            Modal.show('imagesModal', null, function($modal) {
                $modal.find('.image-picker-confirm').data('target', $this);
                $modal.find('.image-picker-thumbnail').each(function() {
                    if ($(this).data('text') == value) {
                        $(this).addClass('selected');
                        return false;
                    }
                });
            });
        });

        $('.image-picker').each(function() {
            var $this = $(this);
            var options = $this.children('option');
            var container = $('<div>', {class: 'image-picker-thumbnails'});
            for (var i = 0; i < options.length; i++) {
                $('<div>', {
                    class: 'image-picker-thumbnail',
                    'data-value': options[i].value,
                    'data-text': options[i].text
                }).css({
                    'background-image': 'url(' + options[i].value + ')'
                }).appendTo(container);
            }
            $this.before(container);
            $this.hide();
        });

        $('.image-picker-confirm').click(function() {
            $(this).data('target').val($(this).parent().find('.image-picker-thumbnail.selected').data('text'));
        });

        $('.image-picker-thumbnail').click(function() {
            $(this).siblings().removeClass('selected');
            $(this).addClass('selected');
            $(this).parent().siblings('.image-input').val($(this).data('value'));
        });
    });
})();
