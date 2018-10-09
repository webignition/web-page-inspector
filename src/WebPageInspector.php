<?php

namespace webignition\WebPageInspector;

use Symfony\Component\DomCrawler\Crawler;
use webignition\WebResourceInterfaces\WebPageInterface;

class WebPageInspector
{
    const CHARSET_GB2312 = 'GB2312';
    const CHARSET_BIG5 = 'BIG5';
    const CHARSET_UTF_8 = 'UTF-8';

    /**
     * @var WebPageInterface
     */
    private $webPage;

    /**
     * @var string
     */
    private $characterSet;

    /**
     * @var Crawler
     */
    private $crawler;

    /**
     * @param WebPageInterface $webPage
     * @param CharacterSetExtractor|null $characterSetExtractor
     *
     * @throws UnparseableContentTypeException
     */
    public function __construct(WebPageInterface $webPage, ?CharacterSetExtractor $characterSetExtractor = null)
    {
        $this->webPage = $webPage;
        $webPageContent = (string)$webPage->getContent();

        $characterSetExtractor = (empty($characterSetExtractor))
            ? new CharacterSetExtractor()
            : $characterSetExtractor;

        $this->characterSet = $characterSetExtractor->extract($webPageContent);
    }

    public function getCharacterSet(): ?string
    {
        return $this->characterSet;
    }

    public function getCrawler()
    {
        if (empty($this->crawler)) {
            $contentTypeString = $this->webPage->getContentType()->getTypeSubtypeString();
            if (!empty($this->characterSet)) {
                $contentTypeString .= '; charset=' . $this->characterSet;
            }

            $webPageContent = $this->webPage->getContent();

            $this->crawler = new Crawler();
            $this->crawler->addContent($webPageContent, $contentTypeString);
        }

        return $this->crawler;
    }
}
