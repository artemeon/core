<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                           *
********************************************************************************************************/

// --- Module texts -------------------------------------------------------------------------------------

$text["modul_titel"]				= "Seiten";
$text["modul_rechte"]				= "Modul-Rechte";
$text["modul_liste"]				= "Liste";
$text["modul_liste_alle"]			= "Flache Liste";
$text["modul_neu"]					= "Neue Seite";
$text["modul_neu_ordner"]			= "Neuer Ordner";
$text["modul_elemente"]				= "Elemente";
$text["modul_element_neu"]			= "Neues Element";
$text["updatePlaceholder"]          = "Platzhalter anpassen";

$text["permissions_header"]         = array(0 => "Anzeigen", 1 => "Bearbeiten", 2 => "Löschen", 3 => "Rechte", 4 => "Elemente", 5 => "Ordner", 6 => "Platzhalter", 7 => "", 8 => "");

$text["browser"]					= "Browser öffnen";
$text["klapper"]					= "Ordner ein-/ausblenden";

$text["seite_bearbeiten"]			= "Seite bearbeiten";
$text["liste_seiten_leer"]			= "Keine Seiten angelegt";
$text["seite_inhalte"]				= "Seiteninhalte bearbeiten";
$text["seite_loeschen_frage"]		= "Möchten Sie die Seite &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$text["seite_loeschen_erfolg"]		= "Seite erfolgreich gelöscht";
$text["seite_rechte"]				= "Rechte bearbeiten";
$text["seite_vorschau"]				= "Vorschau anzeigen";
$text["seite_copy"]                 = "Seite kopieren";
$text["lastuserTitle"]				= "Letzter Autor:";
$text["lasteditTitle"]				= "Letzte Änderung:";
$text["pageNameTitle"]				= "Seitenname:";

$text["pages_hoch"]					= "Eine Ebene nach oben";
$text["pages_ordner_oeffnen"]		= "Ordner öffnen";
$text["ordner_anlegen_erfolg"]		= "Ordner erfolgreich angelegt";
$text["ordner_loeschen_erfolg"]		= "Ordner erfolgreich gelöscht";
$text["ordner_loeschen_fehler"]		= "Fehler beim Löschen des Ordners";
$text["ordner_loschen_leer"]        = "Ordner kann nicht gelöscht werden, er ist nicht leer";
$text["pages_ordner_rechte"]		= "Rechte bearbeiten";
$text["pages_ordner_loeschen_frage"]= "Möchten Sie den Ordner &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";

$text["pages_ordner_edit"]			= "Ordner bearbeiten";

$text["inhalte_titel"]				= "Seitenverwaltung - ";
$text["inhalte_navi2"]				= "Seite: ";
$text["inhalte_liste"]				= "Liste der Seiten";
$text["inhalte_element"]			= "Seitenelemente verwalten";

$text["fehler_recht"]				= "Keine ausreichenden Rechte um diese Aktion durchzuführen";
$text["fehler_name"]				= "Kein Seitenname angegeben";

$text["element_bearbeiten"]			= "Element bearbeiten";
$text["element_install"]            = "Element installieren";
$text["element_installer_hint"]     = "Gefundene Installer noch nicht installierter Elemente:";
$text["element_anlegen"]			= "Element anlegen";
$text["element_anlegen_fehler"]		= "Fehler beim Anlegen des Elements";
$text["element_bearbeiten_fehler"]	= "Fehler beim Bearbeiten des Elements";

$text["element_loeschen_frage"]		= "Möchten Sie das Element &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$text["element_loeschen_fehler"]	= "Fehler beim Löschen des Elements";
$text["element_hoch"]				= "Element nach oben verschieben";
$text["element_runter"]				= "Element nach unten verschieben";
$text["element_status_aktiv"]		= "Status ändern (ist aktiv)";
$text["element_status_inaktiv"]		= "Status ändern (ist inaktiv)";
$text["element_liste_leer"]			= "Keine Elemente im Template vorhanden";
$text["elemente_liste_leer"]		= "Keine Elemente installiert";

$text["option_ja"]					= "Ja";
$text["option_nein"]				= "Nein";

$text["ds_gesperrt"]				= "Der Datensatz ist momentan gesperrt";
$text["ds_seite_gesperrt"]			= "Die Seite kann nicht gelöscht werden, da sie gesperrte Datensätze beinhaltet";
$text["ds_entsperren"]				= "Datensatz entsperren";

$text["warning_elementsremaining"]  = "ACHTUNG<br>Im System befinden sich Seitenelemente, die keinem Platzhalter zugeordnet werden können. Dies kann der Fall sein, wenn ein Platzhalter im Template umbenannt oder gelöscht wurde. Um Platzhalter auch im System umzubenennen, können Sie die Funktion \"Platzhalter anpassen\" verwenden. Eine Liste der betroffenen Elemente befindet sich unter dieser Warnung.";

$text["placeholder"]                = "Platzhalter: ";

