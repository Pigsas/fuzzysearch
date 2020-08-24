<?php

namespace Pigsas\FuzzySearch\Installer;

use Db;
use Exception;
use Language;
use Module;
use Tab;

class Installer
{
    /**
     * @var Module
     */
    private $module;

    /**
     * Installer constructor.
     * @param Module $module
     */
    public function __construct(Module $module)
    {
        $this->module = $module;

    }

    /**
     * @param string $id
     * @param array $parameters
     * @param string|null $domain
     * @param string|null $locale
     * @return string
     */
    protected function trans(string $id, array $parameters = array(), string $domain = null, string $locale = null): string
    {
        return $this->module->getTranslator()->trans($id, $parameters, $domain, $locale);
    }

    /**
     * @param string $hookId
     * @param array|null $shop_list
     * @return $this
     * @throws Exception
     */
    public function installHook(string $hookId, array $shop_list = null): self
    {
        if (!$this->module->registerHook($hookId, $shop_list)) {
            throw new Exception(
                $this->trans('Hook %s has not been installed.', [$hookId], 'Modules.ProductAdditionalInfo.Installer')
            );
        }
        return $this;
    }

    /**
     * @param string $hookId
     * @param array|null $shop_list
     * @return $this
     * @throws Exception
     */
    public function uninstallHook(string $hookId, array $shop_list = null): self
    {
        if (!$this->module->unregisterHook($hookId, $shop_list)) {
            throw new Exception(
                $this->trans('Hook %s has not been uninstalled.', [$hookId], 'Modules.ProductAdditionalInfo.Installer')
            );
        }
        return $this;
    }

    /**
     * @param string $path
     * @return $this
     * @throws Exception
     */
    public function executeSqlFile(string $path): self
    {
        if (empty($path)) {
            throw new Exception(
                $this->trans('Path for SQL file was not provided.', [], 'Modules.ProductAdditionalInfo.Installer')
            );
        }

        if (!file_exists($path))
        {
            throw new Exception(
                $this->trans('SQL file in path: %s does not exists.', [$path], 'Modules.ProductAdditionalInfo.Installer')
            );
        }

        $sql = file_get_contents($path);
        $sql = str_replace('PREFIX_', _DB_PREFIX_, $sql);
        $sql = preg_split("/;\s*[\r\n]+/", $sql);

        foreach ($sql as $query)
        {
            if(strlen(trim($query)) <= 3)
            {
                continue;
            }

            if (!Db::getInstance()->Execute(trim($query)))
            {
                throw new Exception(
                    $this->trans('SQL was not executed: %s.', [$query], 'Modules.ProductAdditionalInfo.Installer')
                );
            }
        }

        return $this;
    }

    /**
     * @param string $class
     * @param string $parentClass
     * @param string $name
     * @param string $icon
     * @return $this
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     */
    public function installTab(string $class, string $parentClass, string $name, string $icon = ''): self
    {
        $tabId = (int)Tab::getIdFromClassName($class);
        if (!$tabId) {
            $tabId = null;
        }
        $tab = new Tab($tabId);
        $tab->active = 1;
        $tab->icon = $icon;
        $tab->class_name = $class;
        $tab->name = array();
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $name;
        }
        $tab->id_parent = (int)Tab::getIdFromClassName($parentClass);
        $tab->module = $this->module->name;

        if(!$tab->save()) {
            throw new Exception(
                $this->trans('Tab %s has not been installed', [$class], 'Modules.ProductAdditionalInfo.Installer')
            );
        }

        return $this;
    }

    public function uninstallTab(string $class): self
    {
        $tabId = (int)Tab::getIdFromClassName($class);
        if ($tabId) {
            $tab = new Tab($tabId);
            try {
                $tab->delete();
            } catch (Exception $e) {
                throw new Exception(
                    $this->trans('Tab %s has not been uninstalled', [$class], 'Modules.ProductAdditionalInfo.Installer')
                );
            }
        }

        return $this;
    }

    public function done()
    {
        return true;
    }
    

}