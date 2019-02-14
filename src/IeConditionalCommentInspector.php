<?php

namespace webignition\WebPageInspector;

class IeConditionalCommentInspector
{
    const IE_CONDITIONAL_COMMENT_IDENTIFICATION_PATTERN = '/^\[if[^>]+]>(?:(?!<!\[endif\]).)+<!\[endif\]$/ms';

    public function isIeConditionalComment(\DOMComment $commentNode): bool
    {
        return preg_match(self::IE_CONDITIONAL_COMMENT_IDENTIFICATION_PATTERN, $commentNode->data) > 0;
    }
}
