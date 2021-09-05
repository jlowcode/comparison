<?php
/**
 * Download list plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.download
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Image;

// Require the abstract plugin class
require_once COM_FABRIK_FRONTEND . '/models/plugin-list.php';



/**
 * Download list plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.download
 * @since       3.0
 */
class PlgFabrik_ListComparison extends PlgFabrik_List
{
	protected $buttonPrefix = 'comparison';
	protected $msg = null;
	protected $hasThumb = false;
	protected $main_column;

	public function button(&$args)
	{
		parent::button($args);

		return true;
	}

	protected function getImageName()
	{
		return 'list';
	}

	protected function buttonLabel()
	{
		return 'Comparar';
	}

	protected function getAclParam()
	{
		return 'comparison_access';
	}

	public function canSelectRows()
	{
		return $this->canUse();
	}

	protected function getRowsData() {
        $model = $this->getModel();
        $table = $model->getGenericTableName();
        $ids = $model->getColumnData($table . '.id');

        $data = array();
        foreach($ids as $id) {
            $data[] = $this->getRowData($id);
        }

        return $data;
    }

    protected function getRowData($id) {
        $listModel = $this->getModel();
	    $table = $listModel->getGenericTableName();
        $columns = $this->getColumnsComparison();
        $db = JFactory::getDbo();
        $data = array();
        $data['id'] = $id;
        foreach($columns as $column) {
            $column = (Object) $column;
            $column->params = (Object) $column->params;

            if ($column->plugin === 'databasejoin') {
                if (($column->params->database_join_display_type === 'multilist') || ($column->params->database_join_display_type === 'checkbox')) {
                    $query = $db->getQuery(true);
                    $query->select($column->name)->from($table . "_repeat_" . $column->name)->where("parent_id = " . (int)$id);
                    $db->setQuery($query);
                    $result = $db->loadColumn();
                }
                else {
                    $query = $db->getQuery(true);
                    $query->select($column->name)->from($table)->where("id = " . (int)$id);
                    $db->setQuery($query);
                    $result = $db->loadColumn();
                }
                $true_result = "<ul class='fabrikRepeatData'>";
                foreach($result as $item) {
                    $query = $db->getQuery(true);
                    $query->select($column->params->join_val_column)->from($column->params->join_db_name)->where("id = " . (int)$item);
                    $db->setQuery($query);
                    $true_result .= "<li class='badge badge-info multitag'>" . $db->loadResult() . "</li>";
                }
                $true_result .= "</ul>";
                $data[$column->name] = $true_result;
            }
            else if ($column->plugin === 'fileupload') {
                if ((bool) $column->params->ajax_upload) {
                    $query = $db->getQuery(true);
                    $query->select($column->name . ", params")->from($table . "_repeat_" . $column->name)->where("parent_id = " . (int)$id);
                    $db->setQuery($query);
                    $result = $db->loadAssocList();
                }
                else {
                    $query = $db->getQuery(true);
                    $query->select($column->name)->from($table)->where("id = " . (int)$id);
                    $db->setQuery($query);
                    $result = $db->loadAssocList();
                }
                $isPdf = false;
                if (end(explode(".", basename($result[0][$column->name]))) === 'pdf') {
                    $isPdf = true;
                }
                $true_result = "";
                if (!$result) {
                    $default_image = COM_FABRIK_LIVESITE . $column->params->default_image;
                    $caption = json_decode($result[0]["params"])->caption;
                    $true_result .= "<a title='{$caption}'>";
                    $true_result .= "<img src='{$default_image}'></a>";
                }
                else {
                    if (!$isPdf) {
                        if ($column->params->fu_show_image_in_table === 3) {
                            $query = $db->getQuery(true);
                            $query->select("main_image")->from($table)->where("id = " . (int)$id);
                            $db->setQuery($query);
                            $principal = json_decode($db->loadResult());
                            $principal = (array) $principal;
                            $principal = $principal[$column->name]->name;
                            foreach ($result as $item) {
                                $crop = COM_FABRIK_LIVESITE . $column->params->fileupload_crop_dir . "/" . basename($item[$column->name]);
                                $thumb = COM_FABRIK_LIVESITE . $column->params->thumb_dir . "/" . basename($item[$column->name]);
                                $file = COM_FABRIK_LIVESITE . $item[$column->name];
                                $caption = json_decode($item["params"])->caption;
                                $not_principal_img = "";
                                if (basename($item[$column->name]) === $principal) {
                                    $principal_img = "<a href='{$file}' target='_blank' data-lightbox='{$column->name}_{$id}' title='{$caption}'>";
                                    if (((bool) $column->params->fileupload_crop) && (JFile::exists($crop))) {
                                        $principal_img .= "<img src='{$crop}' alt='{$caption}'>";
                                    }
                                    else if (((bool) $column->params->make_thumbnail) && (JFile::exists($thumb))) {
                                        $principal_img .= "<img src='{$thumb}' alt='{$caption}'>";
                                    }
                                    else {
                                        $principal_img .= "<img src='{$file}' height='250px' width='300px' alt='{$caption}'>";
                                    }
                                    $principal_img .= "</a>";
                                }
                                else {
                                    $not_principal_img .= "<a href='{$file}' target='_blank' data-lightbox='{$column->name}_{$id}' title='{$caption}' style='display: none'>";
                                    if (((bool) $column->params->fileupload_crop) && (JFile::exists($crop))) {
                                        $not_principal_img .= "<img src='{$crop}' alt='{$caption}'>";
                                    }
                                    else if (((bool) $column->params->make_thumbnail) && (JFile::exists($thumb))) {
                                        $not_principal_img .= "<img src='{$thumb}' alt='{$caption}'>";
                                    }
                                    else {
                                        $not_principal_img .= "<img src='{$file}' height='250px' width='300px' alt='{$caption}'>";
                                    }
                                    $not_principal_img .= "</a>";
                                }
                            }
                            $true_result = $principal_img . $not_principal_img;
                        }
                        else {
                            for ($i=0; $i<count($result); $i++) {
                                $crop = COM_FABRIK_LIVESITE . $column->params->fileupload_crop_dir . "/" . basename($result[$i][$column->name]);
                                $thumb = COM_FABRIK_LIVESITE . $column->params->thumb_dir . "/" . basename($result[$i][$column->name]);
                                $file = COM_FABRIK_LIVESITE . $result[$i][$column->name];
                                $caption = json_decode($result[$i]["params"])->caption;
                                $not_principal_img = "";
                                if ($i === 0) {
                                    $principal_img = "<a href='{$file}' target='_blank' data-lightbox='{$column->name}_{$id}' title='{$caption}'>";
                                    if (((bool) $column->params->fileupload_crop) && (JFile::exists($crop))) {
                                        $principal_img .= "<img src='{$crop}' alt='{$caption}'>";
                                    }
                                    else if (((bool) $column->params->make_thumbnail) && (JFile::exists($thumb))) {
                                        $principal_img .= "<img src='{$thumb}' alt='{$caption}'>";
                                    }
                                    else {
                                        $principal_img .= "<img src='{$file}' height='250px' width='300px' alt='{$caption}'>";
                                    }
                                    $principal_img .= "</a>";
                                }
                                else {
                                    $not_principal_img .= "<a href='{$file}' target='_blank' data-lightbox='{$column->name}_{$id}' title='{$caption}' style='display: none'>";
                                    if (((bool) $column->params->fileupload_crop) && (JFile::exists($crop))) {
                                        $not_principal_img .= "<img src='{$crop}' alt='{$caption}'>";
                                    }
                                    else if (((bool) $column->params->make_thumbnail) && (JFile::exists($thumb))) {
                                        $not_principal_img .= "<img src='{$thumb}' alt='{$caption}'>";
                                    }
                                    else {
                                        $not_principal_img .= "<img src='{$file}' height='250px' width='300px' alt='{$caption}'>";
                                    }
                                    $not_principal_img .= "</a>";
                                }
                            }
                            $true_result = $principal_img . $not_principal_img;
                        }
                    }
                    else {
                        $thumb = COM_FABRIK_LIVESITE . $column->params->thumb_dir . "/" . basename($result[0][$column->name]);
                        $thumb = str_replace(".pdf", ".png", $thumb);
                        $file = COM_FABRIK_LIVESITE . $result[0][$column->name];
                        $caption = json_decode($result[0]["params"])->caption;
                        $true_result .= "<a href='{$file}' target='_blank' title='{$caption}'>";
                        $true_result .= "<img src='{$thumb}' alt='{$caption}'></a>";
                    }
                }
                $data[$column->name] = $true_result;
            }
            else if ($column->plugin === 'user') {
                $query = $db->getQuery(true);
                $query->select($column->name)->from($table)->where("id = " . (int)$id);
                $db->setQuery($query);
                $id_user = $db->loadResult();
                $query = $db->getQuery(true);
                $query->select("name")->from("#__users")->where("id = " . (int)$id_user);
                $db->setQuery($query);
                $data[$column->name] = $db->loadResult();
            }
            else if ($column->plugin === 'tags') {
                $query = $db->getQuery(true);
                $query->select($column->name)->from($table . "_repeat_" . $column->name)->where("parent_id = " . (int)$id);
                $db->setQuery($query);
                $result = $db->loadColumn();
                $true_result = '';
                foreach($result as $tag) {
                    $query = $db->getQuery(true);
                    $query->select("title")->from("#__tags")->where("id = " . (int)$tag);
                    $db->setQuery($query);
                    $true_result .= $db->loadResult() . "<br>";
                }
                $data[$column->name] = $true_result;
            }
            else if ($column->plugin === 'date') {
                $query = $db->getQuery(true);
                $query->select($column->name)->from($table)->where("id = " . (int)$id);
                $db->setQuery($query);
                $result = $db->loadResult();
                $data[$column->name] = date("d/m/Y H:i:s", strtotime($result));
            }
            else {
                $query = $db->getQuery(true);
                $query->select($column->name)->from($table)->where("id = " . (int)$id);
                $db->setQuery($query);
                $result = $db->loadResult();
                $data[$column->name] = $result;
            }
        }

        return $data;
    }

