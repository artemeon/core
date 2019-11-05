<?php

$lang["modul_titel"]            = "Flow";
$lang["modul_titel_flow"]       = "Settings";
$lang["modul_titel_assignment"] = "Objects";
$lang["modul_titel_step"]       = "Workflow";
$lang["modul_titel_transition"] = "Transition";
$lang["modul_titel_transition_action"] = "Actions";
$lang["modul_titel_transition_condition"] = "Conditions";

$lang["list_flow"]              = "Settings";
$lang["list_flow_long_description"] = "Created at: {0}";
$lang["list_flow_no_status"] = "No status available";
$lang["list_flow_no_right"] = "No rights";

$lang["action_steps"]           = "Workflow steps";
$lang["action_transitions"]     = "Transitions";

$lang["action_transition_action"] = "Actions";
$lang["action_transition_condition"] = "Conditions";

$lang["form_flow_name"]         = "Name";
$lang["form_flow_iconcolor"]    = "Color";
$lang["form_flow_headline_groups"] = "Group configuration";
$lang["form_flow_headline_roles"] = "Role configuration";
$lang["form_flow_headline_transitions"] = "Transitions";
$lang["form_flow_viewgroups"]   = "View";
$lang["form_flow_editgroups"]   = "Edit";
$lang["form_flow_deletegroups"] = "Delete";
$lang["form_flow_rightgroups"]  = "Rights";
$lang["form_flow_usergroup"]    = "User groups";
$lang["form_flow_transitions"]  = "Transitions";
$lang["form_flow_visible"]      = "Visible";

$lang["form_flow_targetstatus"] = "Target-Status";
$lang["form_flow_actions"]      = "Actions";
$lang["form_flow_conditions"]   = "Conditions";

$lang["form_flow_config"]       = "Configuration";

$lang["flow_step_icon_0"]       = "icon_flag_black";
$lang["flow_step_icon_1"]       = "icon_flag_blue";
$lang["flow_step_icon_2"]       = "icon_flag_brown";
$lang["flow_step_icon_3"]       = "icon_flag_green";
$lang["flow_step_icon_4"]       = "icon_flag_grey";
$lang["flow_step_icon_5"]       = "icon_flag_orange";
$lang["flow_step_icon_6"]       = "icon_flag_purple";
$lang["flow_step_icon_7"]       = "icon_flag_red";
$lang["flow_step_icon_8"]       = "icon_flag_yellow";

$lang["transition_visible_0"]   = "No";
$lang["transition_visible_1"]   = "Yes";

$lang["form_flow_skip"]    = "Status change";
$lang["transition_skip_0"] = "Automatic";
$lang["transition_skip_1"] = "None";

$lang["flow_step_no_delete"] = "It is not possible to delete this status";

$lang["delete_step_question"]   = "Do you really want to delete the step &quot;<b>%%element_name%%</b>&quot;?";
$lang["delete_transition_question"]   = "Do you really want to delete the transition &quot;<b>%%element_name%%</b>&quot;?";
$lang["delete_transition_action_question"]   = "Do you really want to delete the action &quot;<b>%%element_name%%</b>&quot;?";
$lang["delete_transition_condition_question"]   = "Do you really want to delete the condition &quot;<b>%%element_name%%</b>&quot;?";
$lang["delete_question"]   = "Do you really want to delete the configuration &quot;<b>%%element_name%%</b>&quot;?";

$lang["flow_transition_action_list"]   = "The following actions are assigned to the transition (<b>{0}</b> to <b>{1}</b>).";
$lang["flow_transition_condition_list"]   = "The following conditions are assigned to the transition (<b>{0}</b> to <b>{1}</b>).";

$lang["form_next_status_description"]   = "Please select the next status for the object <b>{0}</b>.";

$lang["action_status_change_title"]   = "Response";
$lang["action_status_change_success"]   = "Status change was successful.";
$lang["action_status_change_redirect"]   = "The system needs user input in order to execute the status change.";

$lang["flow_controller_trait_headline_status"]   = "Status change";
$lang["flow_controller_trait_headline_action"]   = "Actions";

$lang["action_skip_transition_title"]   = "Skip status message";
$lang["action_skip_transition_description"] = "";

$lang["flow_condition_and_title"]   = "AND Condition";
$lang["flow_condition_case_title"]   = "Case Condition";
$lang["flow_condition_group_title"]   = "Group Condition";
$lang["flow_condition_invert_title"]   = "Invert Condition";
$lang["flow_condition_or_title"]   = "OR Condition";
$lang["flow_condition_xor_title"]   = "XOR Condition";

$lang['flow_condition_user_forbidden']              = 'Check user permission';
$lang['flow_condition_user_forbidden_title']        = 'Check user permission for status change';
$lang['flow_condition_user_forbidden_description']  = 'A user is not allowed to execute two status changes in a row (principle of dual control)';
$lang['flow_condition_user_forbidden_error']        = 'You have no permission to execute the status change. This step must be performed by a different person.';
