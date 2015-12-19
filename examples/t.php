<?php 

$a = array(
                'id' => array(
                    'type' => 'number',
                    'display' => 'hidden'
                ),
                "operator" => array(
                    "label" => 'ooo'),
                "description" => array(),
                "method" => array(),
                "quantity" => array(),
                'sss',
                "quantity2" => array(),
                array('label' => 'data00'),
                array('label' => 'data0'),
                'ts' => array(
                    'label' => 'data',
                    'type' => 'date'
                ),
            );
            
            $i = 0;
            foreach ($a as $k => $v) {
                $i++;
                echo "{$i} {$k} <br>";
            } 
            $i = 0;
function test_print($elemento2, $k){
    global $i;
    $i++;
    echo "test_print({$i} {$k} <br>";
}

echo "Antes ...:\n";
array_walk($a, 'test_print');