    protected function getColumnsComparison()
    {
        $params = $this->getParams();
        $listModel = $this->getModel();
        $formModel = $this->getModel()->getFormModel();
        $elements_id = json_decode($params->get("list_comparison_columns"))->comparison_columns;
        $thumb_column_id = $params->get("thumb_column");
        $main_column = $params->get('main_column');

        if (!in_array($main_column, $elements_id)) {
            array_unshift($elements_id, $main_column);
        }
        if ($thumb_column_id) {
            array_unshift($elements_id, $thumb_column_id);
            $this->hasThumb = true;
        }

        $elements = array();
        foreach ($elements_id as $item) {
            $element_full = $formModel->getElement($item, true)->element;
            $element = new stdClass();
            //$element->id = $element_full->id;
            $element->name = $element_full->name;
            $element->label = $element_full->label;
            $element->plugin = $element_full->plugin;
            $element->params = json_decode($element_full->params);

            $elements[] = $element;

            if ($element_full->id === $main_column) {
                $this->main_column = $element;
            }
        }

        return $elements;
    }

	public function onLoadJavascriptInstance($args)
	{
		parent::onLoadJavascriptInstance($args);

		$opts             = $this->getElementJSOptions();
		$opts->url_site = COM_FABRIK_LIVESITE;
        $opts->table = $this->getModel()->getTable()->db_table_name;
        $opts->columns = $this->getColumnsComparison();
        $opts->main_column = $this->main_column;
        $opts->data = $this->getRowsData();
        $opts->url = COM_FABRIK_LIVESITE;
        //echo '<pre>', var_dump($opts->columns), '</pre>';
        //exit();

		$opts             = json_encode($opts);
		$this->jsInstance = "new FbListComparison($opts)";

		return true;
	}
	
	/**
	 * Load the AMD module class name
	 *
	 * @return string
	 */
	public function loadJavascriptClassName_result()
	{
		return 'FbListComparison';
	}
}
