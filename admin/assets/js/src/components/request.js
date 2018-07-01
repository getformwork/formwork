Formwork.Request = function(options, callback) {
    var request = $.ajax(options);
    if (typeof callback === 'function') {
        request.always(function() {
            var response = request.responseJSON || {};
            var code = response.code || request.status;
            if (code == 403) {
                location.reload();
            } else {
                callback(response, request);
            }
        });
    }
    return request;
};
