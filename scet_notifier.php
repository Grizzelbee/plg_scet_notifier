<?php
/**
 * @copyright	Copyright (C) 2005 - 2012 by Hanjo Hingsen, All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 * @version     2.5.6
 * @history     
        V2.5.6, 2012-09-26, Hanjo
            [+] Unterstützung für Jahrestag in die Heute-meldung eingebaut.

            V2.5.5, 2012-04-17, Hanjo
            [+] Getrennte Meldungen für neue und aktualisierte Termine
            
        V2.5.4, 2012-04-16, Hanjo
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
        $db = JFactory::getDbo();
        // Get UserId 
        $thisUserId = intval(JUserHelper::getUserId($user['username']));
      
        $db->setQuery( 'SELECT max(inserted) as newData FROM #__scet_events' );
        $newestEvent = $db->loadResult();

        $db->setQuery( 'SELECT max(updated) as lastChange FROM #__scet_events' );
        $lastEventUpdate = $db->loadResult();

        $db->setQuery( 'SELECT lastvisitdate FROM #__scet_visits where juserid=' . $thisUserId );
        $lastUserVisit = $db->loadResult();

        // Feature one: Leave an Information, that the Eventpage has new Entries
        if ( $newestEvent > $lastUserVisit ) {
          JError::raiseNotice( 1000, JText::_( $this->params->get( 'MSG_NEW_DATA', 'Error reading Param: MSG_NEW_DATA' ) ));
          // GetActualURL
          $actualLoginUrl = Jfactory::GetURI()->current();
          $redirectUrl = '&return=' . urlencode(base64_encode('index.php?option=com_scet&view=scet'));
          $finalUrl = $actualLoginUrl . $redirectUrl;
          // SetFinalURL
        }
        
        // Feature two: Leave an Information, that the Eventpage has been updated
        if ( $lastEventUpdate > $lastUserVisit ) {
          JError::raiseNotice( 1000, JText::_( $this->params->get( 'MSG_UPDATED', 'Error reading Param: MSG_UPDATED' ) ));
        }

        // Feature three: Leave an Information, that today is an Event.
        $today = date('Y-m-d', time());
        $db->setQuery( "SELECT  event,  
                                CASE
                                    WHEN anniversary = 1 THEN DATE(CONCAT(YEAR(CURRENT_DATE), '-', MONTH(datum), '-', DAY(datum)) )
                                    ELSE datum
                                END AS datum,
                                CASE 
                                    WHEN anniversary=1 THEN YEAR(CURRENT_DATE) - YEAR(datum) 
                                    ELSE 0 
                                END AS iteration 
                        FROM    #__scet_events 
                        WHERE   published = 1;" );
        $events = $db->loadObjectList();
        foreach ($events as $event) {
            if ($event->datum == $today){
                JError::raiseNotice(1000, JText::_( Trim($this->params->get( 'MSG_TODAY', 'Error reading Param: MSG_TODAY' )) ) . sprintf(" $event->event", $event->iteration) );
            }
        }
	}
}