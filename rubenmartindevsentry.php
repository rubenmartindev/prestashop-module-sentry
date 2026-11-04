<?php

declare(strict_types=1);

if (!\defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Extalion\Sentry\Exception\InstallerException;
use Extalion\Sentry\Helper\Installer;

class RubenMartinDevSentry extends Module
{
    private array $tabsConfig = [];

    public function __construct()
    {
        $this->author = 'rubenmartin.dev';
        $this->bootstrap = true;
        $this->name = 'rubenmartindevsentry';
        $this->need_instance = 0;
        $this->tab = 'administration';
        $this->version = '0.0.0';

        parent::__construct();

        $this->displayName = $this->trans(
            'Sentry',
            [],
            'Modules.Rubenmartindevsentry.Admin'
        );
        $this->description = $this->trans(
            'Sentry integration with prestashop',
            [],
            'Modules.Rubenmartindevsentry.Admin'
        );

        $this->confirmUninstall = $this->trans(
            'Are you sure',
            [],
            'Modules.Rubenmartindevsentry.Admin'
        );

        $this->ps_versions_compliancy = [
            'min' => '1.7.8',
            'max' => _PS_VERSION_,
        ];
    }

    public function _install(): bool
    {
        $sqlInstallResult = include __DIR__ . '/sql/install.php';

        return $sqlInstallResult
            && parent::install()
        ;
    }

    public function _uninstall(): bool
    {
        $sqlUninstallResult = include __DIR__ . '/sql/uninstall.php';

        return $sqlUninstallResult
            && parent::uninstall()
        ;
    }

    public function _enable($forceAll = false): bool
    {
        $this->uninstallOverrides();

        try {
            if (parent::enable($forceAll) && $this->installTab()) {
                Installer::enableSentry();

                return true;
            }
        } catch (InstallerException $ex) {
            $this->_errors[] = $ex->getMessage();
        }

        return false;
    }

    public function _disable($forceAll = false): bool
    {
        try {
            if (parent::disable($forceAll) && $this->uninstallTab()) {
                Installer::disableSentry();

                return true;
            }
        } catch (InstallerException $ex) {
            $this->_errors[] = $ex->getMessage();
        }

        return false;
    }

    public function installOverrides()
    {
        // Module::enable() ignores the returned value and only stops on an exception.
        if (!$this->addOverride('PrestaShopException')) {
            throw new InstallerException('The PrestaShopException override could not be installed.');
        }

        foreach (['DbPDO', 'Hook'] as $class) {
            try {
                $installed = $this->addOverride($class);
                $error = $installed ? '' : 'addOverride() returned false';
            } catch (\Throwable $ex) {
                $installed = false;
                $error = $ex->getMessage();
            }

            if (!$installed) {
                PrestaShopLogger::addLog(
                    "[rubenmartindevsentry] The {$class} override could not be installed, tracing will not measure it: {$error}",
                    PrestaShopLogger::LOG_SEVERITY_LEVEL_WARNING
                );
            }
        }

        // PrestaShop only rebuilds the class index of the current environment.
        foreach (['dev', 'prod'] as $environment) {
            $classIndex = _PS_ROOT_DIR_ . "/var/cache/{$environment}/class_index.php";

            if (\file_exists($classIndex)) {
                @\unlink($classIndex);
            }
        }

        return true;
    }

    private function installTab(): bool
    {
        $tabRepository = $this->get('prestashop.core.admin.tab.repository');

        foreach ($this->tabsConfig as $tabConfig) {
            $tabId = $tabRepository
                ->findOneIdByClassName($tabConfig['class_name'])
            ;

            $tab = new Tab($tabId);
            $tab->id_parent = $tabRepository
                ->findOneIdByClassName($tabConfig['parent'])
            ;
            $tab->active = 1;
            $tab->class_name = $tabConfig['class_name'];
            $tab->icon = $tabConfig['icon'];
            $tab->module = $this->name;
            $tab->name = [];

            foreach (Language::getLanguages() as $lang) {
                $tab->name[$lang['id_lang']] = $this->trans(
                    $tabConfig['name'],
                    [],
                    'Modules.RubenMartinDevSentry.Admin',
                    $lang['locale']
                );
            }

            if (!(bool) $tab->save()) {
                return false;
            }
        }

        return true;
    }

    private function uninstallTab(): bool
    {
        $tabRepository = $this->get('prestashop.core.admin.tab.repository');

        foreach ($this->tabsConfig as $tabConfig) {
            $tabId = $tabRepository
                ->findOneIdByClassName($tabConfig['class_name'])
            ;

            if ($tabId) {
                $tab = new Tab($tabId);

                if (!(bool) $tab->delete()) {
                    return false;
                }
            }
        }

        return true;
    }

    public function _getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink(
                'ExtSentryAdminConfigurationController',
                true,
                ['route' => 'extalion_sentry_configuration']
            )
        );
    }

    public function _isUsingNewTranslationSystem(): bool
    {
        return true;
    }
}
