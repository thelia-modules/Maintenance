<?php

namespace Maintenance\Form;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Thelia\Form\BaseForm;

class AccessForm extends BaseForm
{
    protected function buildForm(): void
    {
        $this->formBuilder
            ->add('allowed_paths', TextareaType::class, ['required' => false])
            ->add('allowed_ips', TextareaType::class, ['required' => false]);
    }

    public static function getName(): string
    {
        return 'maintenance_access_form';
    }
}
