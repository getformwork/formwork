/**
 * Prevent the login form from being submitted twice
 *
 * The button is enabled again when the page is shown from the history, e.g. going back
 */
export function initLogin(form: HTMLElement) {
    let submitter: HTMLButtonElement | null = null;

    form.addEventListener("submit", (event) => {
        if (event.submitter instanceof HTMLButtonElement) {
            submitter = event.submitter;
            submitter.disabled = true;
        }
    });

    window.addEventListener("pageshow", (event) => {
        if (event.persisted && submitter) {
            submitter.disabled = false;
            submitter = null;
        }
    });
}
