<?php
/**
 * This file is part of the Crystal framework.
 *
 * (c) Sergey Novikov (novikov.stranger@gmail.com)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Date: 08.12.2014
 * Time: 17:42
 */

namespace core;

/**
 * Class Request
 * @package core
 *
 * This class has been copied from the framework CodeIgnter v.3 (See class CI_Security) and refactored
 */
class Security
{
    /**
     * List of sanitize filename strings
     *
     * @var    array
     */
    protected $filename_bad_chars = [
        '../', '<!--', '-->', '<', '>',
        "'", '"', '&', '$', '#',
        '{', '}', '[', ']', '=',
        ';', '?', '%20', '%22',
        '%3c',      // <
        '%253c',    // <
        '%3e',      // >
        '%0e',      // >
        '%28',      // (
        '%29',      // )
        '%2528',    // (
        '%26',      // &
        '%24',      // $
        '%3f',      // ?
        '%3b',      // ;
        '%3d'       // =
    ];

    /**
     * Character set
     *
     * Will be overridden by the constructor.
     *
     * @var    string
     */
    protected $charset = 'UTF-8';

    /**
     * XSS Hash
     *
     * Random Hash for protecting URLs.
     *
     * @var    string
     */
    protected $xss_hash;

    /**
     * CSRF Hash
     *
     * Random hash for Cross Site Request Forgery protection cookie
     *
     * @var    string
     */
    protected $csrf_hash;

    /**
     * CSRF Expire time
     *
     * Expiration time for Cross Site Request Forgery protection cookie.
     * Defaults to two hours (in seconds).
     *
     * @var    int
     */
    protected $csrf_expire = 7200;

    /**
     * CSRF Token name
     *
     * Token name for Cross Site Request Forgery protection cookie.
     *
     * @var    string
     */
    protected $csrf_token_name = 'crystal_csrf_token';

    /**
     * CSRF Cookie name
     *
     * Cookie name for Cross Site Request Forgery protection cookie.
     *
     * @var    string
     */
    protected $csrf_cookie_name = 'crystal_csrf_token';

    /**
     * List of never allowed strings
     *
     * @var    array
     */
    protected $_never_allowed_str = [
        'document.cookie'   => '[removed]',
        'document.write'    => '[removed]',
        '.parentNode'       => '[removed]',
        '.innerHTML'        => '[removed]',
        '-moz-binding'      => '[removed]',
        '<!--'              => '&lt;!--',
        '-->'               => '--&gt;',
        '<![CDATA['         => '&lt;![CDATA[',
        '<comment>'         => '&lt;comment&gt;'
    ];

    /**
     * List of never allowed regex replacements
     *
     * @var    array
     */
    protected $never_allowed_regex = [
        'javascript\s*:',
        '(document|(document\.)?window)\.(location|on\w*)',
        'expression\s*(\(|&\#40;)', // CSS and IE
        'vbscript\s*:', // IE, surprise!
        'wscript\s*:', // IE
        'jscript\s*:', // IE
        'vbs\s*:', // IE
        'Redirect\s+30\d',
        "([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
    ];

    // --------------------------------------------------------------------
    // --------------------------------------------------------------------

    /**
     * Class constructor
     */
    public function __construct()
    {
        // Is CSRF protection enabled?

        if (Utils::boolValue(App::config()->get(Config::SECURITY_SECTION, 'csrf_protection')))
        {
            // CSRF config
            foreach (['csrf_expire', 'csrf_token_name', 'csrf_cookie_name'] as $key)
            {
                if ('' !== ($val = App::config()->get(Config::SECURITY_SECTION, $key)))
                {
                    $this->$key = $val;
                }
            }

            // Append application specific cookie prefix
            if ($cookie_prefix = App::config()->get(Config::COOKIE_SECTION, 'prefix'))
            {
                $this->csrf_cookie_name = $cookie_prefix . $this->csrf_cookie_name;
            }

            // Set the CSRF hash
            $this->csrfSetHash();
        }
        $this->charset = strtoupper(App::config()->get(Config::GLOBAL_SECTION, 'charset'));
    }

