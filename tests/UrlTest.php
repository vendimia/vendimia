<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class Vendimia
{
    static $base_url = 'base_url/';
    static $application;
}

final class UrlTest extends TestCase
{
    public function testSimplePartsJoin()
    {
        $this->assertEquals(
            Vendimia::$base_url . 'a/b',
            new Vendimia\Url('a', 'b')
        );
    }

    public function testAppURL()
    {
        Vendimia::$application = 'app';
        $this->assertEquals(
            'base_url/app/something',
            new Vendimia\Url(':something')
        );
        $this->assertEquals(
            'base_url/other_app/something',
            new Vendimia\Url('other_app:something')
        );
    }

    public function testAbsoluteURL()
    {
        $this->assertEquals(
            'http://an_absolute.url/test?a=b#c',
            new Vendimia\Url('http://an_absolute.url/test?a=b#c')
        );
        $this->assertEquals(
            'http://an_absolute.url/test?a=b',
            new Vendimia\Url('http://an_absolute.url/test', ['a'=>'b'])
        );
    }
}
