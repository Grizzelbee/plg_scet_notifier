<?php
/**
 * @copyright	Copyright (C) 2005 - 2012 by Hanjo Hingsen, All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @version     2.5.4
 * @history     V2.5.4, 2012-04-16, Hanjo
                    [~] SQL angepasst. Tabellenname musste lowercase sein. Sonst funktioniert das SQL unter Linux nicht.
                    [+] Upgrade als Installationsmethode eingebaut
                    
                V2.5.3, 2012-04-16, Hanjo
                    [~] Von Sprach-INIs auf DB-Parameter umgestellt, das macht die Verwaltung der 
                        Meldungstexte noch einfacher.
                        
                V2.5.2, 2012-04-10, Hanjo
                    [+] Update-Server eingebaut
                    
                V2.5.1, 2012-03-xx, Hanjo
                    [+] Heute-Meldung eingebaut
                    
                V2.5.0, 2012-03-xx, Hanjo
                    [+] Erste Version
 }
 */
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.plugin.plugin');

class plgUserSCET_Notifier extends JPlugin
{
    public function __construct(& $subject, $config)
    {
        parent::__construct($subject, $config);
    }
    
    function onUserLogin($user, $options)
    {
        // run this only on FrontEnd-Login, not on Backend-Login
        if ( JFactory::getApplication()->isAdmin() ) {
            return;  
        }

        // Load the profile data from the database.
        $db         = JFactory::getDbo();
        // Get UserId 
        $thisUserId = intval(JUserHelper::getUserId($user['username']));
      
        $db->setQuery( 'SELECT max(updated) as lastChange FROM #__scet_events' );
        $lastEventUpdate = $db->loadResult();

        $db->setQuery( 'SELECT lastvisitdate FROM #__scet_visits where juserid=' . $thisUserId );
        $lastUserVisit = $db->loadResult();

        // Feature one: Leave an Information, that the Eventpage has been updated
        if ( $lastEventUpdate > $lastUserVisit ) {
          JError::raiseNotice( 1000, JText::_( $this->params->get( 'MSG_NEW_DATA', 'Error reading Param: MSG_NEW_DATA' ) ));
        }
        
        // Feature two: Leave an Information, that today is an Event.
        $today = date('Y-m-d', time());
        $db->setQuery( 'SELECT * FROM #__scet_events where published = 1;' );
        $events = $db->loadObjectList();
        foreach ($events as $event) {
            if ($event->datum == $today){
                JError::raiseNotice(1000, JText::_( Trim($this->params->get( 'MSG_TODAY', 'Error reading Param: MSG_TODAY' )) ) . " $event->event" );
            }
        }
	}
}