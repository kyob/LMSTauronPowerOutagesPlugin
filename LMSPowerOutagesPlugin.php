<?php

/**
 * LMSPowerOutagesPlugin
 * 
 * @author Łukasz Kopiszka <lukasz@alfa-system.pl>
 */
class LMSPowerOutagesPlugin extends LMSPlugin
{
    const PLUGIN_NAME = 'LMS PowerOutages Plugin';
    const PLUGIN_DESCRIPTION = 'Shows power outages in areas served by Tauron.';
    const PLUGIN_AUTHOR = 'Łukasz Kopiszka &lt;lukasz@alfa-system.pl&gt;';
    const PLUGIN_DIRECTORY_NAME = 'LMSPowerOutagesPlugin';

    public function registerHandlers()
    {
        $this->handlers = array(
            'menu_initialized' => array(
                'class' => 'PowerOutagesHandler',
                'method' => 'menuPowerOutages'
            ),
            'smarty_initialized' => array(
                'class' => 'PowerOutagesHandler',
                'method' => 'smartyPowerOutages'
            ),
            'modules_dir_initialized' => array(
                'class' => 'PowerOutagesHandler',
                'method' => 'modulesDirPowerOutages'
            ),
            'welcome_before_module_display' => array(
                'class' => 'PowerOutagesHandler',
                'method' => 'welcomePowerOutages'
            )
        );
    }
}
