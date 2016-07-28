<?php

test('/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/', '\'$${s22[s]} $${3[2]} $${s3s}\'');
              test('/([a-z]\w*|.)\[([a-z]\w*|\d+)\]/', ' aa[2bib] c2c[2] c3c[c3ci] 4ss[4bib] c[2] c .[es]');
              test('/([a-z]\w*|.)\[([a-z]\w*|\d+)\]/', 'account_id[1]');
echo preg_match('/^([a-z]\w*|.)\[([a-z]\w*|\d+)\]$/', 'account_id[1]');

function test($patern, $value) {
    $matches = null;
    preg_match($patern, $value, $matches); // \[(\w+|\d+)\]/', $value, $matches);
    echo '<pre>';
    print_r($matches);
    echo '</pre><hr>';
}
            