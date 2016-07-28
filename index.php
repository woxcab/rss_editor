<?php
header("Content-type: text/xml; charset=utf-8");


function allElementsAreArrays($array) {
    foreach ($array as $val) {
        if (!is_array($val)) {
            return false;
        }
    }
    return true;
}

function change_status_and_die($message) {
    http_response_code(502);
    return die($message);
}

function wrapInArray($arg) {
    if (!is_array($arg)) {
        return array($arg);
    } else {
        return $arg;
    }
}


class RSSEditor
{
    /**
     * @var string|null Plain source code of document
     */
    protected $plainText = null;
    /**
     * @var DOMDocument XML document as valid DOMDocument object
     */
    protected $xml;


    /**
     * Check pair matching of two objects.
     *
     * @throws InvalidArgumentException   Only one of two arguments is array or arrays do not have same length
     *
     * @param $firstObject mixed
     * @param $secondObject mixed
     */
    protected function checkPairArgumentsMatching($firstObject, $secondObject) {
        if (is_array($firstObject)) {
            if (!is_array($secondObject) || count($firstObject) !== count($secondObject)) {
                throw new InvalidArgumentException("both arrays must have equal lengths");
            }
        } else {
            if (is_array($secondObject)) {
                throw new InvalidArgumentException("both objects must be arrays or not at the same time");
            }
        }
    }

    /**
     * Apply action with passing two arguments.
     * If arguments are arrays with same length then apply action for each pair of these element by index.
     * Otherwise apply only single time with arguments.
     *
     * @param $action callable
     * @param $arguments mixed   Single argument or array of arguments for $action
     */
    protected function applyAction($action, $arguments) {
        if (is_array($arguments)) {
            if (allElementsAreArrays($arguments)) {
                foreach ($arguments as $args) {
                    call_user_func_array($action, $args);
                }
            } else {
                foreach ($arguments as $arg) {
                    call_user_func($action, $arg);
                }
            }
        } else {
            call_user_func($action, $arguments);
        }
    }

    /**
     * Rename all tags with given name (without attributes and children)
     *
     * @param $oldTagName string Current name of the tags
     * @param $newTagName string New name for the tags
     */
    protected function renameUnifiedTags($oldTagName, $newTagName) {
        $nodes = iterator_to_array($this->xml->getElementsByTagName($oldTagName));

        if (count($nodes) == 0) {
            change_status_and_die("No tags with name " . $oldTagName);
        };
        foreach ($nodes as $node) {
            $newNode = $this->xml->createElement($newTagName);
            $newNode->nodeValue = htmlspecialchars($node->nodeValue);
            $node->parentNode->appendChild($newNode);
            $node->parentNode->removeChild($node);
        };
    }

