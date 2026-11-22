import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { Sections } from "../../../../src/ts/components/ui/sections";
import { byId } from "../../../helpers/events";

/**
 * Add collapsible sections to the document as the panel renders them and handle them
 */
function makeSections(html = section("first") + section("second", true), form = "") {
    document.body.innerHTML = form ? `<form data-form="${form}">${html}</form>` : html;
    new Sections();
}

/**
 * Get the HTML of a collapsible section
 */
function section(id: string, collapsed = false) {
    return `<section class="section collapsible ${collapsed ? "collapsed" : ""}" ${id ? `id="${id}"` : ""}><div class="section-header"><button type="button" class="button section-toggle"></button><span class="caption">${id}</span></div><div class="section-content"></div></section>`;
}

const isCollapsed = (id: string) => byId(id).classList.contains("collapsed");

const toggle = (id: string) => byId(id).querySelector(".section-toggle") as HTMLElement;

describe("Sections", () => {
    test("shows the state of the sections on their toggles", () => {
        makeSections();
        assert.equal(toggle("first").getAttribute("aria-expanded"), "true");
        assert.equal(toggle("second").getAttribute("aria-expanded"), "false");
    });

    test("collapses and expands a section when its header is clicked", () => {
        makeSections();
        (byId("first").querySelector(".section-header") as HTMLElement).click();
        assert.equal(isCollapsed("first"), true);
        assert.equal(toggle("first").getAttribute("aria-expanded"), "false");
        toggle("first").click();
        assert.equal(isCollapsed("first"), false);
    });

    test("remembers the state of the sections", () => {
        makeSections();
        toggle("first").click();
        toggle("second").click();
        assert.equal(window.localStorage.getItem("formwork.sectionStatus[first]"), "collapsed");
        assert.equal(window.localStorage.getItem("formwork.sectionStatus[second]"), "expanded");
        makeSections();
        assert.equal(isCollapsed("first"), true);
        assert.equal(isCollapsed("second"), false);
    });

    test("remembers the state of the sections of a form", () => {
        makeSections(section("first"), "page-editor-form");
        toggle("first").click();
        assert.equal(window.localStorage.getItem("formwork.sectionStatus[page-editor-form.first]"), "collapsed");
    });

    test("does not remember the state of the sections without an id", () => {
        makeSections(section(""));
        (document.querySelector(".section-toggle") as HTMLElement).click();
        assert.equal(document.querySelector(".section")?.classList.contains("collapsed"), true);
        assert.equal(window.localStorage.length, 0);
    });
});
