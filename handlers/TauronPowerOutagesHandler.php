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
        // uncomment if you have current LMS version
        $SMARTY = LMSSmarty::getInstance();

        // uncomment if you have old LMS version
        //$SMARTY = $hook_data['smarty'];

        $filename = ConfigHelper::getConfig('tauron.filename','tauron.json');
        $time_in_cache = ConfigHelper::getConfig('tauron.time_in_cache',60);

        if (file_exists($filename)) {
            $last_updated_cache = filemtime($filename);
        } else {
            $last_updated_cache = 0;
        }

        $commune = ConfigHelper::getConfig('tauron.commune');
        $commune = explode(",", $commune);

        $district = ConfigHelper::getConfig('tauron.district',6);
        $district = explode(",", $district);

        $province = ConfigHelper::getConfig('tauron.province',12);
        $province = explode(",", $province);
        
        $forwardDays = ConfigHelper::getConfig('tauron.forward_days',5);
        $forwardTimeSeconds = intval($forwardDays) * 24 * 60 * 60; $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/waapi');
        
        $outages = array();

        if ((time() - $last_updated_cache) > $time_in_cache) {

            $queryDateTimeStart = date("Y-m-d") . "T" . date("H") . "%3A" . date("i") . "%3A00.000Z";
            $queryDateTimeStop = date("Y-m-d", time() + $forwardTimeSeconds) . "T" . date("H") . "%3A" . date("i") . "%3A00.000Z";
            
            $CURLConnection = curl_init();

            foreach ($province as $provinceGAID) {
                foreach ($district as $districtGAID) {
                    if (!empty($commune)) {
                        foreach ($commune as $communeGAID) {
                            curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outages/area?provinceGAID=" . $provinceGAID . "&districtGAID=" . $districtGAID . "&fromDate=" . $queryDateTimeStart . "&toDate=" . $queryDateTimeStop . "&communeGAID=" . $communeGAID);
                            curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
                            $json = curl_exec($CURLConnection);
                            $outages = array_merge_recursive($outages, json_decode($json, true));
                        }
                    }
                    else {
                            curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outages/area?provinceGAID=" . $provinceGAID . "&districtGAID=" . $districtGAID . "&fromDate=" . $queryDateTimeStart . "&toDate=" . $queryDateTimeStop);
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
        $outages = file_get_contents($filename);
        $outages = json_decode($outages, true);

        $SMARTY->assign(
            'tauron_power_outages',
            array(
                'outages' => $outages['OutageItems'],
                'outages_count' => count($outages['OutageItems']),
                'last_updated_cache' => date("Y-m-d H:i:s", filemtime($filename)),
            )
        );
        return $hook_data;
    }

    public function accessTableInit()
    {
        $access = AccessRights::getInstance();
        $access->insertPermission(new Permission(
            'tauronpoweroutages_full_access',
            trans('Tauron power outages'),
            '^tauronpoweroutages$'
        ), AccessRights::FIRST_FORBIDDEN_PERMISSION);
    }
}
