<?php

namespace webignition\WebPageInspector\Tests;

use webignition\WebPageInspector\CharacterSetExtractor;
use webignition\WebPageInspector\UnparseableContentTypeException;

class CharacterSetExtractorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider extractUnparseableContentTypeDataProvider
     *
     * @param string $content
     * @param string $expectedExceptionMessage
     * @param string $expectedContentType
     */
    public function testExtractUnparseableContentType(
        string $content,
        string $expectedExceptionMessage,
        string $expectedContentType
    ) {
        $characterSetExtractor = new CharacterSetExtractor();

        try {
            $characterSetExtractor->extract($content);
            $this->fail(UnparseableContentTypeException::class . ' not thrown');
        } catch (UnparseableContentTypeException $unparseableContentTypeException) {
            $this->assertEquals(UnparseableContentTypeException::CODE, $unparseableContentTypeException->getCode());
            $this->assertEquals($expectedExceptionMessage, $unparseableContentTypeException->getMessage());
            $this->assertEquals($expectedContentType, $unparseableContentTypeException->getContentType());
        }
    }

    public function extractUnparseableContentTypeDataProvider(): array
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
     * @dataProvider extractSuccessDataProvider
     *
     * @param string $content
     * @param string|null $expectedCharacterSet
     *
     * @throws UnparseableContentTypeException
     */
    public function testExtractSetSuccess(string $content, ?string $expectedCharacterSet)
    {
        $characterSetExtractor = new CharacterSetExtractor();

        $this->assertSame($expectedCharacterSet, $characterSetExtractor->extract($content));
    }

    public function extractSuccessDataProvider(): array
    {
        FixtureLoader::$fixturePath = __DIR__ . '/Fixtures';

        return [
            'empty document' => [
                'content' => '',
                'expectedCharacterSet' => null,
            ],
            'meta http-equiv="Content-Type" (valid)' => [
                'content' => FixtureLoader::load('empty-document-with-valid-http-equiv-content-type.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta http-equiv="Content-Type" (valid, empty)' => [
                'content' => FixtureLoader::load('empty-document-with-empty-http-equiv-content-type.html'),
                'expectedCharacterSet' => null,
            ],
            'meta http-equiv="content-type" (valid)' => [
                'content' => FixtureLoader::load('empty-document-with-valid-http-equiv-content-type-lowercase.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta name="Content-Type" (valid value, malformed)' => [
                'content' => FixtureLoader::load('empty-document-with-malformed-http-equiv-content-type.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta charset="foo" (invalid value, well-formed)' => [
                'content' => FixtureLoader::load('empty-document-with-invalid-meta-charset.html'),
                'expectedCharacterSet' => 'foo',
            ],
        ];
    }
}
