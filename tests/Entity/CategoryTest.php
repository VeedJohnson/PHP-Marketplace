<?php

namespace App\Tests\Entity;

use App\Entity\Category;
use App\Entity\Advertisement;
use PHPUnit\Framework\TestCase;

class CategoryTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $category = new Category();

        $category->setName('Electronics');
        $category->setDescription('All electronic gadgets and devices');
        $category->setActive(true);

        $this->assertSame('Electronics', $category->getName());
        $this->assertSame('All electronic gadgets and devices', $category->getDescription());
        $this->assertTrue($category->isActive());
    }

    public function testDefaultActiveStatus(): void
    {
        $category = new Category();

        $this->assertTrue($category->isActive(), 'A new category should be active by default.');
    }

    public function testAdvertisementRelationship(): void
    {
        $category = new Category();
        $ad1 = new Advertisement();
        $ad2 = new Advertisement();

        // Add advertisements
        $category->addAdvertisement($ad1);
        $category->addAdvertisement($ad2);

        $this->assertCount(2, $category->getAdvertisements());
        $this->assertTrue($category->getAdvertisements()->contains($ad1));
        $this->assertTrue($category->getAdvertisements()->contains($ad2));

        // Remove an advertisement
        $category->removeAdvertisement($ad1);

        $this->assertCount(1, $category->getAdvertisements());
        $this->assertFalse($category->getAdvertisements()->contains($ad1));
        $this->assertTrue($category->getAdvertisements()->contains($ad2));
    }

    public function testBidirectionalAdvertisementRelationship(): void
    {
        $category = new Category();
        $ad = new Advertisement();

        // Add advertisement
        $category->addAdvertisement($ad);

        $this->assertTrue($ad->getCategories()->contains($category), 'Adding a category to an advertisement should reflect in the advertisement\'s categories.');

        // Remove advertisement
        $category->removeAdvertisement($ad);

        $this->assertFalse($ad->getCategories()->contains($category), 'Removing a category from an advertisement should reflect in the advertisement\'s categories.');
    }

    public function testSetActive(): void
    {
        $category = new Category();

        $category->setActive(false);
        $this->assertFalse($category->isActive(), 'The category should be inactive after calling setActive(false).');

        $category->setActive(true);
        $this->assertTrue($category->isActive(), 'The category should be active after calling setActive(true).');
    }
}