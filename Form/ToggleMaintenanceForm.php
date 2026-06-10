<?php

namespace Maintenance\Form;

use Thelia\Form\BaseForm;

class ToggleMaintenanceForm extends BaseForm
{
    protected function buildForm(): void
    {
    }

    public static function getName(): string
    {
        return "toggle_maintenance_form";
    }
}
