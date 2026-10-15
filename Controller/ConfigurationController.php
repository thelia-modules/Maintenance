<?php

namespace Maintenance\Controller;

use Maintenance\Form\AccessForm;
use Maintenance\Form\ConfigurationForm;
use Maintenance\Form\ToggleMaintenanceForm;
use Maintenance\Maintenance;
use Maintenance\Service\AccessList;
use Symfony\Component\HttpFoundation\Response;
use Thelia\Controller\Admin\BaseAdminController;
use Thelia\Core\Security\AccessManager;
use Thelia\Core\Security\Resource\AdminResources;
use Thelia\Core\Translation\Translator;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/admin/module/Maintenance", name: "maintenance")]
class ConfigurationController extends BaseAdminController
{
    #[Route("/configuration", name: "_save", methods: ['POST'])]
    public function configurationAction(): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], 'Maintenance', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ConfigurationForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $maintenanceFile = Maintenance::getMaintenanceFile();

            $content = $maintenanceFile->getContents();

            $title = $data['title'];
            $message = $data['message'];
            $backgroundColor = $data['background_color'];
            $fontColor = $data['font_color'];
            $linkColor = $data['link_color'];

            // Use preg_replace_callback to avoid backreference interpretation of $ and \ in replacement strings.
            $newContent = preg_replace_callback(
                "/<!--TITLE START-->((.|\n)*)<!--TITLE END-->/",
                static fn() => '<!--TITLE START-->'.$title.'<!--TITLE END-->',
                $content
            );
            $newContent = preg_replace_callback(
                "/<!--MESSAGE START-->((.|\n)*)<!--MESSAGE END-->/",
                static fn() => '<!--MESSAGE START-->'.$message.'<!--MESSAGE END-->',
                $newContent
            );
            $newContent = preg_replace_callback(
                "/background_color\*\/((.|\n)*)\/\*background_color/",
                static fn() => 'background_color*/'.$backgroundColor.'/*background_color',
                $newContent
            );
            $newContent = preg_replace_callback(
                "/font_color\*\/((.|\n)*)\/\*font_color/",
                static fn() => 'font_color*/'.$fontColor.'/*font_color',
                $newContent
            );
            $newContent = preg_replace_callback(
                "/link_color\*\/((.|\n)*)\/\*link_color/",
                static fn() => 'link_color*/'.$linkColor.'/*link_color',
                $newContent
            );

            file_put_contents($maintenanceFile->getPathname(), $newContent);
        } catch (\Exception $e) {
            return $this->errorRedirect($e);
        }

        return $this->generateSuccessRedirect($form);
    }

    #[Route("/toggle", name: "_toggle_maintenance", methods: ['POST'])]
    public function toggleMaintenanceAction(): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], 'Maintenance', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(ToggleMaintenanceForm::getName());

        try {
            $this->validateForm($form);

            Maintenance::setConfigValue(Maintenance::CONFIG_ENABLED, Maintenance::isEnabled() ? '0' : '1');
        } catch (\Exception $e) {
            return $this->errorRedirect($e);
        }

        return $this->generateSuccessRedirect($form);
    }

    #[Route("/access", name: "_access", methods: ['POST'])]
    public function accessAction(): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], 'Maintenance', AccessManager::UPDATE)) {
            return $response;
        }

        $form = $this->createForm(AccessForm::getName());

        try {
            $data = $this->validateForm($form)->getData();

            $paths = (string) ($data['allowed_paths'] ?? '');
            $ips = (string) ($data['allowed_ips'] ?? '');
            $invalid = [...AccessList::invalidPaths($paths), ...AccessList::invalidIps($ips)];

            if ([] !== $invalid) {
                throw new \InvalidArgumentException(
                    Translator::getInstance()->trans('Invalid entries: %entries', ['%entries' => implode(', ', $invalid)], Maintenance::DOMAIN_NAME)
                );
            }

            Maintenance::setConfigValue(Maintenance::CONFIG_ALLOWED_PATHS, implode("\n", AccessList::lines($paths)));
            Maintenance::setConfigValue(Maintenance::CONFIG_ALLOWED_IPS, implode("\n", AccessList::lines($ips)));
        } catch (\Exception $e) {
            return $this->errorRedirect($e);
        }

        return $this->generateSuccessRedirect($form);
    }

    private function errorRedirect(\Exception $e): Response
    {
        $this->getRequest()?->getSession()?->getFlashBag()->add(
            'error',
            Translator::getInstance()->trans('Error', [], Maintenance::DOMAIN_NAME).': '.$e->getMessage()
        );

        return $this->generateRedirectFromRoute('admin.module.configure', [], ['module_code' => 'Maintenance']);
    }
}
