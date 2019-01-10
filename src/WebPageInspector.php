<?php

namespace webignition\WebPageInspector;

use Symfony\Component\DomCrawler\Crawler;
use webignition\WebResourceInterfaces\WebPageInterface;

class WebPageInspector
{
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

    public function __construct(WebPageInterface $webPage, ?CharacterSetExtractor $characterSetExtractor = null)
    {
        $this->webPage = $webPage;
        $webPageContent = trim($webPage->getContent());

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

            $webPageContent = trim($this->webPage->getContent());

            $this->crawler = new Crawler();
            $this->crawler->addContent($webPageContent, $contentTypeString);
        }

        return $this->crawler;
    }

    /**
     * An implementation of querySelector
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelector
     *
     * @param string $selectors
     *
     * @return \DOMElement|null
     */
    public function querySelector(string $selectors): ?\DOMElement
    {
        $crawler = $this->getCrawler();

        $filteredCrawler = $crawler->filter($selectors);

        if (0 === $filteredCrawler->count()) {
            return null;
        }

        return $filteredCrawler->getNode(0);
    }

    /**
     * An almost-implementation of querySelectorAll
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Document/querySelectorAll
     *
     * We can't return a \DOMNodeList (as is the case for the above docs). We return instead an array of \DOMElement
     *
     * @param string $selectors
     *
     * @return array
     */
    public function querySelectorAll(string $selectors): array
    {
        $crawler = $this->getCrawler();
        $filteredCrawler = $crawler->filter($selectors);

        $elements = [];

        foreach ($filteredCrawler as $domElement) {
            $elements[] = $domElement;
        }

        return $elements;
    }
}
