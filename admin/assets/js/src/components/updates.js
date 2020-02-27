Formwork.Updates = {
    init: function () {
        var updaterComponent = document.getElementById('updater-component');
        var updateStatus, spinner,
            currentVersion, currentVersionName,
            newVersion, newVersionName;

        if (updaterComponent) {
            updateStatus = $('.update-status');
            spinner = $('.spinner');
            currentVersion = $('.current-version');
            currentVersionName = $('.current-version-name');
            newVersion = $('.new-version');
            newVersionName = $('.new-version-name');

            setTimeout(function () {
                var data = {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')};

                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'updates/check/',
                    data: data
                }, function (response) {
                    updateStatus.innerHTML = response.message;

                    if (response.status === 'success') {
                        if (response.data.uptodate === false) {
                            showNewVersion(response.data.release.name);
                        } else {
                            showCurrentVersion();
                        }
                    } else {
                        spinner.classList.add('spinner-error');
                    }
                });
            }, 1000);

            $('[data-command=install-updates]').addEventListener('click', function () {
                newVersion.style.display = 'none';
                spinner.classList.remove('spinner-info');
                updateStatus.innerHTML = updateStatus.getAttribute('data-installing-text');

                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'updates/update/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Formwork.Notification(response.message, response.status, 5000);
                    notification.show();

                    updateStatus.innerHTML = response.data.status;

                    if (response.status === 'success') {
                        showInstalledVersion();
                    } else {
                        spinner.classList.add('spinner-error');
                    }
                });
            });
        }

        function showNewVersion(name) {
            spinner.classList.add('spinner-info');
            newVersionName.innerHTML = name;
            newVersion.style.display = 'block';
        }

        function showCurrentVersion() {
            spinner.classList.add('spinner-success');
            currentVersion.style.display = 'block';
        }

        function showInstalledVersion() {
            spinner.classList.add('spinner-success');
            currentVersionName.innerHTML = newVersionName.innerHTML;
            currentVersion.style.display = 'block';
        }
    }
};
