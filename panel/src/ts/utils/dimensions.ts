export function getOuterWidth(element: HTMLElement) {
    const style = getComputedStyle(element);
    return element.offsetWidth + parseInt(style.marginLeft) + parseInt(style.marginRight);
}

export function getOuterHeight(element: HTMLElement) {
    const style = getComputedStyle(element);
    return element.offsetHeight + parseInt(style.marginTop) + parseInt(style.marginBottom);
}
