<?php

namespace webignition\WebPageInspector\Tests;

use Symfony\Component\DomCrawler\Crawler;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\CharacterSetExtractor;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResourceInterfaces\WebPageInterface;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCharacterSetUnparseableContentTypeDataProvider
     *
     * @param WebPageInterface $webPage
     * @param string $expectedExceptionMessage
     * @param string $expectedContentType
     */
    public function testCreateUnparseableContentType(
        WebpageInterface $webPage,
        string $expectedExceptionMessage,
        string $expectedContentType
    ) {
        try {
            new WebPageInspector($webPage);
            $this->fail(UnparseableContentTypeException::class . ' not thrown');
        } catch (UnparseableContentTypeException $unparseableContentTypeException) {
            $this->assertEquals(UnparseableContentTypeException::CODE, $unparseableContentTypeException->getCode());
            $this->assertEquals($expectedExceptionMessage, $unparseableContentTypeException->getMessage());
            $this->assertEquals($expectedContentType, $unparseableContentTypeException->getContentType());
        }
    }

    public function getCharacterSetUnparseableContentTypeDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'meta name="Content-Type" (unparseable value, malformed)' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('empty-document-with-unparseable-http-equiv-content-type.html')
                ),
                'expectedExceptionMessage' => 'Unparseable content type "f o o"',
                'expectedContentType' => 'f o o',
            ],
        ];
    }

    /**
     * @dataProvider getCharacterSetDataProvider
     *
     * @param WebPageInterface $webPage
     *
     * @throws UnparseableContentTypeException
     */
    public function testGetCharacterSet(WebPageInterface $webPage)
    {
        $characterSet = 'utf-8';

        $characterSetExtractor = \Mockery::mock(CharacterSetExtractor::class);
        $characterSetExtractor
            ->shouldReceive('extract')
            ->with($webPage->getContent())
            ->andReturn($characterSet);

        $webPageInspector = new WebPageInspector($webPage, $characterSetExtractor);

        $this->assertEquals($characterSet, $webPageInspector->getCharacterSet());
    }

    public function getCharacterSetDataProvider(): array
    {
        return [
            'null content' => [
                'webPage' => $this->createWebPage(),
            ],
            'empty content' => [
                'webPage' => $this->createWebPage(''),
            ],
        ];
    }

    /**
     * @dataProvider useCrawlerDataProvider
     *
     * @param WebPageInterface $webPage
     * @param string $selector
     * @param mixed $eachFunction
     * @param array $expectedFoundValues
     *
     * @throws UnparseableContentTypeException
     */
    public function testUseCrawler(
        WebPageInterface $webPage,
        string $selector,
        callable $eachFunction,
        array $expectedFoundValues
    ) {
        $webPageInspector = new WebPageInspector($webPage);

        $crawler = $webPageInspector->getCrawler();

        $this->assertEquals($expectedFoundValues, $crawler->filter($selector)->each($eachFunction));
    }

    public function useCrawlerDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'script src values' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selector' => 'script[src]',
                'eachFunction' => function (Crawler $crawler) {
                    return $crawler->attr('src');
                },
                'expectedFoundValues' => [
                    '//example.com/foo.js',
                    '/vendor/example/bar.js',

                ],
            ],
            'script values' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selector' => 'script:not([src])',
                'eachFunction' => function (Crawler $crawler) {
                    return trim($crawler->text());
                },
                'expectedFoundValues' => [
                    'var firstFromHead = true;',
                    'var secondFromHead = true;',
                    'var firstFromBody = true;',
                ],
            ],
            'script values from charset=gb2313 content' => [
                'webPage' => $this->createWebPage(
                    FixtureLoader::load('document-with-script-elements-charset=gb2312.html')
                ),
                'selector' => 'script:not([src])',
                'eachFunction' => function (Crawler $crawler) {
                    return trim($crawler->text());
                },
                'expectedFoundValues' => [
                    'var firstFromHead = true;',
                    'var secondFromHead = true;',
                    'var firstFromBody = true;',
                ],
            ],
            'script values from charset=big5 content' => [
                 'webPage' => $this->createWebPage(FixtureLoader::load('document-with-big5-charset.html')),
                'selector' => 'script',
                'eachFunction' => function (Crawler $crawler) {
                    return trim($crawler->text());
                },
                'expectedFoundValues' => [],
            ],
        ];
    }

    /**
     * @dataProvider querySelectorDataProvider
     *
     * @param WebPageInterface $webPage
     * @param string $selectors
     * @param string|null$expectedElement
     *
     * @throws UnparseableContentTypeException
     */
    public function testQuerySelector(WebPageInterface $webPage, string $selectors, ?string $expectedElement)
    {
        $inspector = new WebPageInspector($webPage);
        $element = $inspector->querySelector($selectors);

        if (empty($element)) {
            $this->assertNull($expectedElement);
        } else {
            $this->assertSame($expectedElement, $element->ownerDocument->saveHTML($element));
        }
    }

    public function querySelectorDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty content, empty selector' => [
                'webPage' => $this->createWebPage(),
                'selectors' => '',
                'expectedElement' => null,
            ],
            'empty content, has selector' => [
                'webPage' => $this->createWebPage(),
                'selectors' => '.foo',
                'expectedElement' => null,
            ],
            'document with script elements, non-matching selector' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selectors' => '.foo',
                'expectedElement' => null,
            ],
            'document with script elements, matching selector' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selectors' => 'script[src]',
                'expectedElement' => '<script type="text/javascript" src="//example.com/foo.js"></script>',
            ],
            'from content with leading null bytes' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('leading-null-bytes.html')),
                'selectors' => 'a[href]',
                'expectedElement' => '<a href="/foo">Foo</a>',
            ],
        ];
    }

    /**
     * @dataProvider querySelectorAllDataProvider
     *
     * @param WebPageInterface $webPage
     * @param string $selectors
     * @param array $expectedElements
     *
     * @throws UnparseableContentTypeException
     */
    public function testQuerySelectorAll(WebPageInterface $webPage, string $selectors, array $expectedElements)
    {
        $inspector = new WebPageInspector($webPage);
        $elements = $inspector->querySelectorAll($selectors);

        if (empty($expectedElements)) {
            $this->assertEmpty($elements);
        } else {
            $elementStrings = [];

            foreach ($elements as $element) {
                /* @var \DOMElement $element */
                $elementStrings[] = $element->ownerDocument->saveHTML($element);
            }

            $this->assertSame($expectedElements, $elementStrings);
        }

        $this->assertTrue(true);
    }

    public function querySelectorAllDataProvider()
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty content, empty selector' => [
                'webPage' => $this->createWebPage(),
                'selectors' => '',
                'expectedElements' => [],
            ],
            'empty content, has selector' => [
                'webPage' => $this->createWebPage(),
                'selectors' => '.foo',
                'expectedElements' => [],
            ],
            'document with script elements, non-matching selector' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selectors' => '.foo',
                'expectedElements' => [],
            ],
            'document with script elements, matching script selector' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selectors' => 'script',
                'expectedElements' => [
                    '<script type="text/javascript" src="//example.com/foo.js"></script>',
                    '<script type="text/javascript" src="/vendor/example/bar.js"></script>',
                    '<script type="text/javascript">var firstFromHead = true;</script>',
                    '<script type="text/javascript">var secondFromHead = true;</script>',
                    '<script type="text/javascript">var firstFromBody = true;</script>',
                ],
            ],
            'document with script elements, matching script[src] selector' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selectors' => 'script[src]',
                'expectedElements' => [
                    '<script type="text/javascript" src="//example.com/foo.js"></script>',
                    '<script type="text/javascript" src="/vendor/example/bar.js"></script>',
                ],
            ],
            'document with script elements, matching script:not([src]) selector' => [
                'webPage' => $this->createWebPage(FixtureLoader::load('document-with-script-elements.html')),
                'selectors' => 'script:not([src])',
                'expectedElements' => [
                    '<script type="text/javascript">var firstFromHead = true;</script>',
                    '<script type="text/javascript">var secondFromHead = true;</script>',
                    '<script type="text/javascript">var firstFromBody = true;</script>',
                ],
            ],
        ];
    }


    private function createWebPage(?string $content = null)
    {
        $contentType = \Mockery::mock(InternetMediaTypeInterface::class);
        $contentType
            ->shouldReceive('getTypeSubtypeString')
            ->andReturn('text/html');

        $webPage = \Mockery::mock(WebPageInterface::class);
        $webPage
            ->shouldReceive('getContent')
            ->andReturn($content);

        $webPage
            ->shouldReceive('getContentType')
            ->andReturn($contentType);

        return $webPage;
    }
}
