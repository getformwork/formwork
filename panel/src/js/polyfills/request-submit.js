// HTMLFormElement.prototype.requestSubmit polyfill
// see https://github.com/javan/form-request-submit-polyfill
if (!("requestSubmit" in window.HTMLFormElement.prototype)) {
    window.HTMLFormElement.prototype.requestSubmit = function (submitter) {
        if (submitter) {
            if (!(submitter instanceof HTMLElement)) {
                raise(TypeError, "parameter 1 is not of type 'HTMLElement'");
            }
            if (submitter.type !== "submit") {
                raise(TypeError, "The specified element is not a submit button");
            }
            if (submitter.form !== this) {
                raise(DOMException, "The specified element is not owned by this form element", "NotFoundError");
            }
            submitter.click();
        } else {
            submitter = document.createElement("input");
            submitter.type = "submit";
            submitter.hidden = true;
            this.appendChild(submitter);
            submitter.click();
            this.removeChild(submitter);
        }

        function raise(error, message, name) {
            throw new error(`Failed to execute 'requestSubmit' on 'HTMLFormElement': ${message}.`, name);
        }
    };
}
