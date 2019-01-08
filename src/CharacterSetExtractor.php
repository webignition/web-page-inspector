<?php

namespace webignition\WebPageInspector;

use Symfony\Component\DomCrawler\Crawler;
use webignition\InternetMediaType\Parser\ParseException;
use webignition\InternetMediaType\Parser\Parser as InternetMediaTypeParser;

class CharacterSetExtractor
{
    /**
     * @param string $content
     *
     * @return string|null
     */
    public function extract(string $content)
    {
        $charset = null;
        $characterSetCrawler = new Crawler($content);

        $contentTypes = $this->getContentTypesFromMetaHttpEquivElements($characterSetCrawler);

        foreach ($contentTypes as $contentType) {
            if (empty($charset)) {
                $charset = $this->getCharacterSetFromContentType($contentType);
            }
        }

        if (empty($charset)) {
            $charset = $this->getCharacterSetFromMetaCharsetElement($characterSetCrawler);
        }

        return $charset;
    }

    private function getCharacterSetFromContentType(?string $contentType): ?string
    {
        $charset = null;

        if (empty($contentType)) {
            return $charset;
        }

        $mediaTypeParser = new InternetMediaTypeParser();
        $mediaTypeParser->setIgnoreInvalidAttributes(true);
        $mediaTypeParser->setAttemptToRecoverFromInvalidInternalCharacter(true);

        try {
            $mediaType = $mediaTypeParser->parse($contentType);

            if ($mediaType->hasParameter('charset')) {
                $charset = (string)$mediaType->getParameter('charset')->getValue();
            }
        } catch (ParseException $parseException) {
            // Intentionally swallow exception
        }

        return $charset;
    }

    private function getContentTypesFromMetaHttpEquivElements(Crawler $characterSetCrawler): array
    {
        $contentTypes = [];

        $identifierAttributeNames = [
            'http-equiv',
            'name', // invalid but happens
        ];

        foreach ($identifierAttributeNames as $identifierAttributeName) {
            $contentTypes = array_merge($contentTypes, $this->getContentTypeStringsByMetaElement(
                $characterSetCrawler,
                $identifierAttributeName
            ));
        }

        return $contentTypes;
    }

    private function getCharacterSetFromMetaCharsetElement(Crawler $characterSetCrawler): ?string
    {
        $metaCharsetCrawler = $characterSetCrawler->filter('meta[charset]');

        $callable = function (Crawler $metaCharsetNode) {
            return strtolower($metaCharsetNode->attr('charset'));
        };

        $metaCharsetCrawlerResults = array_filter($metaCharsetCrawler->each($callable));

        $charset = reset($metaCharsetCrawlerResults);

        return $charset ? $charset : null;
    }

    private function getContentTypeStringsByMetaElement(
        Crawler $characterSetCrawler,
        string $identifierAttributeName
    ): array {
        $contentTypes = [];

        $selector = 'meta[' . $identifierAttributeName . ']';

        $metaHttpEquivCrawler = $characterSetCrawler->filter($selector);

        $callable = function (Crawler $metaHttpEquivNode) use ($identifierAttributeName, &$contentTypes) {
            $identifierAttributeValue = strtolower($metaHttpEquivNode->attr($identifierAttributeName));
            $contentValue = strtolower(trim($metaHttpEquivNode->attr('content')));

            if ('content-type' === $identifierAttributeValue && !empty($contentValue)) {
                $contentTypes[] = $contentValue;
            }
        };

        $metaHttpEquivCrawler->each($callable);

        return $contentTypes;
    }
}
