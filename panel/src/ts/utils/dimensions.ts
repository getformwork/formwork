/**
 * Get the width of an element including its margins
 */
export function getOuterWidth(element: HTMLElement) {
    const style = getComputedStyle(element);
    return element.offsetWidth + parseInt(style.marginLeft, 10) + parseInt(style.marginRight, 10);
}

/**
 * Get the height of an element including its margins
 */
export function getOuterHeight(element: HTMLElement) {
    const style = getComputedStyle(element);
    return element.offsetHeight + parseInt(style.marginTop, 10) + parseInt(style.marginBottom, 10);
}
