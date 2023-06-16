<?php

class TauronPowerOutagesHandler
{
    public function smartyTauronPowerOutages(Smarty $hook_data)
    {
        $template_dirs = $hook_data->getTemplateDir();
        $plugin_templates = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSTauronPowerOutagesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'templates';
        array_unshift($template_dirs, $plugin_templates);
        $hook_data->setTemplateDir($template_dirs);
        return $hook_data;
    }

    public function modulesDirTauronPowerOutages(array $hook_data = array())
    {
        $plugin_modules = PLUGINS_DIR . DIRECTORY_SEPARATOR . LMSTauronPowerOutagesPlugin::PLUGIN_DIRECTORY_NAME . DIRECTORY_SEPARATOR . 'modules';
        array_unshift($hook_data, $plugin_modules);
        return $hook_data;
    }

    public function welcomeTauronPowerOutages(array $hook_data = array())
    {
        $SMARTY = LMSSmarty::getInstance();
        $filename = ConfigHelper::getConfig('tauron.filename', 'tauron.json');
        $time_in_cache = ConfigHelper::getConfig('tauron.time_in_cache', 60);

        $last_updated_cache = 0;
        if (file_exists($filename)) {
            $last_updated_cache = filemtime($filename);
        }

        $commune = explode(",", ConfigHelper::getConfig('tauron.commune', '502'));
        $district = explode(",", ConfigHelper::getConfig('tauron.district', '6'));
        $province = explode(",", ConfigHelper::getConfig('tauron.province', '24'));
        $forwardDays = intval(ConfigHelper::getConfig('tauron.forward_days', 7));
        $forwardTimeSeconds = $forwardDays * 24 * 60 * 60;
        $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/waapi');
        $outages = array();

        if ((time() - $last_updated_cache) > $time_in_cache) {
            $queryDateTimeStart = date("Y-m-d") . "T" . date("H") . "%3A" . date("i") . "%3A00.000Z";
            $queryDateTimeStop = date("Y-m-d", time() + $forwardTimeSeconds) . "T" . date("H") . "%3A" . date("i") . "%3A00.000Z";

            $CURLConnection = curl_init();

            foreach ($province as $provinceGAID) {
                foreach ($district as $districtGAID) {
                    foreach ($commune as $communeGAID) {
                        $url = $api_url . "/outages/area?provinceGAID=" . $provinceGAID . "&districtGAID=" . $districtGAID . "&fromDate=" . $queryDateTimeStart . "&toDate=" . $queryDateTimeStop . "&communeGAID=" . $communeGAID;
                        curl_setopt($CURLConnection, CURLOPT_URL, $url);
                        curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
                        $json = curl_exec($CURLConnection);
                        $outages = array_merge_recursive($outages, json_decode($json, true));
                    }
                }
            }

            curl_close($CURLConnection);
            $json = json_encode($outages);

            if (!file_put_contents($filename, $json)) {
                echo "Oops! Error creating json file $filename";
            }
        }

        $outages = json_decode(file_get_contents($filename), true);
        $outageItems = $outages['OutageItems'];
        $outagesCount = is_array($outageItems) ? count($outageItems) : 0;

        $SMARTY->assign(
            'tauron_power_outages',
            array(
                'outages' => $outages['OutageItems'],
                'outages_count' => $outagesCount,
                'last_updated_cache' => date("Y-m-d H:i:s", filemtime($filename)),
            )
        );

        return $hook_data;
    }


    public function accessTableInit()
    {
        $access = AccessRights::getInstance();
        $access->insertPermission(
            new Permission(
                'tauronpoweroutages_full_access',
                trans('Tauron power outages'),
                '^tauronpoweroutages$'
            ), AccessRights::FIRST_FORBIDDEN_PERMISSION
        );
    }
}