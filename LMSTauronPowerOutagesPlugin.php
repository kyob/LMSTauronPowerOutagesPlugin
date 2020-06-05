<?php

/**
 * LMSTauronPowerOutagesPlugin
 * 
 * @author Łukasz Kopiszka <lukasz@alfa-system.pl>
 */
class LMSTauronPowerOutagesPlugin extends LMSPlugin
{
    const PLUGIN_NAME = 'LMS Tauron Power Outages plugin';
    const PLUGIN_DESCRIPTION = 'Shows power outages in areas served by Tauron.';
    const PLUGIN_AUTHOR = 'Łukasz Kopiszka &lt;lukasz@alfa-system.pl&gt;';
    const PLUGIN_DIRECTORY_NAME = 'LMSTauronPowerOutagesPlugin';

    public function registerHandlers()
    {
        $this->handlers = array(
            'menu_initialized' => array(
                'class' => 'TauronPowerOutagesHandler',
                'method' => 'menuTauronPowerOutages'
            ),
            'smarty_initialized' => array(
                'class' => 'TauronPowerOutagesHandler',
                'method' => 'smartyTauronPowerOutages'
            ),
            'modules_dir_initialized' => array(
                'class' => 'TauronPowerOutagesHandler',
                'method' => 'modulesDirTauronPowerOutages'
            ),
            'welcome_before_module_display' => array(
                'class' => 'TauronPowerOutagesHandler',
                'method' => 'welcomeTauronPowerOutages'
            )
        );
    }
}
