<?php
abstract class Data2Html {
    protected $db_params;
    protected $root_path;
    protected $id;
    //
    protected $db;
    protected $table;
    protected $title;
    protected $colsDefs = array();

    /**
     * Class constructor, initializes basic properties
     *
     * @param jqGridLoader $loader
     */
    public function __construct($db_params) {
        if(version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }
        // Params
        //----------------
        $this->db_params = $db_params;
        $this->root_path = dirname(__FILE__).DIRECTORY_SEPARATOR;
        $this->id = "d2h_".get_class($this);
        
        // Register autoload
        //------------------
        spl_autoload_register(array($this, 'autoload'));
        
        // Init
        //----------------
        $this->init();
    }

    /**
     * Abstract function for setting fields properties
     *
     * @abstract
     * @return void
     */
    abstract protected function init();

    /**
     * MAIN ACTION (2): Perform operation to change data is any way
     *
     * @param $oper - operation name
     * @return void
     */
    public function oper($oper) {
        $id = $this->input('_id');
        $oper = strval($oper);

        switch($oper)
        {
            case 'add':
                $data = array_intersect_key($this->input, $this->cols);
                $data = $this->operData($data);

                $id = $this->opAdd($data);

                #Not auto increment -> build new_id from data
                if(empty($this->primary_key_auto_increment))
                {
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

                if(is_callable($callback))
                {
                    call_user_func($callback);
                }
                else
                {
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
    public function run() {
        // Open db		
        $db_driver = $this->db_params;
        $db_class = 'Data2Html_Db_'.$db_driver['db_class'];
        $this->db = new $db_class($db_driver);
    }
    
    /**
     * MAIN ACTION (3): Render grid
     *
     * $jq_loader->render('jq_example');
     *
     * @param array $render_data
     * @return string
     */
    protected function addCols($colArray) {
        $this->colsDefs = $colArray;
    }
    public function renderHtmlTable($tpl) {
        $colArray = $this->colsDefs;
        $tBody = "";
        $thead = "";
        $tbody = "";
        $i = 0;      
        foreach ($colArray as $k => $v) {
            $i++;
            $tbody .= "<td>{{$k}}</td>\n";
            if (isset($v['label'])) {                
                $thead .= "<th>{$v['label']}</th>\n";
            } else {
                $thead .= "<th>{$k}</th>\n";
            }
        } 
        return str_replace(
            array('$${_id}', '$${_title}', '$${_thead}', '$${_tbody}'),
            array($this->id, $this->title,     $thead,       $tbody),
            $tpl
        );
    }
    public function render() {
        if(!is_array($render_data))
        {
            throw new jqGrid_Exception_Render('Render data must be an array');
        }

        $this->render_data = $render_data;

        //------------------
        // Basic data
        //------------------

        $data = array();

        $data['extend'] = $this->render_extend;
        $data['suffix'] = $this->renderGridSuffix($render_data);

        //------------------
        // Render ids
        //------------------

        $data['id'] = $this->grid_id . $data['suffix'];
        $data['pager_id'] = $this->grid_id . $data['suffix'] . '_p';

        //-----------------
        // Render colModel
        //-----------------

        foreach($this->cols as $k => $c)
        {
            if(isset($c['unset']) and $c['unset']) continue;

            #Remove internal column properties
            $c = array_diff_key($c, array_flip($this->internal_col_prop));

            $colModel[] = $this->renderColumn($c);
        }

        //-----------------
        // Render options
        //-----------------

        $options = array(
            'colModel' => $colModel,
            'pager' => '#' . $data['pager_id'],
        );

        #URL's
        $options['url'] = $options['editurl'] = $options['cellurl'] = $this->renderGridUrl();

        #Any postData?
        if($post_data = $this->renderPostData())
        {
            $options['postData'] = $post_data;
        }

        $data['options'] = $this->renderOptions(array_merge($this->default['options'], $options, $this->options));

        //-----------------
        // Render navigator
        //-----------------

        if(is_array($this->nav))
        {
            $data['nav'] = $this->renderNav(array_merge($this->default['nav'], $this->nav));
        }

        //------------------
        // Render base html
        //------------------

        $data['html'] = $this->renderHtml($data);

        //-----------------
        // Compile the final string
        //-----------------

        return $this->renderComplete($data);
    }

    /**
     * All `jqGrid_Exception` exceptions comes here
     * Override this method for custom exception handling
     *
     * @param jqGrid_Exception $e
     * @return mixed
     */
    public function catchException(jqGrid_Exception $e) {
        #More output types will be added
        switch($e->getOutputType())
        {
            case 'responseJson':
                $r = array(
                    'error' => 1,
                    'error_msg' => $e->getMessage(),
                    'error_code' => $e->getCode(),
                    'error_data' => $e->getData(),
                    'error_type' => $e->getExceptionType(),
                );

                if($this->Loader->get('debug_output'))
                {
                    $r['error_string'] = (string)$e;
                }
                else
                {
                    if($e instanceof jqGrid_Exception_DB)
                    {
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
     * @return mixed
     */
    public function catchError(Exception $e) {
        #More output types will be added
        $r = array(
            'error' => 1,
            'error_msg' => $e->getMessage(),
            'error_code' => $e->getCode()
        );
        if($this->Loader->get('debug_output')) {
            $r['error_string'] = (string)$e;
        }
        header('HTTP/1.1 500 ' . $r['error_msg']);
        $this->responseJson($r);
        
        return $e;
    }


    //----------------
    // OUTPUT PART
    //----------------

    /**
     * 
     */
    public function getDataArray_Query($query, $pageNumber=0, $pageSize=0) {
        $this->responseJson(
            $this->db->getQueryArray($query, $pageNumber, $pageSize));
    }
    /**
     * Export data using plugin
     */
    protected function outExport() {
        $type = jqGrid_Utils::checkAlphanum($this->input('export'));

        $class = 'jqGrid_Export_' . ucfirst($type);

        if(!class_exists($class))
        {
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
     * Send to browser a JSON from a php object
     * @param  $obj object to send
     */
    protected function responseJson($obj) {
       // echo '<pre>'.Data2Html_Utils::jsonEncode($obj).'</pre>'; return;
        header("Content-type: application/responseJson; charset=utf-8;");
        echo Data2Html_Utils::jsonEncode($obj);
    }
    
    //----------------
    // OPERATIONS PART
    //----------------

    /**
     * (Oper) Insert
     *
     * Please note: this is the only "Oper" function, which must return new row id
     *
     * @param  array $ins - form data
     * @return integer - new_id
     */
    protected function opAdd($values) {
        if(empty($this->table)) {
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
            header('HTTP/1.0 401 ' . $e->getMessage());
            die ($e->getMessage());
        }
        $this->afterInsert($values, $new_id);
        $this->commit();
        
        return $new_id;
    }
    protected function insert($values) {
        return $this->db->insert($this->table, $values, true);
    }
    protected function beforeInsert($values) {
        return true;
    }
    protected function afterInsert($values, $keyArray) {
    }

    /**
     * (Oper) Update
     *
     * @param  integer $id - id to update
     * @param  array $upd - form data
     * @return void
     */
    protected function opEdit($id, $values) {
        if(empty($this->table)) {
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
            header('HTTP/1.0 401 ' . $e->getMessage());
            die ($e->getMessage());
        }
        $this->afterUpdate($values, $keyArray);
        $this->commit();
                
        return '1';
    }
    protected function update($values, $keyArray) {
        $this->db->update($this->table, $values, $keyArray);
    }
    protected function beforeUpdate($values, $keyArray) {
        return true;
    }
    protected function afterUpdate($values, $keyArray) {
    }

    /**
     * (Oper) Delete
     *
     * @param  integer|string $id - one or multiple id's to delete
     * @return void
     */
    protected function opDel($id) {
        if(empty($this->table)) {
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
            header('HTTP/1.0 401 ' . $e->getMessage());
            die ($e->getMessage());
        }
        $this->afterDelete($keyArray);
        $this->commit();

        return '1';
    }
    protected function delete($keyArray) {
        $this->db->delete($this->table, $this->whereSql($keyArray));
    }
    protected function beforeDelete($keyArray) {
        return true;
    }
    protected function afterDelete($keyArray) {
    }

    //----------------
    // Utils
    //----------------
    /**
     * Auto load
     */
    protected function autoload($class) {
        #Not a Data2Html_% class
        error_log("autoload({$class})<br>");
        if (strpos($class, 'Data2Html_') !== 0) {
            return;
        }
        $path = str_replace('_', '/', $class).'.php';
        $phisicalPath = $this->root_path.$path;
        #Do not interfere with other autoloads
        if ( file_exists($phisicalPath) ) {
            require $phisicalPath;
        } else {
            throw new Exception(
                "->autoload({$class}): File \"{$path}\" does not exist");
        }
    }
}
