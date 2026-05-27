export function $<T extends HTMLElement = HTMLElement>(selector: string, parent: ParentNode = document): T | null {
    return parent.querySelector<T>(selector);
}

export function $$<T extends HTMLElement = HTMLElement>(selector: string, parent: ParentNode = document): NodeListOf<T> {
    return parent.querySelectorAll<T>(selector);
}
