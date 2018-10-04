<?php

namespace webignition\WebPageInspector\Tests;

class FixtureLoader
{
    /**
     * @var string
     */
    public static $fixturePath;

    /**
     * @param string $name
     *
     * @return string
     */
    public static function load($name)
    {
        $fixturePath = realpath(static::$fixturePath . '/' . $name);

        if (empty($fixturePath)) {
            throw new \RuntimeException(sprintf(
                'Unknown fixture %s',
                $name
            ));
        }

        return file_get_contents($fixturePath);
    }
}
