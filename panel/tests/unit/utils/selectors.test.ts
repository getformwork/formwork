import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { $, $$ } from "../../../src/ts/utils/selectors";

describe("Selectors Utils", () => {
    describe("$", () => {
        test("returns the first element matching the selector in the document", () => {
            document.body.innerHTML = '<p class="foo" id="first"></p><p class="foo" id="second"></p>';
            assert.equal($(".foo")?.id, "first");
        });

        test("returns null without matches", () => {
            assert.equal($(".foo"), null);
        });

        test("looks only inside the parent", () => {
            document.body.innerHTML = '<p class="foo" id="first"></p><div><p class="foo" id="second"></p></div>';
            assert.equal($(".foo", document.querySelector("div") as HTMLElement)?.id, "second");
        });
    });

    describe("$$", () => {
        test("returns the elements matching the selector in document order", () => {
            document.body.innerHTML = '<p class="foo" id="first"></p><div><p class="foo" id="second"></p></div>';
            assert.deepEqual(
                Array.from($$(".foo"), (element) => element.id),
                ["first", "second"],
            );
        });

        test("returns an empty list without matches", () => {
            assert.equal($$(".foo").length, 0);
        });

        test("looks only inside the parent", () => {
            document.body.innerHTML = '<p class="foo" id="first"></p><div><p class="foo" id="second"></p></div>';
            assert.deepEqual(
                Array.from($$(".foo", document.querySelector("div") as HTMLElement), (element) => element.id),
                ["second"],
            );
        });
    });
});
