<?php

namespace App\Tests\Form;

use App\Entity\Category;
use App\Form\CategoryFormType;
use Symfony\Component\Form\Test\TypeTestCase;

class CategoryFormTypeTest extends TypeTestCase
{
    public function testSubmitValidData(): void
    {
        $formData = [
            'name' => 'Electronics',
            'description' => 'Category for electronic products',
            'isActive' => true,
        ];

        $category = new Category();
        $form = $this->factory->create(CategoryFormType::class, $category);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame('Electronics', $category->getName());
        $this->assertSame('Category for electronic products', $category->getDescription());
        $this->assertTrue($category->isActive());
    }

    public function testFormFields(): void
    {
        $form = $this->factory->create(CategoryFormType::class);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('isActive'));
    }

}