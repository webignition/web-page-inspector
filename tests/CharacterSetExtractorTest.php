<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\WebPageInspector\Tests;

use webignition\WebPageInspector\CharacterSetExtractor;

class CharacterSetExtractorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider extractSuccessDataProvider
     */
    public function testExtractSuccess(string $content, ?string $expectedCharacterSet)
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
            'unparseable content type string' => [
                'content' => FixtureLoader::load('empty-document-with-unparseable-http-equiv-content-type.html'),
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
            'meta charset="UTF-8" with non-matching meta name="" elements' => [
                'content' => FixtureLoader::load('empty-document-with-meta-charset-and-non-matching-meta-name.html'),
                'expectedCharacterSet' => 'utf-8',
            ],
            'meta http-equiv="Content-Type" incorrectly-encoded gb2312 content' => [
                'content' => FixtureLoader::load('document-with-script-elements-charset=gb2312.html'),
                'expectedCharacterSet' => 'gb2312',
            ],
            'meta http-equiv="Content-Type" gb2312 content converted to utf-8' => [
                'content' => mb_convert_encoding(
                    FixtureLoader::load('document-with-script-elements-charset=gb2312.html'),
                    'utf-8',
                    'gb2312'
                ),
                'expectedCharacterSet' => 'gb2312',
            ],
        ];
    }
}
