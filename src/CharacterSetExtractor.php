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
     *
     * @throws UnparseableContentTypeException
     */
    public function extract(string $content)
    {
        $charset = null;
        $characterSetCrawler = new Crawler($content);

        $contentType = $this->getContentTypeFromMetaHttpEquivElements($characterSetCrawler);
        $charset = $this->getCharacterSetFromContentType($contentType);

        if (empty($charset)) {
            $charset = $this->getCharacterSetFromMetaCharsetElement($characterSetCrawler);
        }

        return $charset;
    }

    /**
     * @param string $contentType
     *
     * @return string|string
     *
     * @throws UnparseableContentTypeException
     */
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
            throw new UnparseableContentTypeException($contentType);
        }

        return $charset;
    }

    private function getContentTypeFromMetaHttpEquivElements(Crawler $characterSetCrawler): ?string
    {
        $identifierAttributeNames = [
            'http-equiv',
            'name', // invalid but happens
        ];

        $contentType = null;

        foreach ($identifierAttributeNames as $identifierAttributeName) {
            if (empty($contentType)) {
                $contentType = $this->getContentTypeStringByMetaElement(
                    $characterSetCrawler,
                    $identifierAttributeName
                );
            }
        }

        return $contentType;
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

    private function getContentTypeStringByMetaElement(
        Crawler $characterSetCrawler,
        string $identifierAttributeName
    ): ?string {
        $selector = 'meta[' . $identifierAttributeName . ']';

        $metaHttpEquivCrawler = $characterSetCrawler->filter($selector);

        $callable = function (Crawler $metaHttpEquivNode) use ($identifierAttributeName) {
            $identifierAttributeValue = strtolower($metaHttpEquivNode->attr($identifierAttributeName));
            $contentValue = strtolower(trim($metaHttpEquivNode->attr('content')));

            if ('content-type' === $identifierAttributeValue && !empty($contentValue)) {
                return $contentValue;
            }

            return null;
        };

        $metaHttpEquivCrawlerResults = array_filter($metaHttpEquivCrawler->each($callable));

        return reset($metaHttpEquivCrawlerResults);
    }
}
