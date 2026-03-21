<?php

declare(strict_types=1);

namespace App\Services;

final class SvgSanitiser
{
    /** Tags that must be removed entirely (including children). */
    private const BANNED_TAGS = ['script', 'iframe', 'object', 'embed', 'link', 'style', 'meta', 'base'];

    /** Attribute prefixes that indicate event handlers or data URIs. */
    private const BANNED_ATTR_PATTERNS = [
        '/^on/i',          // onclick, onload, onerror, …
        '/^xlink:href$/i', // external resource refs
        '/^href$/i',       // anchor hrefs in SVG
    ];

    public static function sanitise(string $svg): string
    {
        $dom = new \DOMDocument;

        // Suppress warnings from malformed XML
        $prev = libxml_use_internal_errors(true);
        $dom->loadXML($svg, LIBXML_NONET | LIBXML_NOBLANKS);
        libxml_use_internal_errors($prev);

        $root = $dom->documentElement;

        if ($root === null) {
            return '';
        }

        self::walkNode($root);

        $sanitised = $dom->saveXML($dom->documentElement);

        return $sanitised !== false ? $sanitised : '';
    }

    private static function walkNode(\DOMNode $node): void
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return;
        }

        /** @var \DOMElement $node */
        $toRemove = [];

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $tag = strtolower($child->localName ?? '');
                if (in_array($tag, self::BANNED_TAGS, true)) {
                    $toRemove[] = $child;
                } else {
                    /** @var \DOMElement $child */
                    self::stripAttributes($child);
                    self::walkNode($child);
                }
            }
        }

        foreach ($toRemove as $el) {
            $node->removeChild($el);
        }
    }

    private static function stripAttributes(\DOMElement $el): void
    {
        $toRemove = [];

        foreach ($el->attributes as $attr) {
            $name = strtolower($attr->name);
            foreach (self::BANNED_ATTR_PATTERNS as $pattern) {
                if (preg_match($pattern, $name)) {
                    $toRemove[] = $attr->name;
                    break;
                }
            }

            // Strip javascript: in any attribute value
            if (str_contains(strtolower($attr->value), 'javascript:')) {
                $toRemove[] = $attr->name;
            }
        }

        foreach ($toRemove as $name) {
            $el->removeAttribute($name);
        }
    }
}
