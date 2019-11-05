<?php

$lang["modul_titel"]            = "Flow";
$lang["modul_titel_flow"]       = "Einstellungen";
$lang["modul_titel_assignment"] = "Objekte";
$lang["modul_titel_step"]       = "Workflow";
$lang["modul_titel_transition"] = "Übergänge";
$lang["modul_titel_transition_action"] = "Aktionen";
$lang["modul_titel_transition_condition"] = "Bedingungen";

$lang["list_flow"]              = "Einstellungen";
$lang["list_flow_long_description"] = "Erstellt am: {0}";
$lang["list_flow_no_status"] = "Kein Status vorhanden";
$lang["list_flow_no_right"] = "Keine Berechtigung";

$lang["action_steps"]           = "Workflow-Schritte";
$lang["action_transitions"]     = "Übergänge";

$lang["action_transition_action"] = "Aktionen";
$lang["action_transition_condition"] = "Bedingungen";

$lang["form_flow_name"]         = "Name";
$lang["form_flow_iconcolor"]    = "Farbe";
$lang["form_flow_headline_groups"] = "Gruppen Konfiguration";
$lang["form_flow_headline_roles"] = "Rollenkonfiguration";
$lang["form_flow_headline_transitions"] = "Übergänge";
$lang["form_flow_viewgroups"]   = "Ansehen";
$lang["form_flow_editgroups"]   = "Bearbeiten";
$lang["form_flow_deletegroups"] = "Löschen";
$lang["form_flow_rightgroups"]  = "Rechte";
$lang["form_flow_usergroup"]    = "Benutzergruppe";
$lang["form_flow_transitions"]  = "Übergänge";
$lang["form_flow_visible"]      = "Sichtbar";

$lang["form_flow_targetstatus"] = "Ziel-Status";
$lang["form_flow_actions"]      = "Aktionen";
$lang["form_flow_conditions"]   = "Bedingungen";

$lang["form_flow_config"]       = "Konfiguration";

$lang["flow_step_icon_0"]       = "icon_flag_black";
$lang["flow_step_icon_1"]       = "icon_flag_blue";
$lang["flow_step_icon_2"]       = "icon_flag_brown";
$lang["flow_step_icon_3"]       = "icon_flag_green";
$lang["flow_step_icon_4"]       = "icon_flag_grey";
$lang["flow_step_icon_5"]       = "icon_flag_orange";
$lang["flow_step_icon_6"]       = "icon_flag_purple";
$lang["flow_step_icon_7"]       = "icon_flag_red";
$lang["flow_step_icon_8"]       = "icon_flag_yellow";

$lang["transition_visible_0"]   = "Nein";
$lang["transition_visible_1"]   = "Ja";

$lang["form_flow_skip"]    = "Statuswechsel";
$lang["transition_skip_0"] = "Automatisch";
$lang["transition_skip_1"] = "Keinen";

$lang["flow_step_no_delete"] = "Dieser Status kann nicht gelöscht werden";

$lang["delete_step_question"]   = "Möchten Sie den Schritt &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$lang["delete_transition_question"]   = "Möchten Sie den Übergang &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$lang["delete_transition_action_question"]   = "Möchten Sie die Aktion &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$lang["delete_transition_condition_question"]   = "Möchten Sie die Bedingung &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";
$lang["delete_question"]   = "Möchten Sie die Konfiguration &quot;<b>%%element_name%%</b>&quot; wirklich löschen?";

$lang["flow_transition_action_list"]   = "Die folgenden Aktionen sind zu dem Statusübergang (<b>{0}</b> nach <b>{1}</b>) zugeordnet.";
$lang["flow_transition_condition_list"]   = "Die folgenden Bedingungen sind zu dem Statusübergang (<b>{0}</b> nach <b>{1}</b>) zugeordnet.";

$lang["form_next_status_description"]   = "Auf dieser Seite können Sie für das Objekt <b>{0}</b> den Folgestatus auswählen.";

$lang["action_status_change_title"]   = "Rückmeldung";
$lang["action_status_change_success"]   = "Statuswechsel erfolgreich durchgeführt.";
$lang["action_status_change_redirect"]   = "Das System benötigt eine Benutzereingabe, um den Statusübergang durchzuführen.";

$lang["flow_controller_trait_headline_status"]   = "Statuswechsel";
$lang["flow_controller_trait_headline_action"]   = "Aktionen";

$lang["action_skip_transition_title"]   = "Überspringe Status Nachricht";
$lang["action_skip_transition_description"] = "";

$lang["flow_condition_and_title"]   = "AND Bedingung";
$lang["flow_condition_case_title"]   = "Case Bedingung";
$lang["flow_condition_group_title"]   = "Group Bedingung";
$lang["flow_condition_invert_title"]   = "Invert Bedingung";
$lang["flow_condition_or_title"]   = "OR Bedingung";
$lang["flow_condition_xor_title"]   = "XOR Bedingung";

$lang['flow_condition_user_forbidden']              = 'Check Benutzerberechtigung';
$lang['flow_condition_user_forbidden_title']        = 'Check Benutzerberechtigung für Statusübergang';
$lang['flow_condition_user_forbidden_description']  = 'Ein Benutzer darf nicht zwei aufeinander folgende Statusübergänge vornehmen (4 Augen Prinzip).';
$lang['flow_condition_user_forbidden_error']        = 'Sie haben keine Berechtigung den Statusübergang vorzunehmen. Dieser Schritt muss durch einen zweiten Benutzer erfolgen.';