    /**
     * Remove all tags with given name (without children)
     *
     * @param $tagName string|string[] Name of the tag
     */
    protected function removeUnifiedTags($tagName) {
        $nodes = iterator_to_array($this->xml->getElementsByTagName($tagName));
        if (count($nodes) == 0) {
            change_status_and_die("No tags with name " . $tagName);
        };
        foreach ($nodes as $node) {
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Split content of the tags with given name using <br> CDATA block (without attributes)
     *
     * @param $tagName string Name of the old tag
     */
    protected function insertBreaksIntoUnifiedTags($tagName) {
        $nodes = $this->xml->getElementsByTagName($tagName);
        if ($nodes->length == 0) {
            change_status_and_die("No tags with name " . $tagName);
        }
        foreach ($nodes as $node) {
            $node->nodeValue = htmlspecialchars(preg_replace('/[\r\n]+/u', '<br/>', $node->nodeValue));
        }
    }


    /**
     * Mark content of the tags with given name as CDATA block (without attributes)
     *
     * @param $tagName string Name of the old tag
     */
    protected function cdataUnifiedTags($tagName) {
        $nodes = $this->xml->getElementsByTagName($tagName);
        if ($nodes->length == 0) {
            change_status_and_die("No tags with name " . $tagName);
        }
        foreach ($nodes as $node) {
            $CDATASection = $this->xml->createCDATASection($node->nodeValue);
            $node->nodeValue = '';
            $node->appendChild($CDATASection);
        }
    }


    /**
     * Replace each match of given regular expression using given replace rule (regular expression in PCRE notation).
     * Modifier 'u' is used for documents processing with UTF-8 encoding.
     * 
     * @param $replaceFrom string    Regex to find matches.
     * @param $replaceTo string    Regex to replace found matches.
     * @param $tagName string    Node name which regex is applied in.
     * @param $isCaseSensitive string    If false then modifier 'i' is applied (searching without case sensitive) else case sensitive searching.
     */
    protected function replaceContentInSameNameTags($replaceFrom, $replaceTo, $tagName, $isCaseSensitive) {
        $nodes = $this->xml->getElementsByTagName($tagName);
        if ($nodes->length == 0) {
            change_status_and_die("No tags with name " . $tagName);
        }
        $replaceFrom = str_replace('/', '\/', $replaceFrom);

        foreach ($nodes as $node) {
            if ($node->childNodes->length <= 2) {
                foreach($node->childNodes as $childNode) {
                    if ($childNode->nodeType === XML_TEXT_NODE || $childNode->nodeType === XML_CDATA_SECTION_NODE) {
                        $childNode->nodeValue = preg_replace($isCaseSensitive ? "/$replaceFrom/u" : "/$replaceFrom/iu",
                            $replaceTo, $childNode->nodeValue);
                    }
                }
            }
        }
    }

    /**
     * @param $url string
     * @param $context resource Stream context
     * @param $replaceAmpToSymbol boolean replace each '&amp;' to '&' symbol
     * @param $htmlEntitiesToNumeric boolean replace all HTML entities to its numeric representation
     * @param $addNamespace string  add additional RSS-namespace(s)
     */
    public function __construct($url, $context, $replaceAmpToSymbol,
                                $htmlEntitiesToNumeric, $addNamespace) {
        $this->plainText = file_get_contents($url, false, $context);
        if (!$this->plainText) {
            change_status_and_die("Cannot load RSS feed " . $url);
        }
        if ($replaceAmpToSymbol) {
            $this->plainText = preg_replace('/&amp;(?=[a-z]{2})/', "&", $this->plainText);
        }

        // replace  & into code if it is not encoded symbol
        $this->plainText = preg_replace("/&(?!([a-z]+|#[0-9]+);)/", "&#38;", $this->plainText);

        if ($htmlEntitiesToNumeric) {
            $this->htmlEntitiesToNumeric();
        }
        if (!is_null($addNamespace)) {
            $this->plainText = str_replace('<rss', '<rss ' . $addNamespace, $this->plainText);
        }

        $this->xml = new DOMDocument();
        if (!$this->xml->loadXML($this->plainText)) {
            change_status_and_die("Cannot get valid XML");
        }

    }

    /**
     * Convert HTML entities to its numeric representation for supporting in XML
     */
    protected function htmlEntitiesToNumeric() {
        $htmlEntities = array('&quot;', '&lt;', '&gt;', '&OElig;', '&oelig;', '&Scaron;', '&scaron;', '&Yuml;', '&circ;', '&tilde;', '&ensp;', '&emsp;', '&thinsp;', '&zwnj;', '&zwj;', '&lrm;', '&rlm;', '&ndash;', '&mdash;', '&lsquo;', '&rsquo;', '&sbquo;', '&ldquo;', '&rdquo;', '&bdquo;', '&dagger;', '&Dagger;', '&permil;', '&lsaquo;', '&rsaquo;', '&euro;', '&fnof;', '&Alpha;', '&Beta;', '&Gamma;', '&Delta;', '&Epsilon;', '&Zeta;', '&Eta;', '&Theta;', '&Iota;', '&Kappa;', '&Lambda;', '&Mu;', '&Nu;', '&Xi;', '&Omicron;', '&Pi;', '&Rho;', '&Sigma;', '&Tau;', '&Upsilon;', '&Phi;', '&Chi;', '&Psi;', '&Omega;', '&alpha;', '&beta;', '&gamma;', '&delta;', '&epsilon;', '&zeta;', '&eta;', '&theta;', '&iota;', '&kappa;', '&lambda;', '&mu;', '&nu;', '&xi;', '&omicron;', '&pi;', '&rho;', '&sigmaf;', '&sigma;', '&tau;', '&upsilon;', '&phi;', '&chi;', '&psi;', '&omega;', '&thetasym;', '&upsih;', '&piv;', '&bull;', '&hellip;', '&prime;', '&Prime;', '&oline;', '&frasl;', '&weierp;', '&image;', '&real;', '&trade;', '&alefsym;', '&larr;', '&uarr;', '&rarr;', '&darr;', '&harr;', '&crarr;', '&lArr;', '&uArr;', '&rArr;', '&dArr;', '&hArr;', '&forall;', '&part;', '&exist;', '&empty;', '&nabla;', '&isin;', '&notin;', '&ni;', '&prod;', '&sum;', '&minus;', '&lowast;', '&radic;', '&prop;', '&infin;', '&ang;', '&and;', '&or;', '&cap;', '&cup;', '&int;', '&there4;', '&sim;', '&cong;', '&asymp;', '&ne;', '&equiv;', '&le;', '&ge;', '&sub;', '&sup;', '&nsub;', '&sube;', '&supe;', '&oplus;', '&otimes;', '&perp;', '&sdot;', '&lceil;', '&rceil;', '&lfloor;', '&rfloor;', '&lang;', '&rang;', '&loz;', '&spades;', '&clubs;', '&hearts;', '&diams;', '&nbsp;', '&iexcl;', '&cent;', '&pound;', '&curren;', '&yen;', '&brvbar;', '&sect;', '&uml;', '&copy;', '&ordf;', '&laquo;', '&not;', '&shy;', '&reg;', '&macr;', '&deg;', '&plusmn;', '&sup2;', '&sup3;', '&acute;', '&micro;', '&para;', '&middot;', '&cedil;', '&sup1;', '&ordm;', '&raquo;', '&frac14;', '&frac12;', '&frac34;', '&iquest;', '&Agrave;', '&Aacute;', '&Acirc;', '&Atilde;', '&Auml;', '&Aring;', '&AElig;', '&Ccedil;', '&Egrave;', '&Eacute;', '&Ecirc;', '&Euml;', '&Igrave;', '&Iacute;', '&Icirc;', '&Iuml;', '&ETH;', '&Ntilde;', '&Ograve;', '&Oacute;', '&Ocirc;', '&Otilde;', '&Ouml;', '&times;', '&Oslash;', '&Ugrave;', '&Uacute;', '&Ucirc;', '&Uuml;', '&Yacute;', '&THORN;', '&szlig;', '&agrave;', '&aacute;', '&acirc;', '&atilde;', '&auml;', '&aring;', '&aelig;', '&ccedil;', '&egrave;', '&eacute;', '&ecirc;', '&euml;', '&igrave;', '&iacute;', '&icirc;', '&iuml;', '&eth;', '&ntilde;', '&ograve;', '&oacute;', '&ocirc;', '&otilde;', '&ouml;', '&divide;', '&oslash;', '&ugrave;', '&uacute;', '&ucirc;', '&uuml;', '&yacute;', '&thorn;', '&yuml;');
        $htmlEntitiesCode = array('&#34;', '&#60;', '&#62;', '&#338;', '&#339;', '&#352;', '&#353;', '&#376;', '&#710;', '&#732;', '&#8194;', '&#8195;', '&#8201;', '&#8204;', '&#8205;', '&#8206;', '&#8207;', '&#8211;', '&#8212;', '&#8216;', '&#8217;', '&#8218;', '&#8220;', '&#8221;', '&#8222;', '&#8224;', '&#8225;', '&#8240;', '&#8249;', '&#8250;', '&#8364;', '&#402;', '&#913;', '&#914;', '&#915;', '&#916;', '&#917;', '&#918;', '&#919;', '&#920;', '&#921;', '&#922;', '&#923;', '&#924;', '&#925;', '&#926;', '&#927;', '&#928;', '&#929;', '&#931;', '&#932;', '&#933;', '&#934;', '&#935;', '&#936;', '&#937;', '&#945;', '&#946;', '&#947;', '&#948;', '&#949;', '&#950;', '&#951;', '&#952;', '&#953;', '&#954;', '&#955;', '&#956;', '&#957;', '&#958;', '&#959;', '&#960;', '&#961;', '&#962;', '&#963;', '&#964;', '&#965;', '&#966;', '&#967;', '&#968;', '&#969;', '&#977;', '&#978;', '&#982;', '&#8226;', '&#8230;', '&#8242;', '&#8243;', '&#8254;', '&#8260;', '&#8472;', '&#8465;', '&#8476;', '&#8482;', '&#8501;', '&#8592;', '&#8593;', '&#8594;', '&#8595;', '&#8596;', '&#8629;', '&#8656;', '&#8657;', '&#8658;', '&#8659;', '&#8660;', '&#8704;', '&#8706;', '&#8707;', '&#8709;', '&#8711;', '&#8712;', '&#8713;', '&#8715;', '&#8719;', '&#8721;', '&#8722;', '&#8727;', '&#8730;', '&#8733;', '&#8734;', '&#8736;', '&#8743;', '&#8744;', '&#8745;', '&#8746;', '&#8747;', '&#8756;', '&#8764;', '&#8773;', '&#8776;', '&#8800;', '&#8801;', '&#8804;', '&#8805;', '&#8834;', '&#8835;', '&#8836;', '&#8838;', '&#8839;', '&#8853;', '&#8855;', '&#8869;', '&#8901;', '&#8968;', '&#8969;', '&#8970;', '&#8971;', '&#9001;', '&#9002;', '&#9674;', '&#9824;', '&#9827;', '&#9829;', '&#9830;', '&#160;', '&#161;', '&#162;', '&#163;', '&#164;', '&#165;', '&#166;', '&#167;', '&#168;', '&#169;', '&#170;', '&#171;', '&#172;', '', '&#174;', '&#175;', '&#176;', '&#177;', '&#178;', '&#179;', '&#180;', '&#181;', '&#182;', '&#183;', '&#184;', '&#185;', '&#186;', '&#187;', '&#188;', '&#189;', '&#190;', '&#191;', '&#192;', '&#193;', '&#194;', '&#195;', '&#196;', '&#197;', '&#198;', '&#199;', '&#200;', '&#201;', '&#202;', '&#203;', '&#204;', '&#205;', '&#206;', '&#207;', '&#208;', '&#209;', '&#210;', '&#211;', '&#212;', '&#213;', '&#214;', '&#215;', '&#216;', '&#217;', '&#218;', '&#219;', '&#220;', '&#221;', '&#222;', '&#223;', '&#224;', '&#225;', '&#226;', '&#227;', '&#228;', '&#229;', '&#230;', '&#231;', '&#232;', '&#233;', '&#234;', '&#235;', '&#236;', '&#237;', '&#238;', '&#239;', '&#240;', '&#241;', '&#242;', '&#243;', '&#244;', '&#245;', '&#246;', '&#247;', '&#248;', '&#249;', '&#250;', '&#251;', '&#252;', '&#253;', '&#254;', '&#255;');

        $this->plainText = str_replace($htmlEntities, $htmlEntitiesCode, $this->plainText);
    }


    /**
     * Rename all tags with given name (without attributes and children)
     *
     * @param $oldTagName string|string[] Current name of the tags
     * @param $newTagName string|string[] New name for the tags
     */
    public function renameTags($oldTagName, $newTagName) {
        $oldTagName = wrapInArray($oldTagName);
        $newTagName = wrapInArray($newTagName);
        try {
            $this->checkPairArgumentsMatching($oldTagName, $newTagName);
        } catch (InvalidArgumentException $e) {
            change_status_and_die("Invalid rename_from/rename_to parameters: " . $e->getMessage());
        }
        $this->applyAction(array($this, 'renameUnifiedTags'),
            array_map(function($ind) use ($oldTagName, $newTagName)
                {return array($oldTagName[$ind], $newTagName[$ind]);},
                range(0, count($oldTagName)-1)));
    }

    /**
     * Remove tags with given name
     *
     * @param $tagName string
     */
    public function removeTags($tagName) {
        $this->applyAction(array($this, 'removeUnifiedTags'), $tagName);
    }

    /**
     * Insert <br/> instead of line breaks in the content of tags with given name
     *
     * @param $tagName string
     */
    public function insertBreaksIntoTags($tagName)
    {
        $this->applyAction(array($this, 'insertBreaksIntoUnifiedTags'), $tagName);
    }

    /**
     * Wrap content of the tags with given name using CDATA
     *
     * @param $tagName string
     */
    public function cdataTags($tagName)
    {
        $this->applyAction(array($this, 'cdataUnifiedTags'), $tagName);
    }

    /**
     * Split the content of the tags with given name into tags with the same name
     * that contains lower cased words from source content that is extracted using CamelCase rules
     *
     * Example:
     *   before
     *     <tag>CamelCaseSTRING</tag>
     *   after
     *     <tag>camel</tag><tag>case</tag><tag>string</tag>
     *
     * @param $tagName string
     */
    public function splitCamelCase($tagName) {
        $nodes = iterator_to_array($this->xml->getElementsByTagName($tagName));
        if (count($nodes) == 0) {
            change_status_and_die("No tags with name " . $tagName);
        }
        foreach ($nodes as $node) {
            $matches = array();
            preg_match_all("/((?:^|[A-ZА-Я])[a-zа-я]+|[A-ZА-Я]+(?=[А-Я]|$))/u",
                $node->nodeValue, $matches);
            foreach ($matches[0] as $match) {
                $newNode = $this->xml->createElement($tagName);
                $newNode->nodeValue = mb_strtolower($match);
                $node->parentNode->appendChild($newNode);
            }
            $node->parentNode->removeChild($node);
        }
    }

    /**
     * Content replacing based on regular expressions in PCRE notation.
     * Replace each match of given regular expression using given replace rule.
     * For documents with UTF-8 encoding.
     *
     * @param $replaceFrom string|string[]    Regex to find matches.
     * @param $replaceTo string|string[]    Regex to replace found matches.
     * @param $replaceInTag string|string[]    Node name which regex is applied in.
     * @param $isCaseSensitive string|string[]    If false then modifier 'i' is applied (searching without case sensitive) else case sensitive searching.
     */
    public function replaceContent($replaceFrom, $replaceTo, $replaceInTag, $isCaseSensitive) {
        $replaceFrom = wrapInArray($replaceFrom);
        $replaceTo = wrapInArray($replaceTo);
        try {
            $this->checkPairArgumentsMatching($replaceFrom, $replaceTo);
        } catch (InvalidArgumentException $e) {
            change_status_and_die("Invalid replace_from/replace_to parameters: " . $e->getMessage());
        }
        if (is_array($replaceInTag)) {
            try {
                $this->checkPairArgumentsMatching($replaceFrom, $replaceInTag);
            } catch (InvalidArgumentException $e) {
                change_status_and_die("Invalid replace_from/replace_to/replace_in parameters: " . $e->getMessage());
            }
        } else {
            $replaceInTag = array_fill(0, count($replaceFrom), $replaceInTag);
        }
        if (is_array($isCaseSensitive)) {
            try {
                $this->checkPairArgumentsMatching($replaceFrom, $isCaseSensitive);
            } catch (InvalidArgumentException $e) {
                change_status_and_die("Invalid replace_from/replace_to/replace_sens parameters: " . $e->getMessage());
            }
        } else {
            $isCaseSensitive = array_fill(0, count($replaceFrom), $isCaseSensitive);
        }
        $this->applyAction(array($this, 'replaceContentInSameNameTags'),
            array_map(function($ind) use ($replaceFrom, $replaceTo, $replaceInTag, $isCaseSensitive)
            { return array($replaceFrom[$ind], $replaceTo[$ind], $replaceInTag[$ind], $isCaseSensitive[$ind]);},
                range(0, count($replaceFrom)-1)));
    }

    /**
     * Get current XML as a plain text
     *
     * @return string   current XML as a plain text
     */
    public function getXMLAsText()
    {
        return $this->xml->saveXML();
    }

}


$opts = array(
    'http' => array(
        'user_agent' => 'Mozilla/5.0 (Windows NT 6.1; rv:21.0) Gecko/20130401 Firefox/21.0',
    )
);
$context = stream_context_create($opts);
libxml_set_streams_context($context);

$url = isset($_GET['url']) ? $_GET['url'] : change_status_and_die("No url of RSS for processing");
if (!(isset($_GET['amp']) ||
      isset($_GET['remove']) ||
      isset($_GET['break']) ||
      isset($_GET['split']) ||
      isset($_GET['cdata'])) &&
    ((isset($_GET['rename_from']) || isset($_GET['rename_to'])) &&
      (!isset($_GET['rename_from']) || !isset($_GET['rename_to'])) ||
    (isset($_GET['replace_from']) || isset($_GET['replace_to']) || isset($_GET['replace_in'])) &&
      (!isset($_GET['replace_from']) || !isset($_GET['replace_to']) || !isset($_GET['replace_in'])))
) {
    change_status_and_die("Incomplete parameters (must be rename_from and rename_to or remove or break or cdata or replace_from and replace_to and replace_in");
}

$feed = new RSSEditor($url, $context, isset($_GET['amp']), true, @$_GET['add_namespace']);

if (isset($_GET['remove'])) {
    $feed->removeTags($_GET['remove']);
}

if (isset($_GET['rename_from']) && isset($_GET['rename_to'])) {
    $feed->renameTags($_GET['rename_from'], $_GET['rename_to']);
}

if (isset($_GET['split'])) {
    $feed->splitCamelCase($_GET['split']);
}

if (isset($_GET['break'])) {
    $feed->insertBreaksIntoTags($_GET['break']);
}

if (isset($_GET['cdata'])) {
    $feed->cdataTags($_GET['cdata']);
}


if (isset($_GET['replace_from']) && isset($_GET['replace_to']) && isset($_GET['replace_in'])) {
    $feed->replaceContent($_GET['replace_from'], $_GET['replace_to'],
        $_GET['replace_in'], isset($_GET['replace_sens']));
}

echo $feed->getXMLAsText();