<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2009 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                            *
********************************************************************************************************/

// --- Module texts -------------------------------------------------------------------------------------
$lang["modul_rechte"]				= "Modul-Rechte";
$lang["modul_liste"]				= "Liste";
$lang["modul_anlegen"]				= "News anlegen";
$lang["modul_kat_anlegen"]			= "Kategorie anlegen";
$lang["modul_titel"]				= "News";
$lang["modul_titel2"]				= "Newsverwaltung - Kategorie ";
$lang["fehler_recht"]				= "Keine ausreichenden Rechte um diese Aktion durchzuführen";
$lang["liste_leer"]					= "Keine News angelegt";
$lang["modul_list_feed"]            = "RSS-Feeds";
$lang["modul_new_feed"]             = "Neuer RSS-Feed";

$lang["permissions_header"]         = array(0 => "Anzeigen", 1 => "Bearbeiten", 2 => "Löschen", 3 => "Rechte", 4 => "News bearbeiten", 5 => "Feeds", 6 => "", 7 => "", 8 => "");

$lang["klapper"]					= "Kategorien ein-/ausblenden";

$lang["kat_anzeigen"]				= "Kategorie anzeigen";
$lang["kat_bearbeiten"]				= "Kategorie bearbeiten";
$lang["kat_loeschen_frage"]			= "Möchten Sie die Kategorie &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$lang["kat_rechte"]					= "Rechte bearbeiten";
$lang["kat_ausblenden"]				= "Kategorien ein-/ausblenden";

$lang["news_inhalt"]				= "Newsinhalte bearbeiten";
$lang["news_grunddaten"]			= "Newsgrunddaten bearbeiten";
$lang["news_rechte"]				= "Rechte bearbeiten";
$lang["news_loeschen_frage"]		= "Möchten Sie die News &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$lang["news_basicdata"]             = "News-Grunddaten";
$lang["news_title"]                 = "Titel:";
$lang["start"]                      = "Start-Datum:";
$lang["end"]                        = "Ende-Datum:";
$lang["archive"]                    = "Archiv-Datum:";
$lang["news_categories"]            = "Kategorien";
$lang["browser"]                    = "Browser öffnen";

$lang["news_intro"]                 = "Aufmacher:";
$lang["news_text"]                  = "Langtext:";
$lang["news_image"]                 = "Bild:";

$lang["news_cat_title"]             = "Kategorie-Titel:";
$lang["speichern"]                  = "Speichern";

$lang["feed_title"]                 = "Titel des Feeds:";
$lang["feed_urltitle"]              = "URL-Titel des Feeds:";
$lang["feed_link"]                  = "Link für weitere Infos:";
$lang["feed_desc"]                  = "Beschreibung des Feeds:";
$lang["feed_page"]                  = "Seite der Detailansicht:";
$lang["feed_cat"]                   = "Kategorie des Feeds:";
$lang["feed_cat_all"]               = "Alle Kategorien";
$lang["feed_liste_leer"]            = "Keine Feeds angelegt";
$lang["editNewsFeed"]               = "Feed bearbeiten";
$lang["feed_loeschen_frage"]        = "Möchten Sie den Feed &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";

$lang["_news_search_resultpage_"]         = "Treffer-Seite:";
$lang["_news_search_resultpage_hint"]     = "Auf dieser Seite erfolgt die Detailansicht der News, die in der Suche gefunden wurden.";

$lang["required_news_cat_title"]    = "Kategorie-Titel";
$lang["required_news_title"]        = "Newstitel";
$lang["required_feed_title"]        = "Feedtitel";
$lang["required_feed_urltitle"]     = "URL-Feedtitel";
$lang["required_feed_page"]         = "Detailseite";


// --- Quickhelp texts ----------------------------------------------------------------------------------

