import type { Command, EditorState } from "prosemirror-state";
import { Plugin, PluginKey } from "prosemirror-state";
import { CellSelection, isInTable, moveTableColumn, moveTableRow, TableMap } from "prosemirror-tables";
import type { EditorView } from "prosemirror-view";
import { app } from "../../services/app";
import * as icons from "../ui/icons";
import { type Axis, addColumnLeft, addColumnRight, addRowAbove, addRowBelow, alignColumn, clearCells, deleteColumns, deleteRows, deleteSelectedCells, deletesAll, deleteWholeTable, getHeadCell, isColumnAligned, selectAxis } from "./markdown-tables";

/**
 * Describes an item of the menu of a handle
 */
interface HandleMenuItem {
    command: Command;
    dom: HTMLButtonElement;
    /**
     * Get whether the item shows the current state, like the alignment of the column
     */
    isActive?: (state: EditorState) => boolean;
    /**
     * Get whether the item is shown
     */
    isVisible?: (state: EditorState) => boolean;
}

/**
 * Describes an item to add to the menu of a handle
 */
type HandleMenuItemOptions = Omit<HandleMenuItem, "dom"> & {
    icon: keyof typeof icons;
    label: string;
    /**
     * Whether the item follows a separator
     */
    separated?: boolean;
};

/**
 * Describes a handle selecting a row or a column, with its menu
 */
interface Handle {
    dom: HTMLElement;
    button: HTMLButtonElement;
    items: HandleMenuItem[];
}

/**
 * Describes a row or a column being dragged by its handle
 */
interface Drag {
    axis: Axis;
    /**
     * Index of the row or the column
     */
    from: number;
    /**
     * Index where the row or the column is moved
     */
    to: number;
    /**
     * Position of the table
     */
    tableStart: number;
    /**
     * Rows of the table, or cells of its first row for the columns
     */
    elements: HTMLElement[];
    table: HTMLElement;
    /**
     * Copy of the row or the column following the pointer
     */
    ghost: HTMLElement;
    /**
     * Distance of the pointer from the start of the row or the column
     */
    offset: number;
}

/**
 * Key of the plugin of the handles, whose state is the handle that made the selection, if any
 */
const handlesKey = new PluginKey<Axis | null>("tableHandles");

/**
 * Select the row or the column of the cursor with its handle
 */
function selectByHandle(state: EditorState, axis: Axis) {
    return state.tr.setSelection(selectAxis(getHeadCell(state), axis)).setMeta(handlesKey, axis);
}

/**
 * Get the items of the menus of the handles
 */
function getMenuItems(): Record<Axis, HandleMenuItemOptions[]> {
    const t = (key: string, defaultValue: string) => app.translation.get(key, defaultValue);

    // Deleting all the rows or the columns deletes the table
    const deleteTable = (axis: Axis): HandleMenuItemOptions => ({
        command: deleteWholeTable,
        isVisible: (state) => deletesAll(state, axis),
        icon: "trash",
        label: t("panel.editor.table.deleteTable", "Delete table"),
    });

    const align = (alignment: "left" | "center" | "right", icon: keyof typeof icons, label: string): HandleMenuItemOptions => ({
        command: alignColumn(alignment),
        isActive: (state) => isColumnAligned(state, alignment),
        icon,
        label,
    });

    return {
        row: [
            { command: addRowAbove, icon: "arrowUpCircle", label: t("panel.editor.table.addRowAbove", "Add row above") },
            { command: addRowBelow, icon: "arrowDownCircle", label: t("panel.editor.table.addRowBelow", "Add row below") },
            { command: clearCells("row"), icon: "timesCircle", label: t("panel.editor.table.clearRow", "Clear row") },
            { command: deleteRows, isVisible: (state) => !deletesAll(state, "row"), icon: "trash", separated: true, label: t("panel.editor.table.deleteRow", "Delete row") },
            deleteTable("row"),
        ],
        column: [
            align("left", "alignLeft", t("panel.editor.table.alignLeft", "Align left")),
            align("center", "alignCenter", t("panel.editor.table.alignCenter", "Align center")),
            align("right", "alignRight", t("panel.editor.table.alignRight", "Align right")),
            { command: addColumnLeft, icon: "arrowLeftCircle", separated: true, label: t("panel.editor.table.addColumnLeft", "Add column left") },
            { command: addColumnRight, icon: "arrowRightCircle", label: t("panel.editor.table.addColumnRight", "Add column right") },
            { command: clearCells("column"), icon: "timesCircle", label: t("panel.editor.table.clearColumn", "Clear column") },
            { command: deleteColumns, isVisible: (state) => !deletesAll(state, "column"), icon: "trash", separated: true, label: t("panel.editor.table.deleteColumn", "Delete column") },
            deleteTable("column"),
        ],
    };
}

