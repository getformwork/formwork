Formwork.Dashboard = {
    init: function () {
        $('[data-command=clear-cache]').on('click', function () {
            new Formwork.Request({
                method: 'POST',
                url: Formwork.baseUri + 'cache/clear/',
                data: {'csrf-token': $('meta[name=csrf-token]').attr('content')}
            }, function (response) {
                Formwork.Notification(response.message, response.status, 5000);
            });
        });

        $('[data-command=make-backup]').on('click', function () {
            var $button = $(this);
            $button.attr('disabled', true);
            new Formwork.Request({
                method: 'POST',
                url: Formwork.baseUri + 'backup/make/',
                data: {'csrf-token': $('meta[name=csrf-token]').attr('content')}
            }, function (response) {
                Formwork.Notification(response.message, response.status, 5000);
                setTimeout(function () {
                    if (response.status === 'success') {
                        var csrfToken = $('meta[name=csrf-token]').attr('content');
                        Formwork.Utils.download(response.data.uri, csrfToken);
                    }
                    $button.removeAttr('disabled');
                }, 1000);
            });
        });
    }
};
