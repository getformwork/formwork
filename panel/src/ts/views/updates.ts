import { effect } from "@preact/signals-core";
import { Notification } from "../components/ui/notification";
import { renderSpinner } from "../components/ui/spinner";
import { type UpdateCheck, type UpdateInstallation, UpdatesModel } from "../models/updates";
import { app } from "../services/app";
import { post } from "../services/request";
import { $ } from "../utils/selectors";

/**
 * Check for updates and install them
 */
export function initUpdates(updater: HTMLElement) {
    const updateStatus = $(".update-status", updater) as HTMLElement;
    const spinner = $(".spinner", updater) as HTMLElement;
    const currentVersion = $(".current-version", updater) as HTMLElement;
    const currentVersionName = $(".current-version-name", updater) as HTMLElement;
    const newVersion = $(".new-version", updater) as HTMLElement;
    const newVersionName = $(".new-version-name", updater) as HTMLElement;
    const installCommand = $("[data-command=install-updates]", updater) as HTMLElement;

    const updates = new UpdatesModel();

    effect(() => {
        renderSpinner(spinner, updates.spinnerStatus.value);
        newVersion.style.display = updates.newVersionVisible.value ? "block" : "none";
        currentVersion.style.display = updates.currentVersionVisible.value ? "block" : "none";
    });

    effect(() => {
        if (updates.message.value !== null) {
            updateStatus.textContent = updates.message.value;
        }
    });

    effect(() => {
        if (updates.releaseName.value !== null) {
            newVersionName.textContent = updates.releaseName.value;
        }
    });

    effect(() => {
        if (updates.phase.value === "installed") {
            currentVersionName.textContent = newVersionName.textContent;
        }
    });

    window.setTimeout(() => {
        post<UpdateCheck>(`${app.config.baseUri}updates/check/`, {}, (response) => updates.checked(response));
    }, 1000);

    installCommand.addEventListener("click", () => {
        updates.installing(updateStatus.dataset.installingText as string);

        post<UpdateInstallation>(`${app.config.baseUri}updates/update/`, {}, (response) => {
            const notification = new Notification(response.message, response.status, { icon: "checkCircle" });
            notification.show();

            updates.installed(response);
        });
    });
}
