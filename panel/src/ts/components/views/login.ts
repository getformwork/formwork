import { app } from "../../app";

export class Login {
    constructor() {
        app.forms["login-form"]?.element.addEventListener("submit", ({ submitter }) => {
            if (submitter instanceof HTMLButtonElement) {
                submitter.disabled = true;
            }
        });
    }
}
