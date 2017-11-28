<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PathTest extends TestCase
{
    public function testSimpleJoin()
    {
        $this->assertEquals(
            'a/b',
            Vendimia\Path::join('a', 'b')
        );
    }

    public function testPathsJoin()
    {
        $this->assertEquals(
            'a/b/c/b',
            Vendimia\Path::join('a/b/c', 'b')
        );
    }

    public function testEmptyPathRemoval()
    {
        $this->assertEquals(
            'a/c/b/d',
            Vendimia\Path::join('a////c', 'b//d')
        );
    }

    public function testAbsolutePath()
    {
        $this->assertEquals(
            '/ruta/absoluta',
            Vendimia\Path::join('/ruta', 'absoluta')
        );
        $this->assertEquals(
            '/ruta/absoluta',
            Vendimia\Path::join('/', 'ruta', 'absoluta')
        );
    }

    public function testArrayPaths()
    {
        $this->assertEquals(
            '/ruta/absoluta',
            Vendimia\Path::join('/', ['ruta', 'absoluta'])
        );
        $this->assertEquals(
            'a/b/c/d',
            Vendimia\Path::join(['a/b', 'c'], ['d'])
        );
    }
}
