<?php

use Pigsas\FuzzySearch\Installer\Installer;

class FuzzySearch extends Module
{

    /**
     * @var array
     */
    private $config;

    public function __construct()
    {
        $this->tab = 'other_modules';
        $this->name = 'fuzzysearch';
        $this->version = '1.0.0';
        $this->author = 'Pigsas';
        $this->bootstrap = true;

        parent::__construct();
        $this->autoload();

        $this->displayName = $this->trans('Fuzzy search', [], 'Modules.Fuzzysearch');
        $this->description = $this->trans('', [], 'Modules.Fuzzysearch');
    }

    public function install()
    {
        $installer = new Installer($this);

        Configuration::updateValue('FUZZY_SEARCH_FUZZINESS',true);
        Configuration::updateValue('FUZZY_SEARCH_PREFIX_LENGTH', 2);
        Configuration::updateValue('FUZZY_SEARCH_MAX_EXPANSIONS', 50);
        Configuration::updateValue('FUZZY_SEARCH_DISTANCE',  50);

        return parent::install()
         && $installer->installTab('AdminFuzzySearchConfiguration', 'ShopParameters', 'Fuzzy Search');
    }

    public function uninstall()
    {
        $installer = new Installer($this);

        return parent::uninstall()
            && $installer->uninstallTab('AdminFuzzySearchConfiguration');
    }


    public function getContent()
    {
        Tools::redirect($this->context->link->getAdminLink('AdminFuzzySearchConfiguration'));
    }

    private function autoLoad()
    {
        $autoLoadPath = $this->getLocalPath().'vendor/autoload.php';

        require_once $autoLoadPath;
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }
}
