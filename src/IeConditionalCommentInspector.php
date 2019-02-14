<?php

namespace webignition\WebPageInspector;

class IeConditionalCommentInspector
{
    const START_PATTERN = '\[if[^>]+\]>';
    const END_PATTERN = '<!\[endif\]';
    const IDENTIFICATION_PATTERN =
        '/^' . self::START_PATTERN . '(?:(?!' . self::END_PATTERN . ').)+' . self::END_PATTERN . '$/ms';

    public function isIeConditionalComment(\DOMComment $commentNode): bool
    {
        return preg_match(self::IDENTIFICATION_PATTERN, $commentNode->data) > 0;
    }

    public function extractData(\DOMComment $commentNode): string
    {
        if (!$this->isIeConditionalComment($commentNode)) {
            return '';
        }

        return trim(preg_replace(
            [
                '/^' . self::START_PATTERN . '/',
                '/' . self::END_PATTERN . '$/',
            ],
            '',
            $commentNode->data
        ));
    }
}
