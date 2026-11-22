/**
 * Replacements of the characters not allowed in a slug
 */
const slugMap: Record<string, string> = {
    '"': "-",
    "'": "-",
    "/": "-",
    "\\": "-",
    _: "-",
    "~": "-",
    "¢": "c",
    ß: "ss",
    à: "a",
    á: "a",
    â: "a",
    ã: "a",
    ä: "ae",
    å: "a",
    æ: "ae",
    ç: "c",
    è: "e",
    é: "e",
    ê: "e",
    ë: "e",
    ì: "i",
    í: "i",
    î: "i",
    ï: "i",
    ð: "d",
    ñ: "n",
    ò: "o",
    ó: "o",
    ô: "o",
    õ: "o",
    ö: "oe",
    ø: "o",
    ù: "u",
    ú: "u",
    û: "u",
    ü: "ue",
    ý: "y",
    þ: "th",
    ÿ: "y",
    ā: "a",
    ă: "a",
    ą: "a",
    ć: "c",
    ĉ: "c",
    ċ: "c",
    č: "c",
    ď: "d",
    đ: "d",
    ē: "e",
    ĕ: "e",
    ė: "e",
    ę: "e",
    ě: "e",
    ĝ: "g",
    ğ: "g",
    ġ: "g",
    ģ: "g",
    ĥ: "h",
    ħ: "h",
    ĩ: "i",
    ī: "i",
    ĭ: "i",
    į: "i",
    ı: "i",
    ĳ: "ij",
    ĵ: "j",
    ķ: "k",
    ĸ: "k",
    ĺ: "l",
    ļ: "l",
    ľ: "l",
    ŀ: "l",
    ł: "l",
    ń: "n",
    ņ: "n",
    ň: "n",
    ŉ: "n",
    ŋ: "n",
    ō: "o",
    ŏ: "o",
    ő: "o",
    œ: "oe",
    ŕ: "r",
    ŗ: "r",
    ř: "r",
    ś: "s",
    ŝ: "s",
    ş: "s",
    š: "s",
    ţ: "t",
    ť: "t",
    ŧ: "t",
    ũ: "u",
    ū: "u",
    ŭ: "u",
    ů: "u",
    ű: "u",
    ų: "u",
    ŵ: "w",
    ŷ: "y",
    ź: "z",
    ż: "z",
    ž: "z",
    ș: "s",
    ț: "t",
    ſ: "s",
    "–": "-",
    "—": "-",
    "‘": "-",
    "’": "-",
    "“": "-",
    "”": "-",
};

/**
 * Characters matching each letter in a search ignoring diacritics
 */
const diacriticsMap: Record<string, string> = {
    a: "[aáàăâǎåäãȧąāảȁạ]",
    b: "[bḃḅ]",
    c: "[cćĉčċç]",
    d: "[dďḋḑḍ]",
    e: "[eéèĕêěëẽėȩęēẻȅẹ]",
    g: "[gǵğĝǧġģḡ]",
    h: "[hĥȟḧḣḩḥ]",
    i: "[iiíìĭîǐïĩįīỉȉịı]",
    j: "[jĵǰ]",
    k: "[kḱǩķḳ]",
    l: "[lĺľļḷ]",
    m: "[mḿṁṃ]",
    n: "[nńǹňñṅņṇ]",
    o: "[oóòŏôǒöőõȯǿǫōỏȍơọ]",
    p: "[pṕṗ]",
    r: "[rŕřṙŗȑṛ]",
    s: "[sśŝšṡşṣș]",
    t: "[tťẗṫţṭț]",
    u: "[uúùŭûǔůüűũųūủȕưụ]",
    v: "[vṽṿ]",
    w: "[wẃẁŵẘẅẇẉ]",
    x: "[xẍẋ]",
    y: "[yýỳŷẙÿỹẏȳỷỵ]",
    z: "[zźẑžżẓ]",
};

/**
 * HTML entities of the escaped characters
 */
const htmlEntityMap: Record<string, string> = {
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
    "/": "&#x2F;",
    "`": "&#x60;",
    "=": "&#x3D;",
};

/**
 * Escape the characters of a string having a special meaning in a regular expression
 *
 * `-` is not escaped, as it is special only in character classes and its escape is not valid with
 * the `u` flag
 */
export function escapeRegExp(string: string) {
    return string.replace(/[[\]/{}()*+?.\\^$|]/g, "\\$&");
}

/**
 * Make a regular expression string ignore diacritics
 */
export function makeDiacriticsRegExp(string: string) {
    return string.replace(/./g, (match) => diacriticsMap[match] || diacriticsMap[match.toLowerCase()]?.toUpperCase() || match);
}

/**
 * Make a slug from a text
 */
export function makeSlug(string: string) {
    // Hyphens are collapsed and removed at the ends after removing the characters not allowed, which may leave them
    return validateSlug(string).replace(/-+/g, "-").replace(/^-|-$/g, "");
}

/**
 * Escape the characters of a string having a special meaning in HTML
 */
export function escapeHtml(string: string) {
    return string.replace(/[&<>"'`=/]/g, (match) => htmlEntityMap[match]);
}

/**
 * Make a string a valid slug while typing
 *
 * Leading and trailing hyphens are kept
 */
export function validateSlug(slug: string) {
    return slug
        .toLowerCase()
        .replace(/./gu, (match) => slugMap[match] ?? match)
        .replace(/\s+/g, "-")
        .replace(/[^a-z0-9-]/g, "");
}
