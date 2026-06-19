<?php

namespace AppKernel\Form\Fieldsets;

use AppEntity\AppPosts;
use AppKernel\Form\Fieldsets\CategoryFieldset;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Hydrator\ClassMethodsHydrator as ClassMethodsHydrator;

class PostFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('app_posts');

        $this->setHydrator(new ClassMethodsHydrator(false));
        $this->setObject(new AppPosts());

        $this->add([
            'name' => 'name',
            'options' => [
                'label' => 'postName',
            ],
            'attributes' => [
                'required' => 'required',
            ],
        ]);

        
        $this->add([
            'type' => Element\Collection::class,
            'name' => 'categories',
            'options' => [
                'label' => 'Please choose categories for this product',
                'count' => 2,
                'should_create_template' => true,
                'allow_add' => true,
                'target_element' => [
                    'type' => CategoryFieldset::class,
                ],
            ],
        ]);
    }

    /**
     * @return array
     */
    public function getInputFilterSpecification()
    {
        return [
            'name' => [
                'required' => true,
            ],
        ];
    }
}