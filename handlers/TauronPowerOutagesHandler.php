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
    public function welcomeTauronPowerOutages(array $hook_data = [])
    {
        require_once __DIR__ . '/../lib/TauronPowerOutagesFetcher.php';

        $SMARTY = LMSSmarty::getInstance();

        $inlineRefresh = ConfigHelper::getConfig('tauron.inline_refresh', false);
        $timeInCache = intval(ConfigHelper::getConfig('tauron.time_in_cache', 300));
        $filename = ConfigHelper::getConfig('tauron.filename', 'tauron.json');
        $cachePath = TauronPowerOutagesFetcher::resolveCachePath($filename);

        $cacheExists = file_exists($cachePath);
        $lastUpdatedCache = $cacheExists ? filemtime($cachePath) : 0;
        $cacheIsStale = (time() - $lastUpdatedCache) > $timeInCache;

        if ($inlineRefresh && $cacheIsStale) {
            // Best-effort, short-timeout refresh; failures do not block the page.
            try {
                TauronPowerOutagesFetcher::fetchAndCache([
                    'province' => explode(",", ConfigHelper::getConfig('tauron.province', '24')),
                    'district' => explode(",", ConfigHelper::getConfig('tauron.district', '6')),
                    'commune' => explode(",", ConfigHelper::getConfig('tauron.commune', '')),
                    'forward_days' => intval(ConfigHelper::getConfig('tauron.forward_days', 7)),
                    'api_url' => ConfigHelper::getConfig('tauron.api_url', 'https://www.tauron-dystrybucja.pl/waapi'),
                    'timeout' => intval(ConfigHelper::getConfig('tauron.timeout', 5)),
                    'user_agent' => ConfigHelper::getConfig('tauron.user_agent', 'LMS-Tauron-Power-Outages'),
                    'cache_path' => $cachePath,
                ]);
                $lastUpdatedCache = file_exists($cachePath) ? filemtime($cachePath) : $lastUpdatedCache;
            } catch (Exception $e) {
                // Do not break page rendering; leave cache as-is.
            }
        }

        $cache = TauronPowerOutagesFetcher::loadCache($cachePath);
        $outageItems = $cache['items'];
        $outagesCount = is_array($outageItems) ? count($outageItems) : 0;
        $lastUpdatedDate = $lastUpdatedCache ? date("Y-m-d H:i:s", $lastUpdatedCache) : '-';

        $SMARTY->assign(
            'tauron_power_outages',
            [
                'outages' => $outageItems,
                'outages_count' => $outagesCount,
                'last_updated_cache' => $lastUpdatedDate
            ]
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