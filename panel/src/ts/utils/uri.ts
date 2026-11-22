/**
 * Matches URIs with a scheme (e.g. `https:`, `mailto:`, `data:`) or protocol-relative (`//`)
 */
const absoluteUriRegExp = /^(?:[a-z][a-z\d+.-]*:|\/\/)/i;

/**
 * Placeholder origin of the URLs used to resolve paths, of which only the path, the query and the
 * fragment are used
 */
const placeholderOrigin = "http://site.invalid/";

/**
 * Resolve a URI of the content of a page as the site does when rendering it
 *
 * Relative URIs are resolved against the page and URIs starting with `/` against the root of the
 * site, which parent segments do not go beyond. URIs with a scheme or a host and fragments are
 * left as they are
 *
 * @param siteUri URI of the site
 * @param pageUri URI of the page
 */
export function resolveUri(uri: string, siteUri: string, pageUri: string) {
    if (uri === "" || uri.startsWith("#") || absoluteUriRegExp.test(uri)) {
        return uri;
    }

    const sitePath = new URL(siteUri, placeholderOrigin).pathname.replace(/\/*$/, "/");
    const pagePath = new URL(pageUri, placeholderOrigin).pathname;

    // The route of the page is its path inside the site
    const route = pagePath.startsWith(sitePath) ? pagePath.slice(sitePath.length - 1) : pagePath;

    const resolved = new URL(uri, new URL(route, placeholderOrigin));

    return `${sitePath.slice(0, -1)}${resolved.pathname}${resolved.search}${resolved.hash}`;
}