$lang["quickhelp_list"]             = "Alle News und Kategorien werden in dieser Ansicht aufgelistet.<br />Im ersten Teil werden die Kategorien aufgelistet, im zweiten die einzelnen Newsmeldungen. <br />Um alle News einer Kategorie anzuzeigen, kann per Klick auf 'Kategorie anzeigen' bei der entsprechenden Kategorie die Liste der News gefiltert werden.<br />In der Liste der News werden der Titel der News, die Anzahl Zugriffe sowie das Start- End und Archivdatum der News angezeigt.";
$lang["quickhelp_newNews"]          = "Beim Bearbeiten oder Anlegen einer News werden deren Grunddaten erfasst. Hierzu gehört unter Anderem der Titel der News. Des Weiteren können verschiedene Datumswerte definiert werden: <ul><li>Start-Datum: Ab diesem Datum erscheint die Newsmeldung im Portal</li><li>Ende-Datum: Ab diesem Datum verschwindet die Newsmeldung komplett aus dem Portal, auch aus dem Archiv</li><li>Archiv-Datum: Ab diesem Datum wandert die Newsmeldung in die Archivansicht</li></ul>Zusätzlich können die Zugehörigkeitein zu verschiedenen News-Kategorien angegeben werden.";
$lang["quickhelp_editNews"]         = "Beim Bearbeiten oder Anlegen einer News werden deren Grunddaten erfasst. Hierzu gehört unter Anderem der Titel der News. Des Weiteren können verschiedene Datumswerte definiert werden: <ul><li>Start-Datum: Ab diesem Datum erscheint die Newsmeldung im Portal</li><li>Ende-Datum: Ab diesem Datum verschwindet die Newsmeldung komplett aus dem Portal, auch aus dem Archiv</li><li>Archiv-Datum: Ab diesem Datum wandert die Newsmeldung in die Archivansicht</li></ul>Zusätzlich können die Zugehörigkeitein zu verschiedenen News-Kategorien angegeben werden.";
$lang["quickhelp_newCat"]           = "Für eine neue oder bereits vorhanden Kategorie kann momentan lediglich ein Titel vergeben werden.";
$lang["quickhelp_editCat"]          = "Für eine neue oder bereits vorhanden Kategorie kann momentan lediglich ein Titel vergeben werden.";
$lang["quickhelp_editNewscontent"]  = "Die eigentlichen Inhalte einer News werden in dieser Ansicht erfasst und bearbeitet.";
$lang["quickhelp_newsFeed"]         = "Die Verwaltung der RSS-Feeds erfolgt in diesem Teil der Newsverwaltung. In dieser Liste finden Sie alle RSS-Feeds, die im System konfiguriert wurden.";
$lang["quickhelp_newNewsFeed"]      = "Mit Hilfe des aktuellen Formulars können die Eigenschaften eines vorhandenen, oder eines anzulegenden Newsfeeds verändert werden.<br />Die Seite 'Detailansicht' wird dann aufgerufen, wenn ein Abonnent des Newsfeeds die Detaildarstellung der Newsmeldung anfordert. Mit der Einstellung 'Kategorie des Feeds' können die im Feed anzuzeigenden Newsmeldungen eingeschränkt werden. <br />Über das Feld URL-Titel wird ein Titel des Feeds festgelegt, anhand dessen der Feed im Internet erreicht werden kann, z.B. /newsnfacts.rss. Dieser Titel sollte nur aus Buchstaben und Ziffern bestehen (a-z, A-Z, 0-9).";
$lang["quickhelp_editNewsFeed"]     = "Mit Hilfe des aktuellen Formulars können die Eigenschaften eines vorhandenen, oder eines anzulegenden Newsfeeds verändert werden.<br />Die Seite 'Detailansicht' wird dann aufgerufen, wenn ein Abonnent des Newsfeeds die Detaildarstellung der Newsmeldung anfordert. Mit der Einstellung 'Kategorie des Feeds' können die im Feed anzuzeigenden Newsmeldungen eingeschränkt werden. <br />Über das Feld URL-Titel wird ein Titel des Feeds festgelegt, anhand dessen der Feed im Internet erreicht werden kann, z.B. /newsnfacts.rss. Dieser Titel sollte nur aus Buchstaben und Ziffern bestehen (a-z, A-Z, 0-9).";
?>