    /**
     * CSRF Verify
     *
     * @param $request_params
     * @return $this
     * @throws \Exception
     */
    public function csrfVerify(&$request_params)
    {
        // Do the tokens exist in both the _POST|_DELETE and _COOKIE arrays?
        if (!isset($request_params[$this->csrf_token_name], $_COOKIE[$this->csrf_cookie_name])
            OR $request_params[$this->csrf_token_name] !== $_COOKIE[$this->csrf_cookie_name]) // Do the tokens match?
        {
            throw new \RuntimeException('The action you have requested is not allowed', 403);
        }

        // We kill this since we're done and we don't want to pollute the _POST|_DELETE array
        unset($request_params[$this->csrf_token_name]);

        // Regenerate on every submission?
        $regenerate = Utils::boolValue(App::config()->get(Config::SECURITY_SECTION, 'csrf_regenerate'));
        if (($regenerate === true) && (Utils::isAjaxRequest() === false))
        {
            // Nothing should last forever
            unset($_COOKIE[$this->csrf_cookie_name]);
            $this->csrf_hash = NULL;
        }

        $this->csrfSetHash();
        $this->csrfSetCookie();

        return $this;
    }

    /**
     * CSRF Set Cookie
     *
     * @return $this
     */
    public function csrfSetCookie()
    {
        $expire = time() + $this->csrf_expire;
        $secure_cookie = Utils::boolValue(App::config()->get(Config::COOKIE_SECTION, 'secure'));

        if ($secure_cookie && (!Utils::isHTTPS()))
        {
            return false;
        }
        setcookie(
            $this->csrf_cookie_name,
            $this->csrf_hash,
            $expire,
            App::config()->get(Config::COOKIE_SECTION, 'path'),
            App::config()->get(Config::COOKIE_SECTION, 'domain'),
            $secure_cookie,
            true
        );
        return $this;
    }

    /**
     * Get CSRF Hash
     *
     * @return string CSRF hash
     */
    public function getCsrfHash()
    {
        return $this->csrf_hash;
    }

    /**
     * Get CSRF Token Name
     *
     * @return string CSRF token name
     */
    public function getCsrfTokenName()
    {
        return $this->csrf_token_name;
    }

