<?php

namespace AppKernel\Form\Fieldsets;

use AppEntity\AppCategories;
use Laminas\Form\Fieldset;
use Laminas\InputFilter\InputFilterProviderInterface;
use Laminas\Hydrator\ClassMethodsHydrator as ClassMethodsHydrator;

class CategoryFieldset extends Fieldset implements InputFilterProviderInterface
{
    public function __construct()
    {
        parent::__construct('app_categories');

        $this->setHydrator(new ClassMethodsHydrator(false));
        $this->setObject(new AppCategories());

        $this->setLabel('Category');

        $this->add([
            'name' => 'name',
            'options' => [
                'label' => 'Name of the category',
            ],
            'attributes' => [
                'required' => 'required',
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