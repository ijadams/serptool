<?php
namespace SEOstats\Services\Google;

/**
 * SEOstats extension for Google data.
 *
 * @package    SEOstats
 * @author     Stephan Schmitz <eyecatchup@gmail.com>
 * @copyright  Copyright (c) 2010 - present Stephan Schmitz
 * @license    http://eyecatchup.mit-license.org/  MIT License
 * @updated    2013/12/17
 */

use SEOstats\Common\SEOstatsException as E;
use SEOstats\SEOstats as SEOstats;
use SEOstats\Config as Config;
use SEOstats\Helper as Helper;

class Search extends SEOstats
{

    /**
     * Returns array, containing detailed results for any Google search.
     *
     * @param     string    $query  String, containing the search query.
     * @param     string    $tld    String, containing the desired Google top level domain.
     * @return    array             Returns array, containing the keys 'URL', 'Title' and 'Description'.
     */
    public static function getSerps($query, $maxResults=100, $domain=false)
    {
        $q = rawurlencode($query);
        $maxPage = ceil(($maxResults/10)-1);
        $result = new Helper\ArrayHandle ();
        $pages = 3;
        $delay = 0;

        $domainRexExp = static::getDomainFilter($domain);

        for ($start=0; $start<$pages; $start++) {

            $haveNextPage = static::makeRequest ($start, $q, $result, $domainRexExp);
            if (!$haveNextPage) {
                $pages -= 1;
            } else {
                $pages += 1;
                $delay += 200000;
                usleep($delay);
            }

            if ($start == $maxPage) {
                $pages -= 1;
            }
        } // for ($start=0; $start<$pages; $start++)

        return $result->toArray();
    }

    protected static function makeRequest ($start, $query, $result, $domainRexExp)
    {
        $ref = static::getReference($start, $query);
        $nextSerp = static::getNextSerp($start, $query);

        $curledSerp = utf8_decode( static::gCurl($nextSerp, $ref) );

        static::guardNoCaptcha($curledSerp);

        $matches = array();
        preg_match_all('#<h3 class="?r"?>(.*?)</h3>#', $curledSerp, $matches);

        if (empty($matches[1])) {
            // No [@id="rso"]/li/h3 on currect page
            return false;
        }

        static::parseResults($matches, $domainRexExp, $start * 10, $result);

        if ( preg_match('#id="?pnnext"?#', $curledSerp) ) {
            // Found 'Next'-link on currect page
            return true;
        }

        // No 'Next'-link on currect page
        return false;
    }

    protected static function getReference ($start, $query)
    {
        return 0 == $start
            ? 'ncr'
            : sprintf('search?q=%s&hl=en&prmd=imvns&start=%s0&sa=N', $query, $start);
    }

    protected static function getDomainFilter ($domain)
    {
        return $domain
            ? "#^(https?://)?[^/]*{$domain}#i"
            : false;
    }

    protected static function getNextSerp ($start, $query)
    {
        return 0 == $start
            ? sprintf('search?q=%s&filter=0', $query)
            : sprintf('search?q=%s&filter=0&start=%s0', $query, $start);
    }

    protected static function guardNoCaptcha ($response)
    {
        if (preg_match("#answer[=|/]86640#i", $response)) {
            print('Please read: https://support.google.com/websearch/answer/86640');
            exit();
        }
    }

    protected static function parseResults ($matches, $domainRexExp, $start, $result)
    {
        $c = 0;

        foreach ($matches[1] as $link) {
            $match = static::parseLink($link);

            $c++;
            $resCnt = $start + $c;
            if (! $domainRexExp) {
                $result->setElement($resCnt, array(
                    'url' => $match[1],
                    'headline' => trim(strip_tags($match[2]))
                ));
            } elseif (preg_match($domainRexExp, $match[1])) {
                $result->push(array(
                    'position' => $resCnt,
                    'url' => $match[1],
                    'headline' => trim(strip_tags($match[2]))
                ));
            }
        } // foreach ($matches[1] as $link)
    }

    protected static function parseLink($link)
    {
        $isValidLink = preg_match('#<a\s+[^>]*href=[\'"]?([^\'" ]+)[\'"]?[^>]*>(.*?)</a>#', $link, $match);

        // is valid and not webmaster link
        return ( !$isValidLink || self::isAGoogleWebmasterLink($match[1]) )
            ? false
            : $match;
    }

    protected static function isAGoogleWebmasterLink($url)
    {
        return preg_match('#^https?://www.google.com/(?:intl/.+/)?webmasters#', $url);
    }

