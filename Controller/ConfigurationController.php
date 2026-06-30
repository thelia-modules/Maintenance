<?php

namespace Maintenance\Controller;

use Maintenance\Form\ConfigurationForm;
use Maintenance\Form\ToggleMaintenanceForm;
use Maintenance\Maintenance;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
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
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], 'Maintenance', AccessManager::VIEW)) {
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
            $this->getRequest()?->getSession()?->getFlashBag()->add(
                'error',
                Translator::getInstance()->trans('Error', [], Maintenance::DOMAIN_NAME).': '.$e->getMessage()
            );

            return $this->generateRedirectFromRoute('admin.module.configure', [], ['module_code' => 'Maintenance']);
        }

        return $this->generateSuccessRedirect($form);
    }

    #[Route("/toggle", name: "_toggle_maintenance", methods: ['POST'])]
    public function toggleMaintenanceAction(): Response
    {
        if (null !== $response = $this->checkAuth([AdminResources::MODULE], 'Maintenance', AccessManager::VIEW)) {
            return $response;
        }

        $form = $this->createForm(ToggleMaintenanceForm::getName());

        try {
            $this->validateForm($form);

            $finder = new Finder();
            $finder->files()->depth('== 0')->in(THELIA_WEB_DIR)->name('index.php');

            $contents = '';
            $filePath = '';

            /** @var SplFileInfo $file */
            foreach ($finder as $file) {
                $contents = $file->getContents();
                $filePath = $file->getPathname();
            }

            if (preg_match('/\/\/⚠((.|\n)*)⚠/', $contents)) {
                $newContent = preg_replace('/\/\/⚠((.|\n)*)⚠/', '', $contents);
            } else {
                $maintenanceTag = "**/\n\n//⚠--MAINTENANCE\nhttp_response_code(503);\ninclude('maintenance.html');\ndie();\n//__⚠\n";
                $newContent = preg_replace('/\*\*\/\n\n/i', $maintenanceTag, $contents);
            }

            file_put_contents($filePath, $newContent);
        } catch (\Exception $e) {
            $this->getRequest()?->getSession()?->getFlashBag()->add(
                'error',
                Translator::getInstance()->trans('Error', [], Maintenance::DOMAIN_NAME).': '.$e->getMessage()
            );

            return $this->generateRedirectFromRoute('admin.module.configure', [], ['module_code' => 'Maintenance']);
        }

        return $this->generateSuccessRedirect($form);
    }
}
