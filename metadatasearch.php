<?php
class metadataSearchHelper {
    public static function getViewDefs($module, $viewName)
    {
        $files = glob('custom/modules/' . $module . '/clients/base/views/' . $viewName . '/' . $viewName . '.php');
        $roleFiles = glob('custom/modules/' . $module . '/clients/base/views/' . $viewName . '/roles/*/' . $viewName . '.php');
        $dropdownFiles = glob('custom/modules/' . $module . '/clients/base/views/' . $viewName .'/dropdowns/*/*/'. $viewName .'.php');
        $files = array_merge($files, $roleFiles, $dropdownFiles);
        if (count($files) < 1) {

            $files = glob('modules/' . $module . '/clients/base/views/' . $viewName . '/' . $viewName . '.php');
        }

        if(($viewName == 'recorddashlet' || $viewName == 'preview') && count($files) < 1) {
            $files = self::getViewDefs($module, 'record');
        }

        return $files;
    }
    public static function searchForField($module, $viewName, $fieldName)
    {
        $locations = self::getViewDefs($module, $viewName);
        forEach($locations as $location) {
            include $location;
            forEach($viewdefs[$module]['base']['view'][$viewName]['panels'] as $panel){
                forEach($panel['fields'] as $field){
                    if($field['name'] === $fieldName){
                        return $field;
                    }
                }
            }
            return false;
        }
    return false;
    }

    public static function getFieldDefProp($module, $field, $prop)
    {
        return $GLOBALS['dictionary'][$module]['fields'][$field][$prop];
    }

    public static function searchFilterForField($module, $fieldName)
    {
        $filterFiles = glob('custom/modules/' . $module . '/clients/*/filters/default/default.php');
        forEach($filterFiles  as $location) {
            include $location;
            if(isset($viewdefs[$module]['base']['filter']['default']['fields'][$fieldName])){
                return true;
            }
            return false;
        }
        return true;
    }
}

if(!defined('sugarEntry'))define('sugarEntry', true);
//We cant include the base vardef files for a module without autoloader processing.
require_once('include/entryPoint.php');
$forecastSettings = Forecast::getSettings();

include 'modules/Opportunities/vardefs.php';
include 'modules/RevenueLineItems/vardefs.php';



//Retrieve the Parameterized SQL

$query = "select count(*) as record_count from forecasts where date_modified > DATE_SUB(now(), INTERVAL 3 MONTH );";

$conn = $GLOBALS['db']->getConnection();
$statement = $conn->Executequery($query);
$countResults = $statement->fetchAll();
$forecastCount = $countResults[0]['record_count'];
forEach(glob('custom/Extension/modules/Opportunities/Ext/Vardefs/*.php') as $filename)
{
    include $filename;
}

forEach(glob('custom/Extension/modules/RevenueLineItems/Ext/Vardefs/*.php') as $filename)
{
    include $filename;
}
global $current_language;

$current_language = $GLOBALS['sugar_config']['default_language'];


$module = 'Opportunities';
$field = 'amount';
$results = [
    'active_forecasts' => [
        'has_commits' => (bool) $forecastSettings['has_commits'],
        'recent_commits' => $forecastCount,
    ],
    'language' => $current_language,
    'opportunities_amount' => [
        'formula' => metadataSearchHelper::getFieldDefProp('Opportunity', $field, 'formula'),
        'field_label' => translate(metadataSearchHelper::getFieldDefProp('Opportunity', $field, 'vname'), $module),
        'record_view' => (bool) metadataSearchHelper::searchForField($module, 'record', $field),
        'record_label' => metadataSearchHelper::searchForField($module, 'record', $field)['label'],
        'record_dashlet' => (bool) metadataSearchHelper::searchForField($module, 'recorddashlet', $field),
        'preview' => (bool) metadataSearchHelper::searchForField($module, 'preview', $field),
        'preview_label' => metadataSearchHelper::searchForField($module, 'preview', $field)['label'],
        'search' => metadataSearchHelper::searchFilterForField($module, $field),
        'list_enabled' => metadataSearchHelper::searchForField($module, 'list', $field)['enabled'],
        'list_default' => metadataSearchHelper::searchForField($module, 'list', $field)['default'],
        'list_label' => metadataSearchHelper::searchForField($module, 'list', $field)['label'],
        'subpanel-for-accounts_enabled' =>  metadataSearchHelper::searchForField($module, 'subpanel-for-accounts-opportunities', $field)['enabled'],
        'subpanel-for-accounts_default' => metadataSearchHelper::searchForField($module, 'subpanel-for-accounts-opportunities', $field)['default'],
        'subpanel-for-accounts_label' => metadataSearchHelper::searchForField($module, 'subpanel-for-accounts-opportunities', $field)['label'],
    ],
];

$module = 'RevenueLineItems';
$field = 'likely_case';
if($forecastSettings['forecast_by'] === $module) {
    $results['rli_likely_case'] = [
        'formula' => metadataSearchHelper::getFieldDefProp('RevenueLineItem', $field, 'formula'),
        'field_label' => translate(metadataSearchHelper::getFieldDefProp('RevenueLineItem', $field, 'vname'), $module),
        'record_view' => (bool)metadataSearchHelper::searchForField($module, 'record', $field),
        'record_label' => translate(metadataSearchHelper::searchForField($module, 'record', $field)['label'], $module),
        'record_dashlet' => (bool)metadataSearchHelper::searchForField($module, 'recorddashlet',
            $field),
        'preview' => (bool)metadataSearchHelper::searchForField($module, 'preview', $field),
        'preview_label' => translate(metadataSearchHelper::searchForField($module, 'preview', $field)['label'], $module),
        'search' => metadataSearchHelper::searchFilterForField($module, $field),
        'list_enabled' => metadataSearchHelper::searchForField($module, 'list', $field)['enabled'],
        'list_enabled_label' => translate(metadataSearchHelper::searchForField($module, 'list', $field)['label'] ,$module),
        'list_default' => metadataSearchHelper::searchForField($module, 'list', $field)['default'],

    ];
}
$rliSubpanel = metadataSearchHelper::searchForField($module,
    'subpanel-for-opportunities-revenuelineitems', $field);
if (!$rliSubpanel) {
    $rliSubpanel = metadataSearchHelper::searchForField($module,
        'subpanel-for-opportunities', $field);
}

$results['rli_likely_case']['subpanel-for-opportunities_enabled'] = $rliSubpanel['enabled'];
$results['rli_likely_case']['subpanel-for-opportunities_default'] = $rliSubpanel['default'];
$results['rli_likely_case']['subpanel-for-opp`ortunities_label'] = translate($rliSubpanel['label'], $module);

return $results;
