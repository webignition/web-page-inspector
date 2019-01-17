<?php
/** @noinspection PhpUnhandledExceptionInspection */

namespace webignition\WebPageInspector\Tests;

use webignition\InternetMediaTypeInterface\InternetMediaTypeInterface;
use webignition\InternetMediaType\Parser\Parser as ContentTypeParser;

class ContentTypeFactory
{
    public static function createFromString(string $contentTypeString): ?InternetMediaTypeInterface
    {
        $parser = new ContentTypeParser();

        return $parser->parse($contentTypeString);
    }
}
