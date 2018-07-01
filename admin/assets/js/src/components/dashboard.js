Formwork.Dashboard = {
    init: function() {
        $('#clear-cache').click(function() {
            new Formwork.Request({
                method: 'POST',
                url: Formwork.Utils.uriPrependBase('/admin/cache/clear/', location.pathname),
                data: {'csrf-token': $('meta[name=csrf-token]').attr('content')}
            }, function(response) {
                Formwork.Notification(response.message, response.status, 5000);
            });
        });
    }
};
