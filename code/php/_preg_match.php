<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>preg_match</title>
</head>
<div class="container">
<?php
// $obj = array(22+4) ;
// echo '<pre>';
// echo json_encode($obj, JSON_PRETTY_PRINT);
// echo '</pre>';
 //test('/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/', '\'$${s22[s]} $${3[2]} $${s3s}\'');
$v = 'aa[2bib] C2c[2] c3X[c3ci] 33 33ee 4ss[4bib] c[5] c[ wdw3uw ] C .[es] sum(c[eee])';
//test('/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i', $v);

//test('/\$\{([\w\d]+|[\w\d]+\s*\|[\s\w\d\.,;:\|()+-]+)\}/', 'a ${bb} ${3} ${cc |4qc 4:q09.+(-)5,q5} ${dddd|2 (|) a}');

test('/["\']([^"\'\$]*)\$\$\{([\w.:-]+)\}/' , 'a ${bb} "# df hsfh df$${3ee} ${cc |4qc 4');
test('/["\']\$\$\{([\w.:-]+)\}/' , 'a ${bb} "$${3ee} ${cc |4qc 4');
//print_r(getLinkedTo('/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i', $v));

  //test('/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i', ' nom divers');
//     '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i' // $matchLinked = 

// test('/^[a-z]\w*\[([a-z]\w*|\d+)\]$/', 'aaa');
// test('/^[a-z]\w*\[([a-z]\w*|\d+)\]$/', 'aaa[2]');
//test('/\w[\w-]*\s*=\s*\"\$\$\{(\w+)(\|*\w*)\}\"/', 'a d_W-1dd="$${aa}" -a ="$${mi|cu}" ');

   
function test($patern, $value) {
    $matches = null;
    $res = preg_match_all($patern, $value, $matches); // \[(\w+|\d+)\]/', $value, $matches);
    echo "'{$value}' = '{$res}':<pre>";
    print_r($matches);
    echo '</pre><hr>';
}

function getLinkedTo($matchLinked,$baseName)
    {
        $matches = null;
        $linkedTo = array();
        preg_match_all($matchLinked, $baseName, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            // link[name|123] -> linked field
            if ($matches[1][$i] && $matches[2][$i]) {
                array_push($linkedTo, array(
                    'match' => $matches[0][$i],
                    'link' => $matches[1][$i],
                    'baseName'=>$matches[2][$i]
                ));
            }
        } 
        echo "<pre>";
        return $linkedTo;
        echo '</pre><hr>';
    }
?>
</div>
</body>
</html>
    