$text["name"]						= "Name (sprachunabhängig):";
$text["beschreibung"]				= "Beschreibung:";
$text["keywords"]					= "Keywords:";
$text["ordner_name"]				= "Ordner:";
$text["ordner_name_parent"]			= "Übergeordneter Ordner:";
$text["template"]					= "Template:";
$text["browsername"]                = "Browsertitel:";
$text["seostring"]                  = "SEO-URL-Keywords:";
$text["templateNotSelectedBefore"]  = "ACHTUNG: Für diese Seite wurde noch kein Template gewählt!";

$text["element_name"]				= "Name:";
$text["element_admin"]				= "Admin-Klasse:";
$text["element_portal"]				= "Portal-Klasse:";
$text["element_repeat"]				= "Wiederholbar:";
$text["submit"]						= "Speichern";
$text["element_cachetime"]          = "Max. Cachedauer:";
$text["element_cachetime_hint"]     = "in Sekunden (-1 = kein Caching)";

$text["_pages_templatewechsel_"]        = "Templatewechsel erlaubt:";
$text["_pages_templatewechsel_hint"]    = "Definiert, ob das Template einer Seite geändert werden darf, wenn diese bereits Elemente enthält. Wird dies erlaubt, kann es zu unerwarteten Nebeneffekten führen!";

$text["_pages_maxcachetime_"]       = "Maximale Cachedauer:";
$text["_pages_maxcachetime_hint"]   = "Gibt an, wie viele Sekunden eine Seite im Cache maximal gültig ist.";

$text["_pages_portaleditor_"]       = "Portaleditor aktiv:";

$text["_pages_newdisabled_"]        = "Neue Seiten inaktiv:";
$text["_pages_newdisabled_hint"]    = "Wenn diese Option aktiviert wird, sind neu angelegte Seiten inaktiv";

$text["_pages_cacheenabled_"]       = "Seitencache aktiv:";

$text["_pages_startseite_"]         = "Startseite:";
$text["_pages_fehlerseite_"]        = "Fehlerseite:";
$text["_pages_defaulttemplate_"]    = "Standardtemplate:";

$text["page_element_placeholder_title"] = "Interner Titel:";
$text["page_element_system_folder"] = "Optionale Felder ein/ausblenden";
$text["page_element_start"]         = "Anzeigezeitraum Start:";
$text["page_element_end"]           = "Anzeigezeitraum Ende:";
$text["element_pos"]                = "Position am Platzhalter:";
$text["element_first"]              = "Am Anfang des Platzhalters";
$text["element_last"]               = "Am Ende des Platzhalters";
$text["page_element_placeholder_language"] = "Sprache:";

$text["required_ordner_name"]       = "Name des Ordners";
$text["required_element_name"]      = "Name des Elements";
$text["required_element_cachetime"] = "Cachedauer des Elements";
$text["required_name"]              = "Name der Seite";
$text["required_elementid"]         = "Ein Element mit diesem Namen exisitiert bereits.";

$text["plUpdateHelp"]               = "Hier können die in der Datenbank gespeicherten Platzhalter aktualisiert werden.<br />Dies kann dann nötig werden, wenn ein Platzhalter um ein weiteres mögliches Seitenelement erweitert wurde. In diesem Fall erscheinen die Seitenelement zwar beim Bearbeiten der Seite, nicht aber im Portal. Um dies zu ändern müssen die in der Datenbank hinterlegten Platzhalter an die neuen Platzhalter angepasst werden.<br /> Hierfür ist es notwendig, den Namen des veränderten Templates, den Titel des alten Platzhalters (name_element), sowie des neuen Platzhalters (name_element|element2) anzugeben. Platzhaler sind ohne Prozentzeichen anzugeben.";
$text["plRename"]                   = "Anpassen";
$text["plToUpdate"]                 = "Alter Platzhalter:";
$text["plNew"]                      = "Neuer Platzhalter:";
$text["plUpdateTrue"]               = "Das Umbenennen war erfolgreich.";
$text["plUpdateFalse"]              = "Beim Umbenennen ist ein Fehler aufgetreten.";


// portaleditor

$text["pe_edit"]                            = "Bearbeiten";
$text["pe_new"]                             = "Neues Element";
$text["pe_delete"]                          = "Löschen";
$text["pe_shiftUp"]                         = "Nach oben";
$text["pe_shiftDown"]                       = "Nach unten";

$text["pe_status_page"]                     = "Seite:";
$text["pe_status_status"]                   = "Status:";
$text["pe_status_autor"]                    = "Letzter Autor:";
$text["pe_status_time"]                     = "Letzte Änderung:";

$text["pe_icon_edit"]                       = "Seite in der Administration öffnen";
$text["pe_icon_page"]                       = "Grunddaten der Seite in der Administration bearbeiten";
$text["pe_disable"]                         = "Den Portaleditor temporär deaktivieren";
$text["pe_enable"]                          = "Den Portaleditor aktivieren";


