<?php

if (!\defined('_PS_VERSION_')) {
    exit;
}

class RubenMartinDevSentry extends Module
{
    /**
     * @var PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer
     */
    private $serviceContainer;

    public function __construct()
    {
        $this->author = 'rubenmartin.dev';
        $this->bootstrap = true;
        $this->name = 'rubenmartindevsentry';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '1.6.0.4',
            'max' => '9.999.999',
        ];
        $this->tab = 'administration';
        $this->version = '0.0.0';

        // Composer
        require_once __DIR__ . '/vendor/autoload.php';

        parent::__construct();

        $this->serviceContainer = new PrestaShop\ModuleLibServiceContainer\DependencyInjection\ServiceContainer(
            $this->name,
            $this->getLocalPath()
        );

        $this->displayName = 'Sentry';
        $this->description = $this->l('Sentry integration with prestashop');
    }

    /**
     * @param string $serviceName
     *
     * @return object|null
     */
    public function getService($serviceName)
    {
        return $this->serviceContainer->getService($serviceName);
    }
}
