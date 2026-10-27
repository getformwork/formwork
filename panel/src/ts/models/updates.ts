import { computed, createModel, signal } from "@preact/signals-core";
import type { SpinnerStatus } from "../components/ui/spinner";
import type { PanelResponse } from "../services/request";

/**
 * Describes the result of the check for updates
 */
export interface UpdateCheck {
    uptodate: boolean;
    release: { name: string };
}

/**
 * Describes the result of the installation of an update
 */
export interface UpdateInstallation {
    status: string;
}

/**
 * Represents the phases of the updater
 */
export type UpdatePhase = "checking" | "outdated" | "current" | "installing" | "installed" | "error";

/**
 * Spinner status of each phase
 */
const spinnerStatuses: Record<UpdatePhase, SpinnerStatus> = {
    checking: "pending",
    outdated: "info",
    current: "success",
    installing: "pending",
    installed: "success",
    error: "danger",
};

/**
 * Holds the state of the updater: checking for a new version and installing it
 */
export const UpdatesModel = createModel(() => {
    const phase = signal<UpdatePhase>("checking");

    // `null` means that there is no message or release name yet
    const message = signal<string | null>(null);
    const releaseName = signal<string | null>(null);

    const spinnerStatus = computed(() => spinnerStatuses[phase.value]);
    const newVersionVisible = computed(() => phase.value === "outdated");
    const currentVersionVisible = computed(() => phase.value === "current" || phase.value === "installed");

    return {
        phase,
        message,
        releaseName,
        spinnerStatus,
        newVersionVisible,
        currentVersionVisible,

        /**
         * Set the result of the check for updates
         */
        checked(response: PanelResponse<UpdateCheck>) {
            message.value = response.message;

            if (response.status !== "success") {
                phase.value = "error";
            } else if (response.data.uptodate === false) {
                releaseName.value = response.data.release.name;
                phase.value = "outdated";
            } else {
                phase.value = "current";
            }
        },

        /**
         * Set the installation as started
         */
        installing(installingText: string) {
            phase.value = "installing";
            message.value = installingText;
        },

        /**
         * Set the result of the installation
         */
        installed(response: PanelResponse<UpdateInstallation>) {
            message.value = response.data.status;
            phase.value = response.status === "success" ? "installed" : "error";
        },
    };
});
