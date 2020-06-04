<?php

class PowerOutagesHandler
{
    public function menuPowerOutages(array $hook_data = array())
    {
        $po_submenus = array(
            array(
                'name' => trans('Power outages'),
                'link' => '?m=poweroutages',
                'tip' => trans('Power outages'),
                'prio' => 150,
            ),
        );
        $hook_data['admin']['submenu'] = array_merge($hook_data['admin']['submenu'], $po_submenus);
        return $hook_data;
    }

    public function smartyPowerOutages(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPowerOutagesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);
        return $hook_data;
    }

    public function modulesDirPowerOutages(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSPowerOutagesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    public function welcomePowerOutages(array $hook_data = array())
    {
        // uncomment if you have current LMS version
        // $SMARTY = LMSSmarty::getInstance();

        // uncomment if you have old LMS version
        $SMARTY = $hook_data['smarty'];

        $gaid = ConfigHelper::getConfig('tauron.gaid', 502);
        $type = ConfigHelper::getConfig('tauron.type', 'commune');
        $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/iapi');

        $CURLConnection = curl_init();
        curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=" . $type);
        curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($CURLConnection);
        curl_close($CURLConnection);

        $SMARTY->assign('powerOutages', json_decode($json, true));
        return $hook_data;
    }
}
