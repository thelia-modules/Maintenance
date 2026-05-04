<?php

namespace Maintenance\Form;

use Thelia\Form\BaseForm;

class ToggleMaintenanceForm extends BaseForm
{
    protected function buildForm()
    {

    }

    public static function getName(): string
    {
        return "toggle_maintenance_form";
    }
}
