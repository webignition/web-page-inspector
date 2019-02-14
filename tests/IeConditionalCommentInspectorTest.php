<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\WebPageInspector\Tests;

use webignition\WebPageInspector\IeConditionalCommentInspector;

class IeConditionalCommentInspectorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider isIeConditionalCommentDataProvider
     */
    public function testIsIeConditionalComment(\DOMComment $commentNode, bool $expectedIs)
    {
        $inspector = new IeConditionalCommentInspector();

        $this->assertEquals($expectedIs, $inspector->isIeConditionalComment($commentNode));
    }

    public function isIeConditionalCommentDataProvider(): array
    {
        return [
            'empty' => [
                'commentNode' => new \DOMComment(''),
                'expectedIs' => false,
            ],
            'not ie conditional comment; single line, plain text' => [
                'commentNode' => new \DOMComment('not ie conditional comment single line'),
                'expectedIs' => false,
            ],
            'not ie conditional comment; multiline, plain text' => [
                'commentNode' => new \DOMComment("not ie conditional comment\nmultiline"),
                'expectedIs' => false,
            ],
            'not ie conditional comment; commented-out script element' => [
                'commentNode' => new \DOMComment('<script type="text/javascript"></script>'),
                'expectedIs' => false,
            ],
            'not ie conditional comment; has start, lacks end' => [
                'commentNode' => new \DOMComment('[if false]><p></p>'),
                'expectedIs' => false,
            ],
            'not ie conditional comment; has start, lacks start' => [
                'commentNode' => new \DOMComment('<p></p><![endif]'),
                'expectedIs' => false,
            ],
            'is conditional comment; single line' => [
                'commentNode' => new \DOMComment('[if false]><p></p><![endif]'),
                'expectedIs' => true,
            ],
            'is conditional comment; multiple lines' => [
                'commentNode' => new \DOMComment(implode("\n", [
                    '[if true]>',
                    '<link rel="stylesheet" href="/if-true-1.css">',
                    '<link rel="stylesheet" href="/if-true-2.css">',
                    '<![endif]',
                ])),
                'expectedIs' => true,
            ],
        ];
    }
}
