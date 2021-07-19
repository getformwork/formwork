import Notification from './notification';
import Request from './request';
import Utils from './utils';

export default {
    init: function () {
        var clearCacheCommand = $('[data-command=clear-cache]');
        var makeBackupCommand = $('[data-command=make-backup]');

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener('click', function () {
                Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'cache/clear/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Notification(response.message, response.status, {icon: 'check-circle'});
                    notification.show();
                });
            });
        }

        if (makeBackupCommand) {
            makeBackupCommand.addEventListener('click', function () {
                var button = this;
                button.setAttribute('disabled', '');
                Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'backup/make/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Notification(response.message, response.status, {icon: 'check-circle'});
                    notification.show();
                    setTimeout(function () {
                        if (response.status === 'success') {
                            Utils.triggerDownload(response.data.uri, $('meta[name=csrf-token]').getAttribute('content'));
                        }
                        button.removeAttribute('disabled');
                    }, 1000);
                });
            });
        }
    }
};
