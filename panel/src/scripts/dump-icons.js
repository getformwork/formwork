import fs from "fs";
import path from "path";

const ICONS_DIR = path.resolve(import.meta.dirname, "../../assets/icons/svg/");
const OUTPUT_FILE = path.resolve(import.meta.dirname, "../ts/components/icons.ts");

function sanitizeName(filename) {
    return filename
        .replace(/\.svg$/i, "")
        .split(/[-_ ]+/)
        .map((part, index) => {
            if (index === 0) {
                return part.toLowerCase();
            }
            return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
        })
        .join("");
}

function readSVGFiles(dir) {
    return fs.readdirSync(dir).filter((file) => file.endsWith(".svg"));
}

function generateModule(svgs) {
    let content = "// This file is auto-generated. Do not edit directly.\n";

    svgs.forEach(({ name, svg }) => {
        const cleanedSVG = svg
            .replace(/\r?\n|\r/g, " ")
            .replace(/\s+/g, " ")
            .trim();
        content += `\nexport const ${name} = \`${cleanedSVG}\\n\`;\n`;
    });

    return content;
}

function buildIcons() {
    const files = readSVGFiles(ICONS_DIR);
    if (!files.length) {
        console.error("No SVG files found in", ICONS_DIR);
        return;
    }

    const svgs = files.map((file) => {
        const filepath = path.join(ICONS_DIR, file);
        const svg = fs.readFileSync(filepath, "utf8");
        const name = sanitizeName(file);
        return { name, svg };
    });

    const moduleContent = generateModule(svgs);
    fs.writeFileSync(OUTPUT_FILE, moduleContent, "utf8");
    console.log(`Generated ${OUTPUT_FILE} with ${svgs.length} icons`);
}

buildIcons();
