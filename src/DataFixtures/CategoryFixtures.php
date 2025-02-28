<?php

// src/DataFixtures/CategoryFixtures.php
namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['Vehicles', 'Cars, motorcycles, trucks, and other vehicles'],
            ['Electronics', 'Phones, computers, gadgets, and other electronic items'],
            ['Furniture', 'Home and office furniture'],
            ['Property', 'Houses, apartments, and commercial properties'],
            ['Fashion', 'Clothing, shoes, accessories, and jewelry'],
            ['Sports & Leisure', 'Sports equipment, outdoor gear, and hobby items'],
            ['Books & Media', 'Books, movies, music, and games'],
            ['Home & Garden', 'Home appliances, garden tools, and decorations'],
            ['Jobs', 'Job listings and employment opportunities'],
            ['Service', 'Professional and personal services']
        ];

        foreach ($categories as [$name, $description]) {
            $category = new Category();
            $category->setName($name);
            $category->setDescription($description);
            $category->setActive(true);

            $manager->persist($category);
        }

        $manager->flush();
    }
}