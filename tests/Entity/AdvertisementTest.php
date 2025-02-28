<?php

namespace App\Tests\Entity;

use App\Entity\Advertisement;
use App\Entity\AdvertisementImage;
use App\Entity\Category;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class AdvertisementTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $advertisement = new Advertisement();

        $advertisement->setTitle('Sample Ad')
            ->setDescription('This is a test advertisement.')
            ->setPrice('99.99')
            ->setCity('Manchester')
            ->setState('Greater Manchester')
            ->setStatus('active');

        $this->assertSame('Sample Ad', $advertisement->getTitle());
        $this->assertSame('This is a test advertisement.', $advertisement->getDescription());
        $this->assertSame('99.99', $advertisement->getPrice());
        $this->assertSame('Manchester', $advertisement->getCity());
        $this->assertSame('Greater Manchester', $advertisement->getState());
        $this->assertSame('active', $advertisement->getStatus());
    }

    public function testCreatedAtAndUpdatedAt(): void
    {
        $advertisement = new Advertisement();
        $createdAt = new \DateTimeImmutable();
        $updatedAt = new \DateTime();

        $advertisement->setCreatedAt($createdAt)
            ->setUpdatedAt($updatedAt);

        $this->assertSame($createdAt, $advertisement->getCreatedAt());
        $this->assertSame($updatedAt, $advertisement->getUpdatedAt());
    }

    public function testUserRelationship(): void
    {
        $advertisement = new Advertisement();
        $user = new User();

        $advertisement->setUser($user);

        $this->assertSame($user, $advertisement->getUser());
    }

    public function testCategoryRelationship(): void
    {
        $advertisement = new Advertisement();
        $category1 = new Category();
        $category2 = new Category();

        // Add categories
        $advertisement->addCategory($category1);
        $advertisement->addCategory($category2);

        $this->assertCount(2, $advertisement->getCategories());
        $this->assertTrue($advertisement->getCategories()->contains($category1));
        $this->assertTrue($advertisement->getCategories()->contains($category2));

        // Remove a category
        $advertisement->removeCategory($category1);

        $this->assertCount(1, $advertisement->getCategories());
        $this->assertFalse($advertisement->getCategories()->contains($category1));
        $this->assertTrue($advertisement->getCategories()->contains($category2));
    }

    public function testAdvertisementImageRelationship(): void
    {
        $advertisement = new Advertisement();
        $image1 = new AdvertisementImage();
        $image2 = new AdvertisementImage();

        // Add images
        $advertisement->addAdvertisementImage($image1);
        $advertisement->addAdvertisementImage($image2);

        $this->assertCount(2, $advertisement->getAdvertisementImages());
        $this->assertTrue($advertisement->getAdvertisementImages()->contains($image1));
        $this->assertTrue($advertisement->getAdvertisementImages()->contains($image2));

        // Remove an image
        $advertisement->removeAdvertisementImage($image1);

        $this->assertCount(1, $advertisement->getAdvertisementImages());
        $this->assertFalse($advertisement->getAdvertisementImages()->contains($image1));
        $this->assertTrue($advertisement->getAdvertisementImages()->contains($image2));
    }
}