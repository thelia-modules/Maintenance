<?php

namespace Maintenance\Hook;

use Maintenance\Form\AccessForm;
use Maintenance\Form\ConfigurationForm;
use Maintenance\Form\ToggleMaintenanceForm;
use Maintenance\Maintenance;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Form\TheliaFormFactory;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;

class BackHook extends BaseHook
{
    public function __construct(
        private readonly TheliaFormFactory $formFactory,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfig'],
            ],
        ];
    }

    public function onModuleConfig(HookRenderEvent $event): void
    {
        $maintenanceFile = Maintenance::getMaintenanceFile();

        $content = $maintenanceFile->getContents();

        preg_match('/<!--TITLE START-->((.|\n)*?)<!--TITLE END-->/', $content, $title);
        preg_match('/<!--MESSAGE START-->((.|\n)*?)<!--MESSAGE END-->/', $content, $message);
        preg_match('/background_color\*\/((.|\n)*?)\/\*background_color/', $content, $backgroundColor);
        preg_match('/font_color\*\/((.|\n)*?)\/\*font_color/', $content, $fontColor);
        preg_match('/link_color\*\/((.|\n)*?)\/\*link_color/', $content, $linkColor);

        $isWebWritable = is_writable(THELIA_WEB_DIR);

        $configForm = $this->formFactory->createForm(ConfigurationForm::getName());
        $configForm->createView();

        $toggleForm = $this->formFactory->createForm(ToggleMaintenanceForm::getName());
        $toggleForm->createView();

        $accessForm = $this->formFactory->createForm(AccessForm::getName());
        $accessForm->createView();

        $event->add(
            $this->render(
                'Maintenance/module-configuration.html.twig',
                [
                    'title' => html_entity_decode($title[1] ?? ''),
                    'message' => html_entity_decode($message[1] ?? ''),
                    'backgroundColor' => html_entity_decode($backgroundColor[1] ?? ''),
                    'fontColor' => html_entity_decode($fontColor[1] ?? ''),
                    'linkColor' => html_entity_decode($linkColor[1] ?? ''),
                    'isInMaintenance' => Maintenance::isEnabled(),
                    'hasLegacyIndexGuard' => Maintenance::hasLegacyIndexGuard(),
                    'isWebWritable' => $isWebWritable,
                    'allowedPaths' => Maintenance::getConfigValue(Maintenance::CONFIG_ALLOWED_PATHS, ''),
                    'allowedIps' => Maintenance::getConfigValue(Maintenance::CONFIG_ALLOWED_IPS, ''),
                    'clientIp' => $this->getRequest()?->getClientIp(),
                    'configForm' => $configForm->getView(),
                    'toggleForm' => $toggleForm->getView(),
                    'accessForm' => $accessForm->getView(),
                ]
            )
        );
    }
}
