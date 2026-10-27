/**
 * Prevent the login form from being submitted twice
 */
export function initLogin(form: HTMLElement) {
    form.addEventListener("submit", ({ submitter }) => {
        if (submitter instanceof HTMLButtonElement) {
            submitter.disabled = true;
        }
    });
}