    /**
     * XSS Clean
     *
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented.  This method does a fair amount of work but
     * it is extremely thorough, designed to prevent even the
     * most obscure XSS attempts.  Nothing is ever 100% foolproof,
     * of course, but I haven't been able to get anything passed
     * the filter.
     *
     * Note: Should only be used to deal with data upon submission.
     *     It's not something that should be used for general
     *     runtime processing.
     *
     * @link    http://channel.bitflux.ch/wiki/XSS_Prevention
     *         Based in part on some code and ideas from Bitflux.
     *
     * @link    http://ha.ckers.org/xss.html
     *         To help develop this script I used this great list of
     *        vulnerabilities along with a few other hacks I've
     *        harvested from examining vulnerabilities in other programs.
     *
     * @param   string|string[]  $str       Input data
     * @param   bool             $is_image  Whether the input is an image
     * @return  string
     */
    public function xssClean($str, $is_image = FALSE)
    {
        // Is the string an array?
        if (is_array($str))
        {
            while (list($key) = each($str))
            {
                $str[$key] = $this->xssClean($str[$key]);
            }
            return $str;
        }

        $str = Utils::removeInvisibleCharacters($str);

        /*
         * URL Decode
         *
         * Just in case stuff like this is submitted:
         *
         * <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
         *
         * Note: Use rawurldecode() so it does not remove plus signs
         */
        do {
            $str = rawurldecode($str);
        } while (preg_match('/%[0-9a-f]{2,}/i', $str));

        /*
         * Convert character entities to ASCII
         *
         * This permits our tests below to work reliably.
         * We only convert entities that are within tags since
         * these are the ones that will pose security problems.
         */
        $str = preg_replace_callback(
            "/[^a-z0-9>]+[a-z0-9]+=([\'\"]).*?\\1/si",
            [$this, 'convertAttribute'],
            $str
        );
        $str = preg_replace_callback(
            '/<\w+.*/si',
            [$this, 'decodeEntity'],
            $str
        );

        $str = Utils::removeInvisibleCharacters($str);

        /*
         * Convert all tabs to spaces
         *
         * This prevents strings like this: ja    vascript
         * NOTE: we deal with spaces between characters later.
         * NOTE: preg_replace was found to be amazingly slow here on
         * large blocks of data, so we use str_replace.
         */
        $str = str_replace("\t", ' ', $str);

        // Capture converted string for later comparison
        $converted_string = $str;

        // Remove Strings that are never allowed
        $str = $this->doNeverAllowed($str);

        /*
         * Makes PHP tags safe
         *
         * Note: XML tags are inadvertently replaced too:
         *
         * <?xml
         *
         * But it doesn't seem to pose a problem.
         */
        if ($is_image === true)
        {
            // Images have a tendency to have the PHP short opening and
            // closing tags every so often so we skip those and only
            // do the long opening tags.
            $str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
        } else {
            $str = str_replace(['<?', '?'.'>'], ['&lt;?', '?&gt;'], $str);
        }

        /*
         * Compact any exploded words
         *
         * This corrects words like:  j a v a s c r i p t
         * These words are compacted back to their correct state.
         */
        $words = [
            'javascript', 'expression', 'vbscript', 'jscript', 'wscript',
            'vbs', 'script', 'base64', 'applet', 'alert', 'document',
            'write', 'cookie', 'window', 'confirm', 'prompt'
        ];

        foreach ($words as $word)
        {
            $word = implode('\s*', str_split($word)).'\s*';

            // We only want to do this when it is followed by a non-word character
            // That way valid stuff like "dealer to" does not become "dealerto"
            $str = preg_replace_callback(
                '#('.substr($word, 0, -3).')(\W)#is',
                [$this, 'compactExplodedWords'],
                $str
            );
        }

        /*
         * Remove disallowed Javascript in links or img tags
         * We used to do some version comparisons and use of stripos(),
         * but it is dog slow compared to these simplified non-capturing
         * preg_match(), especially if the pattern exists in the string
         *
         * Note: It was reported that not only space characters, but all in
         * the following pattern can be parsed as separators between a tag name
         * and its attributes: [\d\s"\'`;,\/\=\(\x00\x0B\x09\x0C]
         * ... however, remove_invisible_characters() above already strips the
         * hex-encoded ones, so we'll skip them below.
         */
        do {
            $original = $str;

            if (preg_match('/<a/i', $str))
            {
                $str = preg_replace_callback(
                    '#<a[^a-z0-9>]+([^>]*?)(?:>|$)#si',
                    [$this, 'jsLinkRemoval'],
                    $str
                );
            }

            if (preg_match('/<img/i', $str))
            {
                $str = preg_replace_callback(
                    '#<img[^a-z0-9]+([^>]*?)(?:\s?/?>|$)#si',
                    [$this, 'jsImgRemoval'],
                    $str
                );
            }

            if (preg_match('/script|xss/i', $str))
            {
                $str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
            }
        }
        while ($original !== $str);

        unset($original);

        // Remove evil attributes such as style, onclick and xmlns
        $str = $this->removeEvilAttributes($str, $is_image);

        /*
         * Sanitize naughty HTML elements
         *
         * If a tag containing any of the words in the list
         * below is found, the tag gets converted to entities.
         *
         * So this: <blink>
         * Becomes: &lt;blink&gt;
         */
        $naughty = 'alert|prompt|confirm|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|button|select|isindex|layer|link|meta|keygen|object|plaintext|style|script|textarea|title|math|video|svg|xml|xss';
        $str = preg_replace_callback(
            '#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is',
            [$this, 'sanitizeNaughtyHtml'],
            $str
        );

        /*
         * Sanitize naughty scripting elements
         *
         * Similar to above, only instead of looking for
         * tags it looks for PHP and JavaScript commands
         * that are disallowed. Rather than removing the
         * code, it simply converts the parenthesis to entities
         * rendering the code un-executable.
         *
         * For example:    eval('some code')
         * Becomes:    eval&#40;'some code'&#41;
         */
        $str = preg_replace('#(alert|prompt|confirm|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si',
            '\\1\\2&#40;\\3&#41;',
            $str);

        // Final clean up
        // This adds a bit of extra precaution in case
        // something got through the above filters
        $str = $this->doNeverAllowed($str);

        /*
         * Images are Handled in a Special Way
         * - Essentially, we want to know that after all of the character
         * conversion is done whether any unwanted, likely XSS, code was found.
         * If not, we return TRUE, as the image is clean.
         * However, if the string post-conversion does not matched the
         * string post-removal of XSS, then it fails, as there was unwanted XSS
         * code found and removed/changed during processing.
         */
        if ($is_image === true)
        {
            return ($str === $converted_string);
        }

        return $str;
    }