$text["systemtask_flushpagescache_name"]    = "Seitencache leeren";
$text["systemtask_flushpagescache_done"]    = "Leeren abgeschlossen.";


// --- Quickhelp texts ----------------------------------------------------------------------------------

$text["quickhelp_list"]             = "In dieser Ansicht können Sie durch die Seitenstruktur Ihres Systems navigieren. <br />Die Seiten können hierfür in virtuellen Ordnern gegliedert werden.<br />In der Listenansicht beim Bearbeiten der Seiteninhalte können Elemente an einem Platzhalter angelegt, bearbeitet oder gelöscht werden.";
$text["quickhelp_listAll"]			= "In der flachen Liste werden alle Seiten, die im System angelegt wurden, angezeigt.<br />Die Ordnerstruktur wird dabei ignoriert und ausgeblendet.<br />Die Ansicht kann zum schnellen Auffinden von Seiten im System hilfreich sein.";
$text["quickhelp_newPage"]			= "Mit Hilfe dieses Formulars können die Grunddaten einer Seite erfasst oder bearbetet werden.<br />Hierfür können die folgenden Felder erfasst werden:<br /><ul><li>Name: Der Seitenname der Seite. Über diesen wird die Seite später im Portal aufgerufen.</li><li>Browsertitel: Das Browserfenster wird im Portal mit diesem Titel versehen.</li><li>SEO-URL-Keywords: Search-Engine-Optimization, geben Sie hier passende Keywords zur Optimierung der Seite im Hinblick auf Suchmaschinen an. Die Keywords werden der URL angehängt.</li><li>Beschreibung: Eine knappe Beschreibung der Seite. Dieser Text wird u.A. in den Suchergebnissen ausgegeben.</li><li>Keywords: Die hier eingegebene, kommaseparierte Liste an Keywords wird in den Quelltext der Seite eingebettet. Auch dies ist für Suchmaschinen relevant.</li><li>Ordner: Der interne Ordner, in dem die Seite abgelegt wird.</li><li>Template: Das der Seite zu Grunde liegende Template. Das Feld kann in der Regel nur verändert werden, wenn auf der Seite keine Inhalte hinterlegt wurden.</li></ul>";
$text["quickhelp_newFolder"]		= "Zum Anlegen oder Umbenennen eines Ordners kann hier der Name des Ordners definiert werden.";
$text["quickhelp_editFolder"]		= "Zum Anlegen oder Umbenennen eines Ordners kann hier der Name des Ordners definiert werden.";
$text["quickhelp_listElements"]		= "In dieser Liste befinden sich alle momentam im System verfügbaren Seitenelemente.<br />Der Name des Elements enstpricht hierbei dem hinteren Teil eines Platzhalters im Template.<br />Findet das System Installer von Elementen, die bisher noch nicht installiert sind, so werden diese am Ende der Liste zu Installation angeboten.";
$text["quickhelp_newElement"]		= "Dieses Formular dient zum Anlegen und Bearbeiten der Grunddaten von Seitenelementen. Hierfür stehen die folgenden Eingabefelder zur Verfügung:<br /><ul><li>Name: Titel des Elements</li><li>Max. Cachedauer: Zeitdauer in Sekunden, die das Element maximal gecached werden darf.<br />Nach Ablauf dieses Zeitraums wird die Seite neu generiert.</li><li>Admin-Klasse: Klasse, die das Admin-Formular bereitstellt.</li><li>Portal-Klasse: Klasse, die die Portal-Ausgabe übernimmt.</li><li>Wiederholbar: Legt fest, ob ein Element an einem Platzhalter mehrfach angelegt werden darf.</li></ul>";
$text["quickhelp_editElement"]		= "Dieses Formular dient zum Anlegen und Bearbeiten der Grunddaten von Seitenelementen. Hierfür stehen die folgenden Eingabefelder zur Verfügung:<br /><ul><li>Name: Titel des Elements</li><li>Max. Cachedauer: Zeitdauer in Sekunden, die das Element maximal gecached werden darf.<br />Nach Ablauf dieses Zeitraums wird die Seite neu generiert.</li><li>Admin-Klasse: Klasse, die das Admin-Formular bereitstellt.</li><li>Portal-Klasse: Klasse, die die Portal-Ausgabe übernimmt.</li><li>Wiederholbar: Legt fest, ob ein Element an einem Platzhalter mehrfach angelegt werden darf.</li></ul>";
$text["quickhelp_flushCache"]		= "Herzlichen Glückwunsch - der Seitencache wurde soeben geleert ;-)";
$text["quickhelp_updatePlaceholder"] = "ACHTUNG! Diese Aktion wird nur dann benötigt, wenn im Template ein Platzhalter erweitert wurde.<br />Wird im Template ein Platzhalter verändern, so werden die zugeordneten Inhalte von nun an im Portal nicht mehr ausgegeben, da im System noch der alte Platzhalter hinterlegt ist. Um die Platzhalter im System anzupassen, können diese hier ersetzt werden.";

?>