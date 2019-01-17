<?php

namespace webignition\WebPageInspector\Tests;

class FixtureLoader
{
    /**
     * @var string
     */
    public static $fixturePath;

    public static function load(string $name): string
    {
        $fixturePath = realpath(static::$fixturePath . '/' . $name);

        if (empty($fixturePath)) {
            throw new \RuntimeException(sprintf(
                'Unknown fixture %s',
                $name
            ));
        }

        return (string) file_get_contents($fixturePath);
    }
}