    /**
     * XSS Hash
     *
     * Generates the XSS hash if needed and returns it.
     *
     * @return string XSS hash
     */
    public function xssHash()
    {
        if ($this->xss_hash === null)
        {
            $rand = $this->getRandomBytes(16);
            $this->xss_hash = ($rand === false)
                ? md5(uniqid(mt_rand(), true))
                : bin2hex($rand);
        }

        return $this->xss_hash;
    }

    // --------------------------------------------------------------------

    /**
     * Get random bytes
     *
     * @param   int     $length  Output length
     * @return  string
     */
    public function getRandomBytes($length)
    {
        if (empty($length) OR (!ctype_digit((string) $length)))
        {
            return false;
        }

        // Unfortunately, none of the following PRNGs is guaranteed to exist ...
        if (defined('MCRYPT_DEV_URANDOM') && ($output = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM)) !== false)
        {
            return $output;
        }


        if (is_readable('/dev/urandom') && ($fp = fopen('/dev/urandom', 'rb')) !== false)
        {
            // Try not to waste entropy ...
            Utils::isPHP('5.4') && stream_set_chunk_size($fp, $length);
            $output = fread($fp, $length);
            fclose($fp);
            if ($output !== false)
            {
                return $output;
            }
        }

        if (function_exists('openssl_random_pseudo_bytes'))
        {
            return openssl_random_pseudo_bytes($length);
        }

