<?php

/**
 * Download list plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  Fabrik.list.download
 * @copyright   Copyright (C) 2005-2016  Media A-Team, Inc. - All rights reserved.
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 */

defined('_JEXEC') or die('Restricted access');

use Fabrik\Helpers\Image;

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

    protected function getRowsData()
    {
        $model = $this->getModel();
        $table = $model->getGenericTableName();
        $ids = $model->getColumnData("{$table}.id");

        return array_map([$this, 'getRowData'], $ids);
    }

    protected function getRowData($id)
    {
        $db = JFactory::getDbo();
        $columns = $this->getColumnsComparison();
        $data = ['id' => $id];

        foreach ($columns as $column) {
            $column = (object) $column;
            $column->params = (object) $column->params;

            $data[$column->name] = $this->getColumnData($db, $column, $id);
        }

        return $data;
    }

    protected function getColumnData($db, $column, $id)
    {
        switch ($column->plugin) {
            case 'databasejoin':
                return $this->getDatabaseJoinData($db, $column, $id);
            case 'fileupload':
                return $this->getFileUploadData($db, $column, $id);
            case 'user':
                return $this->getUserData($db, $column, $id);
            case 'tags':
                return $this->getTagsData($db, $column, $id);
            case 'date':
                return $this->getDateData($db, $column, $id);
            default:
                return $this->getDefaultData($db, $column, $id);
        }
    }

    protected function getDatabaseJoinData($db, $column, $id)
    {
        $query = $db->getQuery(true);
        $displayType = $column->params->database_join_display_type;

        if (in_array($displayType, ['multilist', 'checkbox'])) {
            $query->select($column->name)
                ->from("{$this->getModel()->getGenericTableName()}_repeat_{$column->name}")
                ->where("parent_id = " . (int) $id);
        } else {
            $query->select($column->name)
                ->from($this->getModel()->getGenericTableName())
                ->where("id = " . (int) $id);
        }

        $db->setQuery($query);
        $result = $db->loadColumn();

        return $this->formatDatabaseJoinResult($db, $column, $result);
    }

    protected function formatDatabaseJoinResult($db, $column, $result)
    {
        $output = "<ul class='fabrikRepeatData'>";
        foreach ($result as $item) {
            $query = $db->getQuery(true)
                ->select($column->params->join_val_column)
                ->from($column->params->join_db_name)
                ->where("id = " . (int) $item);
            $db->setQuery($query);
            $output .= "<li class='badge badge-info multitag'>" . $db->loadResult() . "</li>";
        }
        return $output . "</ul>";
    }

    protected function getFileUploadData($db, $column, $id)
    {
        $table = $this->getModel()->getGenericTableName();
        $query = $db->getQuery(true);

        if ((bool) $column->params->ajax_upload) {
            $query->select("{$column->name}, params")
                ->from("{$table}_repeat_{$column->name}")
                ->where("parent_id = " . (int) $id);
        } else {
            $query->select($column->name)
                ->from($table)
                ->where("id = " . (int) $id);
        }

        $db->setQuery($query);
        $result = $db->loadAssocList();

        return $this->formatFileUploadResult($db, $column, $result, $id);
    }

    protected function formatFileUploadResult($db, $column, $result, $id)
    {
        // Melhorias para tratamento de imagem e PDF no upload
        // Detalhes simplificados para melhor manutenção

        if (!$result) {
            return $this->getDefaultImage($column);
        }

        $file = COM_FABRIK_LIVESITE . $result[0][$column->name];
        $isPdf = pathinfo($file, PATHINFO_EXTENSION) === 'pdf';

        return $isPdf ? $this->getPdfThumbnail($column, $result) : $this->getImageThumbnails($column, $result, $id);
    }

    protected function getDefaultImage($column)
    {
        $default_image = COM_FABRIK_LIVESITE . $column->params->default_image;
        $caption = json_decode($column->params->caption)->caption ?? '';
        return "<a title='{$caption}'><img src='{$default_image}'></a>";
    }

    protected function getPdfThumbnail($column, $result)
    {
        $thumb = COM_FABRIK_LIVESITE . str_replace(".pdf", ".png", $result[0][$column->name]);
        $caption = json_decode($result[0]["params"])->caption;
        return "<a href='{$result[0][$column->name]}' target='_blank' title='{$caption}'><img src='{$thumb}' alt='{$caption}'></a>";
    }

    protected function getImageThumbnails($column, $result, $id)
    {
        $principalImg = '';
        $notPrincipalImg = '';
        foreach ($result as $i => $item) {
            $image = $this->createImageElement($column, $item, $id, $i === 0);
            if ($i === 0) {
                $principalImg = $image;
            } else {
                $notPrincipalImg .= $image;
            }
        }
        return $principalImg . $notPrincipalImg;
    }

    protected function createImageElement($column, $item, $id, $isPrincipal)
    {
        $file = COM_FABRIK_LIVESITE . $item[$column->name];
        $caption = json_decode($item["params"])->caption;
        $style = $isPrincipal ? '' : 'style="display: none"';

        return "<a href='{$file}' target='_blank' data-lightbox='{$column->name}_{$id}' title='{$caption}' {$style}>
                    <img src='{$this->getImageSrc($column,$item)}' alt='{$caption}'></a>";
    }

    protected function getImageSrc($column, $item)
    {
        $crop = COM_FABRIK_LIVESITE . $column->params->fileupload_crop_dir . "/" . basename($item[$column->name]);
        $thumb = COM_FABRIK_LIVESITE . $column->params->thumb_dir . "/" . basename($item[$column->name]);
        $file = COM_FABRIK_LIVESITE . $item[$column->name];

        if ((bool) $column->params->fileupload_crop && JFile::exists($crop)) {
            return $crop;
        }
        if ((bool) $column->params->make_thumbnail && JFile::exists($thumb)) {
            return $thumb;
        }
        return $file;
    }

    protected function getUserData($db, $column, $id)
    {
        $query = $db->getQuery(true)
            ->select($column->name)
            ->from($this->getModel()->getGenericTableName())
            ->where("id = " . (int) $id);
        $db->setQuery($query);
        $userId = $db->loadResult();

        $query = $db->getQuery(true)
            ->select("name")
            ->from("#__users")
            ->where("id = " . (int) $userId);
        $db->setQuery($query);
        return $db->loadResult();
    }

    protected function getTagsData($db, $column, $id)
    {
        $query = $db->getQuery(true)
            ->select($column->name)
            ->from($this->getModel()->getGenericTableName() . "_repeat_{$column->name}")
            ->where("parent_id = " . (int) $id);
        $db->setQuery($query);
        $tags = $db->loadColumn();

        return $this->formatTagsResult($db, $tags);
    }

    protected function formatTagsResult($db, $tags)
    {
        $output = '';
        foreach ($tags as $tag) {
            $query = $db->getQuery(true)
                ->select("title")
                ->from("#__tags")
                ->where("id = " . (int) $tag);
            $db->setQuery($query);
            $output .= $db->loadResult() . "<br>";
        }
        return $output;
    }

    protected function getDateData($db, $column, $id)
    {
        $query = $db->getQuery(true)
            ->select($column->name)
            ->from($this->getModel()->getGenericTableName())
            ->where("id = " . (int) $id);
        $db->setQuery($query);
        $result = $db->loadResult();

        return date("d/m/Y H:i:s", strtotime($result));
    }
    protected function getDefaultData($db, $column, $id)
    {
        // Verifica se o nome da coluna está configurado
        if (empty($column->name)) {
            return null; // Ou pode retornar algum valor padrão, como uma string vazia
        }

        $query = $db->getQuery(true)
            ->select($column->name)
            ->from($this->getModel()->getGenericTableName())
            ->where("id = " . (int) $id);
        $db->setQuery($query);

        try {
            $result = $db->loadResult();
        } catch (Exception $e) {
            // Log the error or handle it gracefully
            return null; // ou outra lógica de fallback
        }

        return $result;
    }

    protected function getColumnsComparison()
    {
        $params = $this->getParams();
        $formModel = $this->getModel()->getFormModel();
        $elementsId = json_decode($params->get("list_comparison_columns"))->comparison_columns;
        $thumbColumnId = $params->get("thumb_column");
        $mainColumn = $params->get('main_column');

        $this->prepareColumnsForComparison($elementsId, $mainColumn, $thumbColumnId);

        $elements = [];
        foreach ($elementsId as $item) {
            $elementFull = $formModel->getElement($item, true)->element;
            $elements[] = $this->createElementObject($elementFull);

            if ($elementFull->id === $mainColumn) {
                $this->main_column = end($elements);
            }
        }

        return $elements;
    }

    protected function prepareColumnsForComparison(&$elementsId, $mainColumn, $thumbColumnId)
    {
        // Inicializa $elementsId como array se estiver null
        if (!is_array($elementsId)) {
            $elementsId = [];
        }

        // Verifique se $mainColumn e $thumbColumnId estão definidos e são válidos
        if (!empty($mainColumn) && !in_array($mainColumn, $elementsId)) {
            array_unshift($elementsId, $mainColumn);
        }

        if (!empty($thumbColumnId)) {
            array_unshift($elementsId, $thumbColumnId);
            $this->hasThumb = true;
        }
    }

    protected function createElementObject($elementFull)
    {
        $element = new stdClass();
        $element->name = $elementFull->name;
        $element->label = $elementFull->label;
        $element->plugin = $elementFull->plugin;
        $element->params = json_decode($elementFull->params);
        return $element;
    }

    public function onLoadJavascriptInstance($args)
    {
        parent::onLoadJavascriptInstance($args);

        $opts = $this->getElementJSOptions();
        $opts->url_site = COM_FABRIK_LIVESITE;
        $opts->table = $this->getModel()->getTable()->db_table_name;
        $opts->columns = $this->getColumnsComparison();
        $opts->main_column = $this->main_column;
        $opts->data = $this->getRowsData();
        $opts->url = COM_FABRIK_LIVESITE;

        $this->jsInstance = "new FbListComparison(" . json_encode($opts) . ")";
        return true;
    }

    public function loadJavascriptClassName_result()
    {
        return 'FbListComparison';
    }
}
