<?php

abstract class Data2Html
{
    protected $db_params;
    protected $root_path;
    protected $id;
    //
    protected $db;
    protected $table;
    protected $title;
    protected $colsDefs = array();
    protected $filterDefs = array();

    /**
     * Class constructor, initializes basic properties.
     *
     * @param jqGridLoader $loader
     */
    public function __construct($db_params)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }
        // Params
        //----------------
        $this->db_params = $db_params;
        $this->root_path = dirname(__FILE__).DIRECTORY_SEPARATOR;
        $this->id = 'd2h_'.get_class($this);

        // Register autoload
        //------------------
        spl_autoload_register(array($this, 'autoload'));

        // Init
        //----------------
        $this->init();
    }

    /**
     * Abstract function for setting fields properties.
     *
     * @abstract
     */
    abstract protected function init();

    /**
     * MAIN ACTION (2): Perform operation to change data is any way.
     *
     * @param $oper - operation name
     */
    public function run()
    {
        // Open db		
        $db_driver = $this->db_params;
        $db_class = 'Data2Html_Db_'.$db_driver['db_class'];
        $this->db = new $db_class($db_driver);
        $serverMethod = $_SERVER['REQUEST_METHOD'];
        switch ($serverMethod) {
        case 'GET': $the_request = &$_GET; break;
        case 'POST': $the_request = &$_POST; break;
        default:
            throw new Exception("Server method {$serverMethod} is not supported.");
        }
        $this->oper($the_request);
    }
    protected function oper($request)
    {
        $r = new Data2Html_Values($request);
        $oper = $r->getString('oper', 'list');
        switch ($oper) {
            case '':
            case 'list':
                $pageNumber = $r->getInteger('pageNumber', 1);
                $pageSize = $r->getInteger('pageSize', 12);
                $orderBy = $r->getString('orderBy');
                $query = "select * from {$this->table}";
                $where = '';
                foreach ($this->filterDefs as $v) {
                    $where .= " and {$v['name']} = {$v['value']}";
                }
                if ($where !== '') {
                    $query .= ' where '.substr($where, 5);
                }
                if ($orderBy) {
                    $query .= " order by {$orderBy}";
                }
                $this->responseJson(
                    $this->db->getQueryArray($query, $this->colsDefs,
                        $pageNumber, $pageSize)
                );

                return;
            case 'add':
                $data = array_intersect_key($this->input, $this->cols);
                $data = $this->operData($data);

                $id = $this->opAdd($data);

                #Not auto increment -> build new_id from data
                if (empty($this->primary_key_auto_increment)) {
                    $id = $this->implodePrimaryKey($data);
                }

                $this->response['new_id'] = $id;

                $this->operAfterAddEdit($id);
                break;

            case 'edit':
                $data = array_intersect_key($this->input, $this->cols);
                $data = $this->operData($data);

                $this->opEdit($id, $data);

                $this->operAfterAddEdit($id);
                break;

            case 'del':
                $this->opDel($id);
                break;

            default:
                $callback = array($this, jqGrid_Utils::uscore2camel('op', $oper));

                if (is_callable($callback)) {
                    call_user_func($callback);
                } else {
                    throw new jqGrid_Exception("Oper $oper is not defined");
                }
                break;
        }

        $this->response = array_merge(array('success' => 1), $this->response);

        $this->operComplete($oper);

        //----------------
        // Output result
        //----------------

        $this->responseJson($this->response);
    }

    /**
     * MAIN ACTION (3): Render grid.
     *
     * $jq_loader->render('jq_example');
     *
     * @param array $render_data
     *
     * @return string
     */
    protected function setCols($colArray)
    {
        $this->colsDefs = $colArray;
    }
    protected function setFilter($filterArray)
    {
        $this->filterDefs = $filterArray;
    }
    public function renderAngularTable($templatePath)
    {
        if (substr($templatePath, -1, 1) !== '/') {
            $templatePath .= '/';
        }
        $tpl = file_get_contents($templatePath.'table_div.html');
        $th_sortable = file_get_contents($templatePath.'th_sortable.html');
        $colArray = $this->colsDefs;
        $thead = '';
        $tbody = '';
        $colCount = 0;
        $i = 0;
        $_v = new Data2Html_Values();
        foreach ($colArray as $k => $v) {
            ++$i;
            $_v->set($v);
            $label = $_v->getString('label', $k);
            $thead .= str_replace(
                array('$${name}', '$${label}'),
                array($k, $_v->getString('label', $k)),
                $th_sortable
            );
            $type = $_v->getString('type');
            ++$colCount;
            $tbody .= '<td';
            $class = '';
            $ngClass = '';
            switch ($type) {
                case 'integer':
                case 'number':
                case 'currency':
                    $class .= 'text-right';
                }
            if ($visual = $_v->getString('visualClass')) {
                if (strpos($visual, ':') !== false) {
                    $ngClass = '{'.str_replace(':', ":item.{$k}", $visual).'}';
                } else {
                    $class .= ' '.$visual;
                }
            }
            if ($ngClass) {
                $tbody .= " ng-class=\"{$ngClass}\"";
            }
            if ($class) {
                $tbody .= " class=\"{$class}\"";
            }
            $tbody .= '>';
            if ($type && $format = $_v->getString('format')) {
                $tbody .= "{{item.{$k} | {$type}:'{$format}'}}";
            } elseif ($type === 'currency') {
                $tbody .= "{{item.{$k} | {$type}}}";
            } else {
                $tbody .= "{{item.{$k}}}";
            }
            $tbody .= "</td>\n";
        }

        return str_replace(
            array('$${id}', '$${title}', '$${thead}', '$${tbody}', '$${colCount}'),
            array($this->id, $this->title, $thead, $tbody, $colCount),
            $tpl
        );
    }
    public function renderHtmlTable($tpl)
    {
        $colArray = $this->colsDefs;
        $tBody = '';
        $thead = '';
        $tbody = '';
        $i = 0;
        foreach ($colArray as $k => $v) {
            ++$i;
            $tbody .= "<td>{{$k}}</td>\n";
            if (isset($v['label'])) {
                $thead .= "<th>{$v['label']}</th>\n";
            } else {
                $thead .= "<th>{$k}</th>\n";
            }
        }

        return str_replace(
            array('$${_id}', '$${_title}', '$${_thead}', '$${_tbody}'),
            array($this->id, $this->title,
                        '<tr>'.$thead.'</tr>',
                        '<tr>'.$tbody.'</tr>', ),
            $tpl
        );
    }
    /**
     * All `jqGrid_Exception` exceptions comes here
     * Override this method for custom exception handling.
     *
     * @param jqGrid_Exception $e
     *
     * @return mixed
     */
    public function catchException(jqGrid_Exception $e)
    {
        #More output types will be added
        switch ($e->getOutputType()) {
            case 'responseJson':
                $r = array(
                    'error' => 1,
                    'error_msg' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_data' => $e->getData(),
                    'error_type' => $e->getExceptionType(),
                );

                if ($this->Loader->get('debug_output')) {
                    $r['error_string'] = (string) $e;
                } else {
                    if ($e instanceof jqGrid_Exception_DB) {
                        unset($r['error_data']['query']);
                    }
                }

                $this->responseJson($r);
                break;

            case 'trigger_error':
                trigger_error($e->getMessage(), E_USER_ERROR);
                break;
        }

        return $e;
    }
    /**
     * All exceptions except `jqGrid_Exception` comes here, it is unexpected 
     *      failure so causing send a 500 HTTP error.
     *
     * Override this method for custom exception handling.
     *
     * @param Exception $e
     *
     * @return mixed
     */
    public function catchError(Exception $e)
    {
        #More output types will be added
        $r = array(
            'error' => 1,
            'error_msg' => $e->getMessage(),
            'error_code' => $e->getCode(),
        );
        if ($this->Loader->get('debug_output')) {
            $r['error_string'] = (string) $e;
        }
        header('HTTP/1.1 500 '.$r['error_msg']);
        $this->responseJson($r);

        return $e;
    }

    //----------------
    // OUTPUT PART
    //----------------
    /**
     * Export data using plugin.
     */
    protected function outExport()
    {
        $type = jqGrid_Utils::checkAlphanum($this->input('export'));

        $class = 'jqGrid_Export_'.ucfirst($type);

        if (!class_exists($class)) {
            throw new jqGrid_Exception("Export type $type does not exist");
        }

        #Weird >__<
        $lib = new $class($this->Loader);
        $this->setExportData($lib);
        $lib->doExport();
    }

    //----------------
    // HELPER PART
    //----------------

    /**
     * Send to browser a JSON from a php object.
     *
     * @param  $obj object to send
     */
    protected function responseJson($obj)
    {
        // echo '<pre>'.Data2Html_Utils::jsonEncode($obj).'</pre>'; return;
        header('Content-type: application/responseJson; charset=utf-8;');
        echo json_encode($obj);
    }

    //----------------
    // OPERATIONS PART
    //----------------

    /**
     * (Oper) Insert.
     *
     * Please note: this is the only "Oper" function, which must return new row id
     *
     * @param array $ins - form data
     *
     * @return int - new_id
     */
    protected function opAdd($values)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }
        if ($this->beforeInsert($values) === false) {
            exit;
        }
        // Transaction
        $this->startTransaction();
        try {
            $new_id = $this->insert($values);
        } catch (Exception $e) {
            $this->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->afterInsert($values, $new_id);
        $this->commit();

        return $new_id;
    }
    protected function insert($values)
    {
        return $this->db->insert($this->table, $values, true);
    }
    protected function beforeInsert($values)
    {
        return true;
    }
    protected function afterInsert($values, $keyArray)
    {
    }

    /**
     * (Oper) Update.
     *
     * @param int   $id  - id to update
     * @param array $upd - form data
     */
    protected function opEdit($id, $values)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined', $this->table);
        }

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->beforeUpdate($values, $keyArray) === false) {
            exit;
        }
        // Transaction
        $this->startTransaction();
        try {
            $this->update($values, $keyArray);
        } catch (Exception $e) {
            $this->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->afterUpdate($values, $keyArray);
        $this->commit();

        return '1';
    }
    protected function update($values, $keyArray)
    {
        $this->db->update($this->table, $values, $keyArray);
    }
    protected function beforeUpdate($values, $keyArray)
    {
        return true;
    }
    protected function afterUpdate($values, $keyArray)
    {
    }

    /**
     * (Oper) Delete.
     *
     * @param int|string $id - one or multiple id's to delete
     */
    protected function opDel($id)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->beforeDelete($keyArray) === false) {
            exit;
        }
        // Transaction
        $this->startTransaction();
        try {
            $this->delete($keyArray);
        } catch (Exception $e) {
            $this->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->afterDelete($keyArray);
        $this->commit();

        return '1';
    }
    protected function delete($keyArray)
    {
        $this->db->delete($this->table, $this->whereSql($keyArray));
    }
    protected function beforeDelete($keyArray)
    {
        return true;
    }
    protected function afterDelete($keyArray)
    {
    }

    //----------------
    // Utils
    //----------------
    /**
     * Auto load.
     */
    protected function autoload($class)
    {
        #Not a Data2Html_% class
        error_log("autoload({$class})<br>");
        if (strpos($class, 'Data2Html_') !== 0) {
            return;
        }
        $path = str_replace('_', '/', $class).'.php';
        $phisicalPath = $this->root_path.$path;
        #Do not interfere with other autoloads
        if (file_exists($phisicalPath)) {
            require $phisicalPath;
        } else {
            throw new Exception(
                "->autoload({$class}): File \"{$path}\" does not exist");
        }
    }
}