        return false;
    }

    /**
     * HTML Entities Decode
     *
     * A replacement for html_entity_decode()
     *
     * The reason we are not using html_entity_decode() by itself is because
     * while it is not technically correct to leave out the semicolon
     * at the end of an entity most browsers will still interpret the entity
     * correctly. html_entity_decode() does not convert entities without
     * semicolons, so we are left with our own little solution here. Bummer.
     *
     * @link    http://php.net/html-entity-decode
     *
     * @param    string    $str        Input
     * @param    string    $charset    Character set
     * @return   string
     */
    public function entityDecode($str, $charset = null)
    {
        if (strpos($str, '&') === FALSE)
        {
            return $str;
        }

        static $_entities;

        isset($charset) OR $charset = $this->charset;
        $flag = Utils::isPHP('5.4')
            ? ENT_COMPAT | ENT_HTML5
            : ENT_COMPAT;

        do {
            $str_compare = $str;

            // Decode standard entities, avoiding false positives
            if ($c = preg_match_all('/&[a-z]{2,}(?![a-z;])/i', $str, $matches))
            {
                if ( ! isset($_entities))
                {
                    $_entities = array_map('strtolower', get_html_translation_table(HTML_ENTITIES, $flag, $charset));

                    // If we're not on PHP 5.4+, add the possibly dangerous HTML 5
                    // entities to the array manually
                    if ($flag === ENT_COMPAT)
                    {
                        $_entities[':'] = '&colon;';
                        $_entities['('] = '&lpar;';
                        $_entities[')'] = '&rpar';
                        $_entities["\n"] = '&newline;';
                        $_entities["\t"] = '&tab;';
                    }
                }

                $replace = [];
                $matches = array_unique(array_map('strtolower', $matches[0]));
                for ($i = 0; $i < $c; $i++)
                {
                    if (($char = array_search($matches[$i].';', $_entities, true)) !== false)
                    {
                        $replace[$matches[$i]] = $char;
                    }
                }
                $str = str_ireplace(array_keys($replace), array_values($replace), $str);
            }
            // Decode numeric & UTF16 two byte entities
            $str = html_entity_decode(
                preg_replace('/(&#(?:x0*[0-9a-f]{2,5}(?![0-9a-f;]))|(?:0*\d{2,4}(?![0-9;])))/iS', '$1;', $str),
                $flag,
                $charset
            );
        }
        while ($str_compare !== $str);
        return $str;
    }

    /**
     * Sanitize Filename
     *
     * @param    string  $str            Input file name
     * @param    bool    $relative_path  Whether to preserve paths
     * @return   string
     */
    public function sanitizeFilename($str, $relative_path = false)
    {
        $bad = $this->filename_bad_chars;

        if ( ! $relative_path)
        {
            $bad[] = './';
            $bad[] = '/';
        }

        $str = Utils::removeInvisibleCharacters($str, false);

        do {
            $old = $str;
            $str = str_replace($bad, '', $str);
        } while ($old !== $str);

        return stripslashes($str);
    }

    /**
     * Compact Exploded Words
     *
     * Callback method for xss_clean() to remove whitespace from
     * things like 'j a v a s c r i p t'.
     *
     * @used-by  xssClean()
     * @param    array    $matches
     * @return   string
     */
    protected function compactExplodedWords($matches)
    {
        return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
    }

    /**
     * Remove Evil HTML Attributes (like event handlers and style)
     *
     * It removes the evil attribute and either:
     *
     *  - Everything up until a space. For example, everything between the pipes:
     *
     *    <code>
     *        <a |style=document.write('hello');alert('world');| class=link>
     *    </code>
     *
     *  - Everything inside the quotes. For example, everything between the pipes:
     *
     *    <code>
     *        <a |style="document.write('hello'); alert('world');"| class="link">
     *    </code>
     *
     * @param    string  $str        The string to check
     * @param    bool    $is_image    Whether the input is an image
     * @return   string  The string with the evil attributes removed
     */
    protected function removeEvilAttributes($str, $is_image)
    {
        $evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction', 'form', 'xlink:href');

        if ($is_image === TRUE)
        {
            /*
             * Adobe Photoshop puts XML metadata into JFIF images,
             * including namespacing, so we have to allow this for images.
             */
            unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
        }

        do {
            $count = 0;
            $attributes = array();

            // find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
            preg_match_all(
                '/(?<!\w)(' . implode('|', $evil_attributes) . ')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is',
                $str,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $attr)
            {
                $attributes[] = preg_quote($attr[0], '/');
            }

            // find occurrences of illegal attribute strings without quotes
            preg_match_all(
                '/(?<!\w)(' . implode('|', $evil_attributes) . ')\s*=\s*([^\s>]*)/is',
                $str,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $attr)
            {
                $attributes[] = preg_quote($attr[0], '/');
            }

            // replace illegal attribute strings that are inside an html tag
            if (count($attributes) > 0)
            {
                $str = preg_replace(
                    '/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)(' . implode('|', $attributes) . ')(.*?)([\s><]?)([><]*)/i',
                    '$1$2 $4$6$7$8',
                    $str,
                    -1,
                    $count
                );
            }
        } while ($count);

        return $str;
    }

    /**
     * Sanitize Naughty HTML
     *
     * Callback method for xss_clean() to remove naughty HTML elements.
     *
     * @used-by  xssClean()
     * @param    array    $matches
     * @return   string
     */
    protected function sanitizeNaughtyHtml($matches)
    {
        return '&lt;' . $matches[1] . $matches[2] . $matches[3] // encode opening brace
        // encode captured opening or closing brace to prevent recursive vectors:
        . str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
    }

    /**
     * JS Link Removal
     *
     * Callback method for xss_clean() to sanitize links.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on link-heavy strings.
     *
     * @used-by  xssClean()
     * @param    array    $match
     * @return   string
     */
    protected function jsLinkRemoval($match)
    {
        return str_replace($match[1],
            preg_replace('#href=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
                '',
                $this->filterAttributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]);
    }

    // --------------------------------------------------------------------

    /**
     * JS Image Removal
     *
     * Callback method for xss_clean() to sanitize image tags.
     *
     * This limits the PCRE backtracks, making it more performance friendly
     * and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in
     * PHP 5.2+ on image tag heavy strings.
     *
     * @used-by  xssClean()
     * @param    array    $match
     * @return   string
     */
    protected function jsImgRemoval($match)
    {
        return str_replace($match[1],
            preg_replace('#src=.*?(?:(?:alert|prompt|confirm)(?:\(|&\#40;)|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
                '',
                $this->filterAttributes(str_replace(array('<', '>'), '', $match[1]))
            ),
            $match[0]);
    }

    /**
     * Attribute Conversion
     *
     * @used-by  xssClean()
     * @param    array    $match
     * @return   string
     */
    protected function convertAttribute($match)
    {
        return str_replace(array('>', '<', '\\'), array('&gt;', '&lt;', '\\\\'), $match[0]);
    }

    /**
     * Filter Attributes
     *
     * Filters tag attributes for consistency and safety.
     *
     * @used-by    CI_Security::_js_img_removal()
     * @used-by    CI_Security::_js_link_removal()
     * @param    string    $str
     * @return    string
     */
    protected function filterAttributes($str)
    {
        $out = '';
        if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches))
        {
            foreach ($matches[0] as $match)
            {
                $out .= preg_replace('#/\*.*?\*/#s', '', $match);
            }
        }

        return $out;
    }

    /**
     * HTML Entity Decode Callback
     *
     * @used-by  xssClean()
     * @param    array    $match
     * @return   string
     */
    protected function decodeEntity($match)
    {
        // Protect GET variables in URLs
        // 901119URL5918AMP18930PROTECT8198
        $match = preg_replace(
            '|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-/]+)|i',
            $this->xssHash() . '\\1=\\2',
            $match[0]
        );

        // Decode, then un-protect URL GET vars
        return str_replace(
            $this->xssHash(),
            '&',
            $this->entityDecode($match, $this->charset)
        );
    }

    /**
     * Do Never Allowed
     *
     * @used-by   xssClean()
     * @param     string
     * @return    string
     */
    protected function doNeverAllowed($str)
    {
        $str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);
        foreach ($this->never_allowed_regex as $regex)
        {
            $str = preg_replace('#'.$regex.'#is', '[removed]', $str);
        }
        return $str;
    }

    /**
     * Set CSRF Hash and Cookie
     *
     * @return string
     */
    protected function csrfSetHash()
    {
        if ($this->csrf_hash === null)
        {
            // If the cookie exists we will use its value.
            // We don't necessarily want to regenerate it with
            // each page load since a page could contain embedded
            // sub-pages causing this feature to fail

            if (isset($_COOKIE[$this->csrf_cookie_name]) && is_string($_COOKIE[$this->csrf_cookie_name])
                && preg_match('#^[0-9a-f]{32}$#iS', $_COOKIE[$this->csrf_cookie_name]) === 1)
            {
                return $this->csrf_hash = $_COOKIE[$this->csrf_cookie_name];
            }

            $rand = $this->getRandomBytes(16);
            $this->csrf_hash = ($rand === false)
                ? md5(uniqid(mt_rand(), true))
                : bin2hex($rand);
        }

        return $this->csrf_hash;
    }
}