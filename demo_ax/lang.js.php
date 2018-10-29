<?php
    require_once '_start.php';
    \Data2Html\Lang::responseJs(
        \Data2Html\Data\Lot::getItem('lang', $_GET, 'ca')
    );
