<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>preg_match</title>
</head>
<div class="container">
<?php
$matchLinked = '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i';
        // fields as: link_name[field_name]
$matchTemplate_old = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';

$matchTemplate_n = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\]|[a-z][\w\-]*)\}/i';
$matchTemplate_v = '$${Base_namE} or $${link_name[field_name]} $${tow-word}';

$langTemplate = '/__\{([a-z][\w\-\/]*)\}/i';
$langTemplate_v = 'all text __{tow-word},__{OneWord},__{casa/word}';

test($matchTemplate_old, $matchTemplate_v);
test($matchTemplate_n, $matchTemplate_v);
test($langTemplate, $langTemplate_v);

 
function test($patern, $value) {
    $matches = null;
    preg_match_all($patern, $value, $matches); // \[(\w+|\d+)\]/', $value, $matches);
    echo "'{$value}' :<pre>";
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
    