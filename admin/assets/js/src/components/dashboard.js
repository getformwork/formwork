Formwork.Dashboard = {
    init: function () {
        var clearCacheCommand = $('[data-command=clear-cache]');
        var makeBackupCommand = $('[data-command=make-backup]');

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener('click', function () {
                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'cache/clear/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Formwork.Notification(response.message, response.status, 5000);
                    notification.show();
                });
            });
        }

        if (makeBackupCommand) {
            makeBackupCommand.addEventListener('click', function () {
                var button = this;
                button.setAttribute('disabled', '');
                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'backup/make/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Formwork.Notification(response.message, response.status, 5000);
                    notification.show();
                    setTimeout(function () {
                        if (response.status === 'success') {
                            Formwork.Utils.triggerDownload(response.data.uri, $('meta[name=csrf-token]').getAttribute('content'));
                        }
                        button.removeAttribute('disabled');
                    }, 1000);
                });
            });
        }
    }
};
