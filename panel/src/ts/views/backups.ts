import { effect, signal } from "@preact/signals-core";
import { Notification } from "../components/ui/notification";
import { renderSpinner, type SpinnerStatus } from "../components/ui/spinner";
import { app } from "../services/app";
import { post } from "../services/request";
import { triggerDownload } from "../utils/forms";
import { $, $$ } from "../utils/selectors";

/**
 * Describes a backup sent by the server
 */
interface BackupData {
    uri: string;
    filename: string;
    date: string;
    size: string;
    deleteUri: string;
    maxFiles: number;
}

/**
 * Initialize the button to make a backup
 */
export function initBackups(view: HTMLElement) {
    const button = $<HTMLButtonElement>("[data-command=make-backup]", view);

    if (!button) {
        return;
    }

    // `null` means that no backup has been requested yet
    const status = signal<SpinnerStatus | null>(null);

    effect(() => {
        if (status.value === null) {
            return;
        }

        let spinner = $(".spinner");

        if (!spinner) {
            spinner = document.createElement("div");
            button.insertAdjacentElement("afterend", spinner);
        }

        renderSpinner(spinner, status.value);
    });

    effect(() => {
        button.disabled = status.value === "pending";
    });

    button.addEventListener("click", () => {
        status.value = "pending";

        post<BackupData>(`${app.config.baseUri}backup/make/`, {}, (response) => {
            if (response.status === "success") {
                status.value = "success";

                addBackup(response.data);
            }

            if (response.status === "error") {
                status.value = "danger";
            }

            const notification = new Notification(response.message, response.status, { icon: "checkCircle" });
            notification.show();

            if (response.status === "success") {
                window.setTimeout(() => {
                    triggerDownload(response.data.uri, app.config.csrfToken as string);
                }, 1000);
            }
        });
    });
}

/**
 * Add a backup to the table of the backups, keeping only the most recent ones
 *
 * @param data Data of the backup sent by the server
 */
function addBackup(data: BackupData) {
    const template = $("#backups-row") as HTMLTemplateElement;

    if (!template) {
        return;
    }

    const table = $("#backups-table") as HTMLTableElement;

    const node = template.content.cloneNode(true) as HTMLElement;

    ($(".backup-uri", node) as HTMLAnchorElement).href = data.uri;
    ($(".backup-uri", node) as HTMLElement).textContent = data.filename;

    ($(".backup-date", node) as HTMLElement).textContent = data.date;
    ($(".backup-size", node) as HTMLElement).textContent = data.size;
    ($(".backup-delete", node) as HTMLElement).dataset.modalAction = data.deleteUri;

    ($(".backup-last-time") as HTMLElement).textContent = app.translation.get("date.now", "Now");

    ($("tbody", table) as HTMLElement).prepend(node);

    $$("tr", table).forEach((row, index) => {
        if (index + 1 > data.maxFiles) {
            row.remove();
        }
    });

    ($("#backups-section") as HTMLDivElement).hidden = false;
}
