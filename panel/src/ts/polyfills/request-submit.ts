// HTMLFormElement.prototype.requestSubmit polyfill
// see https://github.com/javan/form-request-submit-polyfill
if (typeof window.HTMLFormElement.prototype.requestSubmit === "undefined") {
    window.HTMLFormElement.prototype.requestSubmit = function (submitter: HTMLInputElement) {
        if (submitter) {
            if (!(submitter instanceof HTMLElement)) {
                throw new TypeError("Failed to execute 'requestSubmit' on 'HTMLFormElement': parameter 1 is not of type 'HTMLElement'.");
            }
            if (submitter.type !== "submit") {
                throw new TypeError("Failed to execute 'requestSubmit' on 'HTMLFormElement': the specified element is not a submit button.");
            }
            if (submitter.form !== this) {
                throw new DOMException("Failed to execute 'requestSubmit' on 'HTMLFormElement': the specified element is not owned by this form element.", "NotFoundError");
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
    };
}
