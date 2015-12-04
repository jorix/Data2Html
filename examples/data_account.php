"cols": ["id", "ts", "quantity", "description", "account", "method", "currency", "operator", "balance"],
<?php
require_once("../code/php/data2html.php");
class aixada_account extends data2html {

    protected function init() {

        #Set database table
        $this->table = 'aixada_account';
        $this->title = 'Diners';
        
        #Set columns
        $this->addCols( array(
            'id' => array(
				'type' => 'number',
				'display' => 'hidden'
            ),
            array('name' => 'ts', 'label' => 'data',
                'type' => 'date'
            ),
            array('name' => 'id',
				'type' => 'number(0,2)'
			),
			array('name' => "description"),
			
        ) );

        #Set nav
        /*
        $this->nav = array(
            'add' => true, 'edit' => true, 'del' => true
        );
        */
    }
    /*
    */
    protected function beforeInsert($values) {
        throw new Exception('Tablxxxxxxxxxxxxxe is not defined');
    }
    protected function beforeUpdate($values, $iid) {
        throw new jqGrid_Exception('Tablxxxxxxxxxxfffffxxxe is not defined');
    }
    protected function beforeDelete($keyArray) {
        global $Text;
        return $this->checkRelated(
            "select id from aixada_product where
                unit_measure_order_id = {id} or
                unit_measure_shop_id = {id}",
            $keyArray,
            $Text['nav_mng_products']
        );
    }
}
$a = new aixada_account('');
