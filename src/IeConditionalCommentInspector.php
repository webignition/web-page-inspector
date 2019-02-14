<?php

namespace webignition\WebPageInspector;

class IeConditionalCommentInspector
{
    const START_PATTERN = '/^\[if[^>]+]>/';
    const END_PATTERN = '/<!\[endif\]$/';
    const IE_CONDITIONAL_COMMENT_IDENTIFICATION_PATTERN = '/^\[if[^>]+]>(?:(?!<!\[endif\]).)+<!\[endif\]$/ms';

    public function isIeConditionalComment(\DOMComment $commentNode): bool
    {
        return preg_match(self::IE_CONDITIONAL_COMMENT_IDENTIFICATION_PATTERN, $commentNode->data) > 0;
    }

    public function extractData(\DOMComment $commentNode): string
    {
        if (!$this->isIeConditionalComment($commentNode)) {
            return '';
        }

        return trim(preg_replace(
            [
                self::START_PATTERN,
                self::END_PATTERN,
            ],
            '',
            $commentNode->data
        ));
    }
}
