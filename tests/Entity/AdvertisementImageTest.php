<?php

namespace App\Tests\Entity;

use App\Entity\Advertisement;
use App\Entity\AdvertisementImage;
use PHPUnit\Framework\TestCase;

class AdvertisementImageTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $image = new AdvertisementImage();

        $image->setFilename('image1.jpg')
            ->setPosition(1);

        $this->assertSame('image1.jpg', $image->getFilename());
        $this->assertSame(1, $image->getPosition());
    }

    public function testAdvertisementRelationship(): void
    {
        $advertisement = new Advertisement();
        $image = new AdvertisementImage();

        $image->setAdvertisement($advertisement);

        $this->assertSame($advertisement, $image->getAdvertisement());
    }
}