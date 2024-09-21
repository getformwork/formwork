import { $ } from "../../utils/selectors";

export class FileInput {
    constructor(input: HTMLInputElement) {
        const label = $(`label[for="${input.id}"]`) as HTMLElement;
        const span = $("span", label) as HTMLElement;
        const defaultLabel = span.innerHTML ?? "";

        let isSubmitted = false;

        input.addEventListener("change", updateLabel);
        input.addEventListener("input", updateLabel);

        input.form?.addEventListener("submit", () => {
            if (input.files && input.files.length > 0) {
                span.innerHTML += ' <span class="spinner"></span>';
            }
            isSubmitted = true;
        });

        label.addEventListener("drag", preventDefault);
        label.addEventListener("dragstart", preventDefault);
        label.addEventListener("dragend", preventDefault);
        label.addEventListener("dragover", handleDragenter);
        label.addEventListener("dragenter", handleDragenter);
        label.addEventListener("dragleave", handleDragleave);

        label.addEventListener("drop", (event) => {
            event.preventDefault();
            if (isSubmitted) {
                return;
            }
            if (event.dataTransfer) {
                input.files = event.dataTransfer.files;
                // Firefox won't trigger a change event, so we explicitly do that
                input.dispatchEvent(new Event("change"));
            }
        });

        label.addEventListener("click", (event) => {
            if (isSubmitted) {
                event.preventDefault();
            }
        });

        function formatFileSize(size: number) {
            const units = ["B", "KB", "MB", "GB", "TB"];
            const exp = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
            return `${(size / 1024 ** exp).toFixed(2)} ${units[exp]}`;
        }

        function updateLabel() {
            if (input.files && input.files.length > 0) {
                const filenames: string[] = [];
                for (const file of Array.from(input.files)) {
                    filenames.push(`${file.name} <span class="file-size">(${formatFileSize(file.size)})</span>`);
                }
                span.innerHTML = filenames.join(", ");
            } else {
                span.innerHTML = defaultLabel;
            }
        }

        function preventDefault(event: Event) {
            event.preventDefault();
        }

        function handleDragenter(this: HTMLInputElement, event: DragEvent) {
            this.classList.add("drag");
            event.preventDefault();
        }

        function handleDragleave(this: HTMLInputElement, event: DragEvent) {
            this.classList.remove("drag");
            event.preventDefault();
        }
    }
}
