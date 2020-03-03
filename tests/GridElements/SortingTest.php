<?php

namespace JasperFW\DataModelTest\GridElements;

use InvalidArgumentException;
use JasperFW\DataModel\GridElements\Sorting;
use PHPUnit\Framework\TestCase;

class SortingTest extends TestCase
{
    public function testCreateSorting()
    {
        $sut = new Sorting('field', 'ASC');
        $this->assertEquals('field', $sut->getSortField());
        $this->assertEquals('ASC', $sut->getSortOrder());
    }

    public function testSortOrderIsCapitalized()
    {
        $sut = new Sorting('', 'asc');
        $this->assertEquals('ASC', $sut->getSortOrder());
    }

    public function testSortOrderIsValid()
    {
        $sut = new Sorting();
        $this->assertEquals('', $sut->getSortOrder(), 'Space is valid');
        $this->expectException(InvalidArgumentException::class);
        $sut->setSortOrder('no');
    }
}