/**
 * Create a handle with its menu
 *
 * @param id    Id of the menu
 * @param axis  Whether the handle is of a row or of a column
 * @param label Label of the handle
 * @param items Items of the menu
 */
function createHandle(id: string, axis: Axis, label: string, items: HandleMenuItemOptions[]): Handle {
    const dom = document.createElement("div");
    dom.className = `dropdown table-handle table-handle-${axis}`;
    dom.hidden = true;

    const button = document.createElement("button");
    button.type = "button";
    button.className = "table-handle-button dropdown-button";
    button.dataset.dropdown = `dropdown-${id}`;
    button.ariaLabel = label;
    button.innerHTML = icons.ellipsisH;

    const menu = document.createElement("div");
    menu.className = "dropdown-menu";
    menu.id = `dropdown-${id}`;

    const menuItems = items.map(({ icon, label, separated, ...item }) => {
        if (separated) {
            const separator = document.createElement("hr");
            separator.className = "dropdown-separator";
            menu.append(separator);
        }

        const dom = document.createElement("button");
        dom.type = "button";
        dom.className = "dropdown-item";
        // biome-ignore lint/performance/noDynamicNamespaceImportAccess: The icons of the items are given by name
        dom.innerHTML = `${icons[icon]} `;
        dom.append(label);
        menu.append(dom);

        return { ...item, dom };
    });

    dom.append(button, menu);

    return { dom, button, items: menuItems };
}

/**
 * Create a copy of a row or a column of a table, with the size of the original cells
 *
 * @param rows  Rows of the table
 * @param index Index of the row or the column
 */
function createGhost(axis: Axis, rows: HTMLElement[], index: number) {
    const ghost = document.createElement("table");
    ghost.className = "table-ghost";
    ghost.setAttribute("aria-hidden", "true");

    const body = ghost.createTBody();

    const copy = (cell: Element) => {
        const rect = cell.getBoundingClientRect();
        const clone = cell.cloneNode(true) as HTMLElement;
        clone.classList.remove("selectedCell");
        clone.style.width = `${rect.width}px`;
        clone.style.height = `${rect.height}px`;
        return clone;
    };

    if (axis === "row") {
        body.insertRow().append(...Array.from(rows[index].children, copy));
        ghost.style.width = `${rows[index].getBoundingClientRect().width}px`;
    } else {
        for (const row of rows) {
            const cell = row.children[index];
            if (cell) {
                body.insertRow().append(copy(cell));
            }
        }
        ghost.style.width = `${rows[0].children[index].getBoundingClientRect().width}px`;
    }

    return ghost;
}

/**
 * Get the index of the row or the column under the pointer, or of the first or the last one when
 * the pointer is beyond the table
 *
 * @param coordinate Vertical coordinate of the pointer for the rows, horizontal for the columns
 */
function getDropIndex(drag: Drag, coordinate: number) {
    const index = drag.elements.findIndex((element) => {
        const rect = element.getBoundingClientRect();
        return coordinate < (drag.axis === "row" ? rect.bottom : rect.right);
    });
    return index === -1 ? drag.elements.length - 1 : index;
}

/**
 * Shows the handles of the row and the column of the cursor, which select them, open a menu to
 * change them and move them when dragged
 */
class TableHandlesView {
    private readonly view: EditorView;

    private readonly container: HTMLElement;

    private readonly handles: Record<Axis, Handle>;

    /**
     * Line showing where the dragged row or column goes
     */
    private readonly indicator: HTMLElement;

    private readonly scheduleUpdate = () => window.requestAnimationFrame(() => this.update());

