<?php
$i = 0;
while (++$i < 10) {
    echo "start {$i}<br>";
	switch ($i) {
        case 1:
        case 2: 
            if ($i) {
                echo "continue {$i}<br>";
                continue;
            }
            echo "break {$i}<br>";
            break;
        default:
            break;
    }
    echo "next {$i}<br>";
}

/* 7.3
Warning: "continue" targeting switch is equivalent to "break". Did you mean to use "continue 2"? in C:\_Apache\Aixada\Data2Html_c\test\sandbox\continue.php on line 10
start 1
continue 1
next 1
start 2
continue 2
next 2
start 3
next 3
start 4
next 4
start 5
next 5
start 6
next 6
start 7
next 7
start 8
next 8
start 9
next 9
*/