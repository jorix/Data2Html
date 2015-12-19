<?php
require_once("../code/php/Data2Html.php");
class aixada_account extends Data2Html {

    protected function init() {
        #Set database table
        $this->table = 'aixada_account';
        $this->title = 'Diners';

        #Set columns
        $this->addCols(
            array(
                'id' => array(
                    'type' => 'number',
                    'display' => 'hidden'
                ),
                "operator" => array(
                    "label" => 'operat'
                ),
                "description" => array(),
                "method" => array(),
                "quantity" => array(),
                "quantity" => array(),
                'ts' => array(
                    'label' => 'data',
                    'type' => 'date'
                ),
            )
        );
    }
    /*
    
    <td>{ts}</td>
					<td>{operator}</td>
					<td>{description}</td>
					<td>{method}</td>
					<td class="textAlignRight formatQty">{quantity}</td>
					<td class="textAlignRight formatQty">{balance}</td>
    
    
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
