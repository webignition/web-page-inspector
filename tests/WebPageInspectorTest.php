<?php

namespace webignition\WebPageInspector\Tests;

use Symfony\Component\DomCrawler\Crawler;
use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\WebPageInspector\CharacterSetExtractor;
use webignition\WebPageInspector\UnparseableContentTypeException;
use webignition\WebPageInspector\WebPageInspector;
use webignition\WebResource\TestingTools\FixtureLoader;
use webignition\WebResourceInterfaces\WebPageInterface;

class ParserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider getCharacterSetUnparseableContentTypeDataProvider
     *
     * @param string $content
     * @param string $expectedExceptionMessage
     * @param string $expectedContentType
     */
    public function testCreateUnparseableContentType(
        string $content,
        string $expectedExceptionMessage,
        string $expectedContentType
    ) {
        $webPage = \Mockery::mock(WebPageInterface::class);
        $webPage
            ->shouldReceive('getContent')
            ->andReturn($content);

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
                'content' => FixtureLoader::load('empty-document-with-unparseable-http-equiv-content-type.html'),
                'expectedExceptionMessage' => 'Unparseable content type "f o o"',
                'expectedContentType' => 'f o o',
            ],
        ];
    }

    /**
     * @throws UnparseableContentTypeException
     */
    public function testGetCharacterSet()
    {
        $characterSet = 'utf-8';
        $content = '';

        $webPage = \Mockery::mock(WebPageInterface::class);
        $webPage
            ->shouldReceive('getContent')
            ->andReturn($content);

        $characterSetExtractor = \Mockery::mock(CharacterSetExtractor::class);
        $characterSetExtractor
            ->shouldReceive('extract')
            ->with($content)
            ->andReturn($characterSet);

        $webPageInspector = new WebPageInspector($webPage, $characterSetExtractor);

        $this->assertEquals($characterSet, $webPageInspector->getCharacterSet());
    }

    /**
     * @dataProvider useCrawlerDataProvider
     *
     * @param string $content
     * @param string $selector
     * @param mixed $eachFunction
     * @param array $expectedFoundValues

     * @throws UnparseableContentTypeException
     */
    public function testUseCrawler(
        string $content,
        string $selector,
        callable $eachFunction,
        array $expectedFoundValues
    ) {
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

        $webPageInspector = new WebPageInspector($webPage);

        $crawler = $webPageInspector->getCrawler();

        $this->assertEquals($expectedFoundValues, $crawler->filter($selector)->each($eachFunction));
    }

    public function useCrawlerDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'script src values' => [
                'content' => FixtureLoader::load('document-with-script-elements.html'),
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
                'content' => FixtureLoader::load('document-with-script-elements.html'),
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
                'content' => FixtureLoader::load('document-with-script-elements-charset=gb2312.html'),
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
                'content' => FixtureLoader::load('document-with-big5-charset.html'),
                'selector' => 'script',
                'eachFunction' => function (Crawler $crawler) {
                    return trim($crawler->text());
                },
                'expectedFoundValues' => [],
            ],
        ];
    }
}
