import * as esbuild from "esbuild";
import process from "process";
import fs from "fs/promises";

const watch = process.argv.includes("--watch");

const options = {
    entryPoints: { "app.min": "./src/ts/app.ts" },
    bundle: true,
    format: "esm",
    target: "es2020",
    chunkNames: "chunks/[name]-[hash]",
    minify: true,
    splitting: true,
    outdir: "./assets/js",
    logLevel: "info",
};

if (watch) {
    // New esbuild API for watch mode
    const ctx = await esbuild.context(options);
    await ctx.watch();
} else {
    await fs.rm("./assets/js/chunks/", { recursive: true, force: true });
    await esbuild.build(options);
}
