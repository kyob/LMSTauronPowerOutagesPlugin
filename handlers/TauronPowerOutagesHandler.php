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

        $commune = ConfigHelper::getConfig('tauron.commune',502);
        $commune = explode(",", $commune);

        $district = ConfigHelper::getConfig('tauron.district',6);
        $district = explode(",", $district);

        $api_url = ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/iapi');
        $outages = array();

        if ((time() - $last_updated_cache) > $time_in_cache) {

            $CURLConnection = curl_init();

            foreach ($commune as $gaid) {
                curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=commune");
                curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
                $json = curl_exec($CURLConnection);
                $outages = array_merge_recursive($outages, json_decode($json, true));
            }

            foreach ($district as $gaid) {
                curl_setopt($CURLConnection, CURLOPT_URL, $api_url . "/outage/GetOutages?gaid=" . $gaid . "&type=district");
                curl_setopt($CURLConnection, CURLOPT_RETURNTRANSFER, true);
                $json = curl_exec($CURLConnection);
                $outages = array_merge_recursive($outages, json_decode($json, true));
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
                'outages' => $outages,
                'outages_count' => count($outages['CurrentOutagePeriods']) + count($outages['FutureOutagePeriods']),
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
