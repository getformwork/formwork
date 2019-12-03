<?php

namespace Formwork\Utils;

class Visitor
{
    /**
     * Array containing regex tokens that identify bots based on monperrus/crawler-user-agents list
     *
     * @see https://raw.githubusercontent.com/monperrus/crawler-user-agents/master/crawler-user-agents.json
     *
     * @var array
     */
    protected const BOTS_REGEX_TOKENS = [
        '(apache-http|btweb|go-http-|http_)client', '(apis|appengine|mediapartners)-google',
        '(analyz|fetch|find|gath|gett|load|read|reap|se|sp[iy]d|track|transcod)er',
        '(bing|skypeuri)preview', '(http_|media|w)get', '(ips-|netcraftsurvey)agent',
        '(mega)?index(er| )?', '(ok|pcore-)http', '(aggregat|detect|extract|validat)or',
        '^ning\/', 'ahc\/', 'amazon cloudfront', 'analyze', 'anyevent', 'appinsights',
        'archiv(er?|ing)', 'axios', 'biglotron', 'binlar', 'blackboard', 'bot', 'brandverity',
        'bubing', 'capture', 'check', 'chrome-lighthouse', 'cloudflare-alwaysonline', 'coccoc',
        'collect', 'control', 'crawl', 'curl', 'dareboost', 'dataprovider', 'daum',
        'deusu', 'digg deeper', 'disqus', 'download(s|er)', 'drupact', 'embedly', 'extract',
        'facebookexternalhit', 'fantom', 'feed', 'fetch', 'findlinks', 'flipboardproxy', 'genieo',
        'gigablast', 'google( favicon| web preview|-physicalweb|-structured-data-testing-tool|-xrawler)',
        'grouphigh', 'harvest', 'hatena', 'heritrix', 'hound', 'html2', 'http(\.rb|unit|urlconnection)',
        'httrack', 'ichiro', 'images', 'iskanie', 'java\/', 'jetty', 'jorgee', 'leech', 'library',
        'link(dex|man)', 'lipperhey', 'locat(e|or)', 'ltx71', 'meltwaternews', 'metauri', 'miniflux',
        'monitor', 'moreover', 'muckrack', 'newspaper', 'nmap scripting engine', 'nuzzel', 'omgili',
        'outbrain', 'page2rss', 'panscient', 'parse', 'perl', 'php\/\d', 'postrank', 'pr-cy\.ru',
        'program', 'proximic', 'python', 'qwant', 'rating', 'retrieve', 'rivva', 'scan', 'scoutjet',
        'scrap(e|y)', 'search', 'sentry', 'site[-\s]?(auditor|check|explorer|improve\.com|scan)',
        'snacktory', 'sniff', 'spy', 'summify', 'sweep', 'sysomos', 'teoma', 'thinklab', 'thumb',
        'traackr', 'twingly', 'twurly', 'um-ln', 'upflow', 'utility', 'verif(y|ier)', 'vkshare',
        'w3c(-mobileok|_unicorn)', 'web(datastats|inator)', 'web[-\s]?search', 'whatsapp', 'worth',
        'xenu link sleuth', 'yahoo', 'zabbix', 'zgrab'
    ];

    /**
     * Compiled bots regex
     *
     * @var string
     */
    protected static $regex;

    /**
     * Return whether current visitor is a bot
     *
     * @return bool
     */
    public static function isBot()
    {
        if (static::$regex === null) {
            static::$regex = '/' . implode('|', self::BOTS_REGEX_TOKENS) . '/i';
        }
        return (bool) preg_match(static::$regex, HTTPRequest::userAgent());
    }

    /**
     * Return whether current user agent is a browser
     *
     * @return bool
     */
    public static function isBrowser()
    {
        return !static::isBot();
    }

    /**
     * Detect whether current visitor prefers not to be tracked
     *
     * @return bool
     */
    public static function isTrackable()
    {
        return !HTTPRequest::hasHeader('Dnt') || HTTPRequest::headers()['Dnt'] !== '1';
    }
}
