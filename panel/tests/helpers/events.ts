/**
 * Get an element of the document by its id
 */
export const byId = (id: string) => document.getElementById(id) as HTMLElement;

/**
 * Options of a key pressed with `press()`
 */
interface PressOptions extends KeyboardEventInit {
    /**
     * Element receiving the event, the focused one by default
     */
    target?: Element;
    type?: "keydown" | "keyup";
}

/**
 * Press a key, dispatching a `keydown` event by default
 *
 * @returns The event, to check whether its default action was prevented
 */
export function press(key: string, { target = document.activeElement ?? document.body, type = "keydown", ...init }: PressOptions = {}) {
    const event = new KeyboardEvent(type, { key, bubbles: true, cancelable: true, ...init });
    target.dispatchEvent(event);
    return event;
}
