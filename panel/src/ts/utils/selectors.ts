export function $(selector: string, parent: ParentNode = document): HTMLElement | null {
    return parent.querySelector(selector);
}

export function $$(selector: string, parent: ParentNode = document): NodeListOf<HTMLElement> {
    return parent.querySelectorAll(selector);
}
