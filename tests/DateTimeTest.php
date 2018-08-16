<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Vendimia\DateTime\DateTime;
use Vendimia\DateTime\Date;
use Vendimia\DateTime\Interval;

final class DateTimeTest extends TestCase
{
    public function testFormatWithDate()
    {
        $dt = new DateTime('1981-02-28');

        $this->assertEquals($dt->format('Y.Y-m-d.d'), '1981.1981-02-28.28');
    }

    public function testFormatWithStrftime()
    {
        $dt = new DateTime('1981-02-28');

        $this->assertEquals($dt->format('%d abc'), '28 abc');
    }

    public function testUpdateDatePart()
    {
        $dt = new DateTime('1981-02-28');
        $dt->setPart('year', '2008');

        $this->assertEquals($dt->format('Y'), '2008');
    }

    public function testFixDateWithOutOfRangePart()
    {
        $dt = new DateTime('1981-02-28');
        $dt->setPart('month', 13);

        $this->assertEquals($dt->format('Y'), '1982');
    }

    public function testAddInterval()
    {
        $dt = new DateTime('1981-02-28');
        $interval = Interval::day(1)->setMonth(2);

        $dt->add($interval);

        $this->assertEquals(4, $dt->format('m'));
    }

    public function testSubstractInterval()
    {
        $dt = new DateTime('1981-02-28');
        $interval = Interval::day(1)->setMonth(2);

        $dt->sub($interval);

        $this->assertEquals('1980-12-27', $dt->format('Y-m-d'));
    }

    public function testDiffWithMonths()
    {
        $dt = new DateTime('1981-02-28');
        $diff = $dt->diff(new DateTime('1981-05-28'));

        $this->assertEquals(3, $diff->getMonth());
    }

    public function testDiffWithDates()
    {
        $dt = Date::now();
        $target = new Date("2018-07-01");
        var_dump($dt->diff($target)->getParts());
    }

    public function testBefore()
    {
        $dt = new DateTime('1981-02-28');

        $this->assertEquals(true, $dt->isBefore(new DateTime('1981-02-29')));
    }

    public function testAfter()
    {
        $dt = new DateTime('1981-02-28');

        $this->assertEquals(true, $dt->isAfter(new DateTime('1981-01-29')));
    }

}
