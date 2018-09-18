Formwork.Updates = {
    init: function() {
        if ($('#updater-component').length > 0) {
            setTimeout(function() {
                var data = {
                    'csrf-token': $('meta[name=csrf-token]').attr('content')
                };
                new Formwork.Request({
                    method: 'POST',
                    url: Formwork.Utils.uriPrependBase('/admin/updates/check/', location.pathname),
                    data: data
                }, function(response) {
                    $('.update-status').html(response.message);
                    if (response.data.uptodate === false) {
                        $('.spinner').addClass('spinner-info');
                        $('.new-version-name').text(response.data.release.name);
                        $('.new-version').show();
                    } else {
                        $('.spinner').addClass('spinner-success');
                        $('.current-version').show();
                    }
                });
            }, 1000);

            $('[data-command=install-updates]').click(function() {
                $('.new-version').hide();
                $('.spinner').removeClass('spinner-info');
                $('.update-status').text($('.update-status').data('installing-text'));
                var data = {
                    'csrf-token': $('meta[name=csrf-token]').attr('content')
                };
                new Formwork.Request({
                    method: 'POST',
                    url: Formwork.Utils.uriPrependBase('/admin/updates/update/', location.pathname),
                    data: data
                }, function(response) {
                    $('.update-status').text(response.data.status);
                    new Formwork.Notification(response.message, response.status, 5000);
                    if (response.status == 'success') {
                        $('.spinner').addClass('spinner-success');
                        $('.current-version-name').text($('.new-version-name').text());
                        $('.current-version').show();
                    } else {
                        $('.spinner').addClass('spinner-error');
                    }
                });
            });
        }
    }
};
