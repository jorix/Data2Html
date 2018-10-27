<!DOCTYPE html>
<?php
    require_once '_start-dev.php';

    $render = Data2Html\Handler::createRender();
    $result = $render->render([], ['html' => '$${require d2h_message}']);
    $lang= 'ca';
?>
<html lang="<?=$lang?>">
<head>
	<meta charset="UTF-8">
	<title>sBox: d2h_message</title>
    
    <script src="../../demo/lang.js.php?lang=<?=$lang?>"></script>
    <?php echo $result->getSource(['base' => '../..', 'lang' => $lang]);?>

    <style>
        button {margin: auto; display:block}
        .gray-box {background: #ccc; width:400px; margin:auto; padding: 5px;}
    </style>
</head>
<body>
    <div class="row">
        <div id="superBox1" class="col-4">
            <br>
            <div>
                <button onclick="d2h_message.clear('#superBox1')">
                    Clear all messages into #superBox1
                </button>
            </div>
            <br>
            <br>
            <div data-d2h-message="#superBox1 div.sub" ></div>
            <div class="sub gray-box">sub div on div#superBox1</div>

            <br>
            <br>
            <div data-d2h-message="#box2"></div>
            <div data-d2h-message="{for:'#box2', position:'bottom-start'}"></div>
            <div id="box2" class="gray-box">
                two messages on #box2
            </div>
            
            <br>
            <br>
            <br>
            <br>
            <div data-d2h-message="#box3"></div>
            <div id="box3" class="gray-box">Warning on #box3</div>
        </div>
        <div id="superBox2" class="col-4" >
            <br>
            <div>
                <button onclick="d2h_message.clear('#superBox2')">
                    Clear all meessages into #superBox2
                </button>
            </div>
            <br>
            <br>
            <div data-d2h-message="#box4"></div>
            <div id="box4" class="gray-box">Danger on #box4</div>
            <br>
            <br>
            <br>
            <div data-d2h-message="#box5"></div>
            <div id="box5" class="gray-box">Info on #box5</div>
        </div>
    </div>
    <script>
        d2h_message.set({messageTime: 0}); // no auto clear
        d2h_message.success('#superBox1', 'Ok on sub div.sub');
        d2h_message.success('#box2', 'Two messages');
        d2h_message.warning('#box3', 'Colors of warning');
        d2h_message.danger('#box4', 'Colors of danger');
        d2h_message.info('#box5', 'Colors of info');
    </script>
</body>
</html>