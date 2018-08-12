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

// test($matchTemplate_old, $matchTemplate_v);
// test($matchTemplate_n, $matchTemplate_v);
// test($langTemplate, $langTemplate_v);

$pattern = '/\$\$\{([a-z_][\w\-]*)\?\[\[(.*?)\]\](|:\[\[(.*?)\]\])\}/i';
$text = ' $${data-item?[[yes]]:[[no]]} or $${data-item?[[only-yes]]} ';
// test($pattern, $text);

//test('/[a-z][\w-]*\s*=\s*\"\$\$\{([a-z][\w\-]*)(|\s*\|\s*.*?)\}\"/i',' style="$${a-r}" stole = "$${b-s|f}" stule="$${c2-s | 5 f(rq5)+24}"');

//test('/\$\$\{([a-z][\w\-]*)(|.*?)\}/i',' style="$${a-r}" stole = "$${b-s}" stule="$${c2-s | 5 f(rq5)+24}"');

//test('/["\']#\$\$\{([a-z][\w\-]*)(|.*?)\}/i',' style="#$${a-r}" stole = "$${b-s}" stule="$${c2-s | 5 f(rq5)+24}"');

//test('/<script(.*?)>(.*?)<\/script>/i','<script >if (a<b) if (a>b)</script>');
//test('/\$\$\{require\s+([a-z][\w\-\s,]*?)}/i','<script >$${require plurti, proti}</script>');

// $html = '$${_level-0?[[
// <td class="$${class}">$${body}</td>]]:[[<span>$${body}</span>]]}';
// $html = str_replace("\n","{_n_}", $html);
// test(
    // '/\$\$\{([a-z_][\w\-]*)\?\[\[(.*?)\]\](|:\[\[(.*?)\]\])\}/i',
    // $html
// );

test(
    '/\$\$\{([a-z_][\w\-]*)\s*\=\s*(\w+)\w*\}/i',
    '  $${label-width = 034} '
);
 
function test($pattern, $value) {
    $matches = null;
    preg_match_all($pattern, $value, $matches); // \[(\w+|\d+)\]/', $value, $matches);
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
    