    /**
     * Keep the handles and the editor focused while the focus is on the handles or on their menus
     */
    private readonly focusChanged = (event: FocusEvent) => {
        this.keepFocused(event);
        this.scheduleUpdate();
    };

    constructor(id: string, view: EditorView) {
        this.view = view;
        this.container = view.dom.parentElement as HTMLElement;

        const t = (key: string, defaultValue: string) => app.translation.get(key, defaultValue);
        const items = getMenuItems();

        this.handles = {
            row: createHandle(`${id}-table-row`, "row", t("panel.editor.table.row", "Row"), items.row),
            column: createHandle(`${id}-table-column`, "column", t("panel.editor.table.column", "Column"), items.column),
        };

        this.indicator = document.createElement("div");
        this.indicator.className = "table-drop-indicator";
        this.indicator.hidden = true;

        for (const axis of ["row", "column"] as const) {
            this.bindHandle(axis);
            this.bindDrag(axis);
        }

        this.container.append(this.handles.row.dom, this.handles.column.dom, this.indicator);

        this.container.addEventListener("focusin", this.focusChanged);
        this.container.addEventListener("focusout", this.focusChanged);
        view.dom.addEventListener("scroll", this.scheduleUpdate);
        window.addEventListener("resize", this.scheduleUpdate);

        this.update();
    }

    /**
     * Return whether a target of an event is in the handles or in their menus
     */
    private inHandles(target: EventTarget | null) {
        return target instanceof globalThis.Node && (this.handles.row.dom.contains(target) || this.handles.column.dom.contains(target));
    }

    /**
     * Make the editor look focused also while the focus is on the handles or on their menus, as the
     * editor does for itself with the `focused` class of its container
     */
    private keepFocused(event: FocusEvent) {
        if (event.type === "focusin" && this.inHandles(event.target)) {
            this.container.classList.add("focused");
        } else if (event.type === "focusout" && this.inHandles(event.target) && !this.inHandles(event.relatedTarget) && event.relatedTarget !== this.view.dom) {
            this.container.classList.remove("focused");
        }
    }

    /**
     * Place an element of the handles at a point of the screen, inside their container
     */
    private place(element: HTMLElement, x: number, y: number) {
        const containerRect = this.container.getBoundingClientRect();
        element.style.left = `${x - containerRect.left}px`;
        element.style.top = `${y - containerRect.top}px`;
    }