    protected static function gCurl($path, $ref, $useCookie = Config\DefaultSettings::ALLOW_GOOGLE_COOKIES)
    {
        $url = sprintf('https://www.google.%s/', Config\DefaultSettings::GOOGLE_TLD);
        $referer = $ref == '' ? $url : $ref;
        $url .= $path;

        $ua = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_9_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/35.0.1916.153 Safari/537.36";
        if (isset($_SERVER["HTTP_USER_AGENT"]) && 0 < strlen($_SERVER["HTTP_USER_AGENT"])) {
            $ua = $_SERVER["HTTP_USER_AGENT"];
        }

        $header = array(
            'Host: www.google.' . Config\DefaultSettings::GOOGLE_TLD,
            'Connection: keep-alive',
            'Cache-Control: max-age=0',
            'User-Agent: ' . $ua,
            'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Referer: ' . $referer,
            'Accept-Language: ' . Config\DefaultSettings::HTTP_HEADER_ACCEPT_LANGUAGE,
            'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7'
        );

        //gen random proxy 
        $proxies = array("104.206.32.120:3128",
        "104.206.34.144:3128",
        "104.140.210.51:3128",
        "104.140.210.18:3128",
        "192.126.191.111:3128",
        "104.140.210.226:3128",
        "104.140.210.218:3128",
        "104.206.32.120:3128",
        "104.206.32.8:3128",
        "192.126.191.181:3128",
        "192.126.165.148:3128",
        "192.126.191.236:3128",
        "192.126.172.89:3128",
        "104.206.34.185:3128",
        "192.126.172.69:3128",
        "104.206.34.166:3128",
        "192.126.185.88:3128",
        "104.140.210.60:3128",
        "192.126.191.123:3128",
        "89.32.64.212:3128",
        "89.32.64.183:3128",
        "192.126.191.9:3128",
        "192.126.172.39:3128",
        "192.126.172.190:3128",
        "89.32.66.79:3128",
        "192.126.191.161:3128",
        "192.126.172.99:3128",
        "192.126.165.234:3128",
        "192.126.185.117:3128",
        "192.126.172.235:3128",
        "104.140.210.235:3128",
        "104.206.34.60:3128",
        "89.32.66.163:3128",
        "104.206.32.82:3128",
        "89.32.64.203:3128",
        "188.208.0.174:3128",
        "89.32.64.97:3128",
        "192.126.172.32:3128",
        "89.32.66.188:3128",
        "89.32.66.76:3128",
        "89.32.66.31:3128",
        "89.32.64.38:3128",
        "192.126.185.116:3128",
        "192.126.174.177:3128",
        "192.126.165.151:3128",
        "104.140.210.182:3128",
        "192.126.191.85:3128",
        "192.126.191.241:3128",
        "192.126.191.176:3128",
        "192.126.174.100:3128",
        "192.126.191.185:3128",
        "192.126.185.101:3128",
        "89.32.66.110:3128",
        "104.206.32.13:3128",
        "192.126.191.250:3128",
        "89.32.64.94:3128",
        "192.126.185.106:3128",
        "104.206.34.13:3128",
        "192.126.174.9:3128",
        "104.140.210.41:3128",
        "192.126.174.95:3128",
        "192.126.174.51:3128",
        "192.126.165.255:3128",
        "192.126.185.85:3128",
        "192.126.172.174:3128",
        "89.32.64.51:3128",
        "192.126.165.224:3128",
        "104.206.34.157:3128",
        "192.126.165.188:3128",
        "104.206.32.165:3128",
        "104.206.32.201:3128",
        "104.206.32.30:3128",
        "192.126.165.127:3128",
        "104.206.34.251:3128",
        "192.126.185.17:3128",
        "104.206.32.137:3128",
        "104.206.34.160:3128",
        "89.32.66.243:3128",
        "192.126.174.220:3128",
        "192.126.174.15:3128",
        "192.126.174.233:3128",
        "192.126.185.3:3128",
        "192.126.165.72:3128",
        "89.32.66.174:3128",
        "89.32.64.66:3128",
        "192.126.191.92:3128");

        shuffle($proxies);


        $ch = curl_init($url);
        //use proxy
        curl_setopt($ch, CURLOPT_PROXY, $proxies[0]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        if ($useCookie == 1) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
            curl_setopt($ch, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
        }
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        $result = curl_exec($ch);
        $info = curl_getinfo($ch);
        curl_close($ch);
        return ($info['http_code']!=200) ? false : $result;
    }
}