    /**
     * Select the row or the column of a handle when it is clicked, and run the items of its menu
     *
     * Backspace and Delete work on the selected row or column also while the handle or its menu
     * have the focus, closing the menu
     */
    private bindHandle(axis: Axis) {
        const handle = this.handles[axis];

        // The editor keeps the focus until the menu is opened
        handle.button.addEventListener("mousedown", (event) => event.preventDefault());

        handle.dom.addEventListener("keydown", (event) => {
            if (event.key !== "Backspace" && event.key !== "Delete") {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            // The menu closes when the focus leaves it
            this.view.focus();
            deleteSelectedCells(this.view.state, this.view.dispatch, this.view);
        });

        handle.button.addEventListener("click", () => {
            if (isInTable(this.view.state)) {
                this.view.dispatch(selectByHandle(this.view.state, axis));
            }
        });

        handle.dom.addEventListener("click", (event) => {
            const item = handle.items.find(({ dom }) => dom.contains(event.target as HTMLElement));

            if (item) {
                this.view.focus();
                item.command(this.view.state, this.view.dispatch, this.view);
            }
        });
    }

    /**
     * Move the row or the column of a handle by dragging it
     *
     * The drag starts once the pointer moves a little, so that a click still opens the menu, and is
     * cancelled with Escape
     */
    private bindDrag(axis: Axis) {
        const { button } = this.handles[axis];
        const coordinateOf = (event: PointerEvent) => (axis === "row" ? event.clientY : event.clientX);

        button.addEventListener("pointerdown", (event) => {
            if (event.button !== 0 || !this.view.editable) {
                return;
            }

            const start = event;
            let drag: Drag | null = null;

            button.setPointerCapture(event.pointerId);

            const move = (event: PointerEvent) => {
                if (!drag) {
                    if (Math.hypot(event.clientX - start.clientX, event.clientY - start.clientY) < 4) {
                        return;
                    }
                    drag = this.startDrag(axis, coordinateOf(start));
                    if (!drag) {
                        stop();
                        return;
                    }
                    button.classList.add("is-dragging");
                }
                drag.to = getDropIndex(drag, coordinateOf(event));
                this.showIndicator(drag);
                this.moveGhost(drag, coordinateOf(event));
            };

            const stop = () => {
                button.removeEventListener("pointermove", move);
                button.removeEventListener("pointerup", drop);
                button.removeEventListener("pointercancel", stop);
                document.removeEventListener("keydown", cancel, true);
                button.classList.remove("is-dragging");
                this.indicator.hidden = true;
                drag?.ghost.remove();

                if (drag) {
                    // The click ending the drag does not open the menu
                    const preventClick = (event: Event) => {
                        event.stopImmediatePropagation();
                        event.preventDefault();
                    };
                    button.addEventListener("click", preventClick, { capture: true, once: true });
                    setTimeout(() => button.removeEventListener("click", preventClick, { capture: true }));
                }
            };

            const drop = () => {
                stop();
                if (drag && drag.to !== drag.from) {
                    const options = { from: drag.from, to: drag.to, pos: drag.tableStart };
                    const command = axis === "row" ? moveTableRow(options) : moveTableColumn(options);
                    command(this.view.state, this.view.dispatch, this.view);
                }
                this.view.focus();
            };

            // Stopping without dropping leaves the row or the column where it is
            const cancel = (event: KeyboardEvent) => {
                if (event.key === "Escape") {
                    event.preventDefault();
                    event.stopPropagation();
                    stop();
                }
            };

            button.addEventListener("pointermove", move);
            button.addEventListener("pointerup", drop);
            button.addEventListener("pointercancel", stop);
            document.addEventListener("keydown", cancel, true);
        });
    }

    /**
     * Select the row or the column of the cursor to drag it, showing its ghost
     */
    private startDrag(axis: Axis, coordinate: number): Drag | null {
        const { state } = this.view;

        if (!isInTable(state)) {
            return null;
        }

        const $cell = getHeadCell(state);
        const tableStart = $cell.start(-1);
        const { top, left } = TableMap.get($cell.node(-1)).findCell($cell.pos - tableStart);
        const table = this.view.nodeDOM(tableStart - 1) as HTMLElement | null;

        if (!table) {
            return null;
        }

        this.view.dispatch(selectByHandle(state, axis));

        const rows = Array.from(table.querySelectorAll<HTMLElement>(":scope > tbody > tr"));
        const elements = axis === "row" ? rows : Array.from(rows[0]?.children ?? []).filter((cell): cell is HTMLElement => cell instanceof HTMLElement);
        const from = axis === "row" ? top : left;
        const rect = elements[from].getBoundingClientRect();

        const ghost = createGhost(axis, rows, from);
        this.container.append(ghost);

        const drag = { axis, from, to: from, tableStart, elements, table, ghost, offset: coordinate - (axis === "row" ? rect.top : rect.left) };
        this.moveGhost(drag, coordinate);

        return drag;
    }

    /**
     * Move the ghost with the pointer along the table, keeping it inside the table
     */
    private moveGhost(drag: Drag, coordinate: number) {
        const tableRect = drag.table.getBoundingClientRect();
        const ghostRect = drag.ghost.getBoundingClientRect();

        if (drag.axis === "row") {
            this.place(drag.ghost, tableRect.left, Math.min(Math.max(coordinate - drag.offset, tableRect.top), tableRect.bottom - ghostRect.height));
        } else {
            this.place(drag.ghost, Math.min(Math.max(coordinate - drag.offset, tableRect.left), tableRect.right - ghostRect.width), tableRect.top);
        }
    }

    /**
     * Show where the dragged row or column goes: after the row or column under the pointer when
     * moving forward, before it when moving backward
     */
    private showIndicator(drag: Drag) {
        const element = drag.elements[drag.to];

        this.indicator.hidden = drag.to === drag.from || !element;

        if (this.indicator.hidden) {
            return;
        }

        const tableRect = drag.table.getBoundingClientRect();
        const rect = element.getBoundingClientRect();
        const forward = drag.to > drag.from;

        // The indicator is as long as the table, across the rows or the columns
        this.indicator.className = `table-drop-indicator table-drop-indicator-${drag.axis}`;
        this.indicator.style.setProperty("--length", `${drag.axis === "row" ? tableRect.width : tableRect.height}px`);

        if (drag.axis === "row") {
            this.place(this.indicator, tableRect.left, forward ? rect.bottom : rect.top);
        } else {
            this.place(this.indicator, forward ? rect.right : rect.left, tableRect.top);
        }
    }

    /**
     * Place the handles at the row and the column of the cursor, or hide them
     */
    update() {
        const { state } = this.view;
        const { row: rowHandle, column: columnHandle } = this.handles;
        const editable = this.view.props.editable ? this.view.props.editable(state) : true;
        const focused = this.view.hasFocus() || this.inHandles(document.activeElement);

        const cell = editable && focused && isInTable(state) ? (this.view.nodeDOM(getHeadCell(state).pos) as HTMLElement | null) : null;
        const row = cell?.parentElement;
        const table = cell?.closest("table");

        if (!cell || !row || !table) {
            rowHandle.dom.hidden = true;
            columnHandle.dom.hidden = true;
            return;
        }

        const editorRect = this.view.dom.getBoundingClientRect();
        const cellRect = cell.getBoundingClientRect();
        const rowRect = row.getBoundingClientRect();
        const tableRect = table.getBoundingClientRect();
        const rowMiddle = rowRect.top + rowRect.height / 2;

        const { selection } = state;
        const rowSelected = selection instanceof CellSelection && selection.isRowSelection();
        const columnSelected = selection instanceof CellSelection && selection.isColSelection();
        // A selection of all the cells, made by a handle, keeps showing only that handle
        const handleAxis = handlesKey.getState(state) ?? null;

        // Handles scrolled out of the editor are hidden, as is the other handle while a row or a
        // column is selected
        rowHandle.dom.hidden = rowMiddle < editorRect.top || rowMiddle > editorRect.bottom || (columnSelected && !rowSelected) || handleAxis === "column";
        columnHandle.dom.hidden = tableRect.top < editorRect.top || tableRect.top > editorRect.bottom || (rowSelected && !columnSelected) || handleAxis === "row";

        // The handles are placed on whole pixels of the screen, or their borders would be blurred
        this.place(rowHandle.dom, Math.round(tableRect.left), Math.round(rowMiddle));
        this.place(columnHandle.dom, Math.round(cellRect.left + cellRect.width / 2), Math.round(tableRect.top));

        rowHandle.button.classList.toggle("is-active", rowSelected && handleAxis !== "column");
        columnHandle.button.classList.toggle("is-active", columnSelected && handleAxis !== "row");

        for (const { command, dom, isActive, isVisible } of [...rowHandle.items, ...columnHandle.items]) {
            dom.hidden = !(isVisible?.(state) ?? true);
            dom.disabled = !command(state);
            dom.classList.toggle("is-active", isActive?.(state) ?? false);
        }
    }

    /**
     * Remove the handles
     */
    destroy() {
        this.container.removeEventListener("focusin", this.focusChanged);
        this.container.removeEventListener("focusout", this.focusChanged);
        this.view.dom.removeEventListener("scroll", this.scheduleUpdate);
        window.removeEventListener("resize", this.scheduleUpdate);
        this.handles.row.dom.remove();
        this.handles.column.dom.remove();
        this.indicator.remove();
    }
}

/**
 * Get the plugin showing the handles of the rows and columns of the tables
 *
 * @param id Id of the editor
 */
export function tableHandles(id: string) {
    return new Plugin<Axis | null>({
        key: handlesKey,
        state: {
            init: () => null,
            // The handle is remembered until the selection is changed otherwise
            apply(tr, axis) {
                const meta = tr.getMeta(handlesKey);
                if (meta !== undefined) {
                    return meta;
                }
                return tr.selectionSet ? null : axis;
            },
        },
        view: (view) => new TableHandlesView(id, view),
    });
}
