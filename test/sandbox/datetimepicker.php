<!DOCTYPE html>
<?php
    require_once '_start-dev.php';

    $render = Data2Html\Handler::createRender();
    $result = $render->render([], ['html' => '$${require datetimepicker}']);
    $lang= 'ca';
?>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>date_time js checks</title>
   
    <script src="../../demo/lang.js.php?lang=<?=$lang?>"></script>
    <?php echo $result->getSource(['base' => '../..', 'lang' => $lang]);?>
    
</head>
<body>
    <div class="container">
        <h2 class="row">
            <a href="https://github.com/moment/moment" target="_blank">github.com/moment/moment</a>
        </h2>
            <span class="col-sm-2">date</span>:         <span id="date_1"></span><br>
            <span class="col-sm-2">date 'L'</span>:     <span id="date_L"></span> <b>OK</b>!<br> 
            <span class="col-sm-2">date 'L_LT'</span>:   <span id="date_L_LT"></span> <b>OK</b>!<br>
            <span class="col-sm-2">date 'LL'</span>:    <span id="date_LL"></span><br>
            <span class="col-sm-2">date 'LLL'</span>:   <span id="date_LLL"></span><br>
            <script>
                $('#date_1').text(moment().format());
                $('#date_L').text(moment().format('L'));
                $('#date_L_LT').text(moment().format('L LT'));
                $('#date_LL').text(moment().format('LL'));
                $('#date_LLL').text(moment().format('LLL'));
            </script>
        <hr>

        <!-- Time zone is not necessary for now
        <h2 class="row">
            <a href="https://github.com/moment/moment-timezone" target="_blank">github.com/moment/moment-timezone</a>
        </h2>
            <span class="col-sm-3">Browser time-zone</span>:    <span id="date_tz"></span><br>
            <span class="col-sm-3">date tz 'Europa/Madrid'</span>: <span id="date_tzma"></span><br>
            <span class="col-sm-3">date tz 'America/Los_Angeles'</span>: <span id="date_tzla"></span><br>
            <script src="../../external/js-date_time/moment-timezone-0.5.13/builds/moment-timezone-with-data.min.js" ></script>
            <script>
                var zone_name = moment.tz.guess();
                var abbr_zone_name = moment.tz(zone_name).zoneAbbr(); 
                $('#date_tz').text(zone_name + ' - ' + abbr_zone_name);
                $('#date_tzma').text(moment().tz('Europa/Madrid').format());
                $('#date_tzla').text(moment().tz('America/Los_Angeles').format());
            </script>
        <hr>
        -->
        
        <h2 class="row">Usage input-output</h2>
        <span class="col-sm-1">input</span>: <span id="io_0"></span> <b>OK for now!</b> (don't use time zone)<br>
        <span class="col-sm-1">input</span>: <span id="io_1"></span><br>
        <span class="col-sm-1">input</span>: <span id="io_2"></span><br>
        <script>
            var testIo = function(id, text, format) {
                if (format != "") {
                    var d = moment(text, format);
                } else {
                    var d = moment(text);
                }
                $('#' + id).text(
                    text + (format != "" ? ' [' + format + ']': '') + 
                    ' -> ' + d.format()
                );
            }
            testIo("io_0", "2017-02-28T15:00:00", "");
            testIo("io_1", "2017-02-28T15:00:00+03:00", "");
            testIo("io_2", "28-02-2017 15:00:00", "L_LT");
        </script>
        <hr>
        
        <h2 class="row"><a 
            href="https://tempusdominus.github.io/bootstrap-4/Usage/"
            target="_blank">https://tempusdominus.github.io/bootstrap-4/Usage/</a></h2>
        <div class="row">
            <h3>By icon [using lang='ru']:</h3>
            <div class="col-sm-3">
                <div class="form-group">
                    <div class="input-group date" data-target-input="nearest" id="check_input_icon">
                        <input type="text" class="form-control datetimepicker-input" data-target="#check_input_icon">
                        <div class="input-group-append" data-toggle="datetimepicker" data-target="#check_input_icon">
                            <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            <script type="text/javascript">
                $(function () {
                    var $dp = $('#check_input_icon').datetimepicker({locale: 'ru'});
                    $('#check_input_icon input').click(function() {
                        $(this).datetimepicker('toggle');
                        return false;
                    });
                });
            </script>
        </div>
        <div class="row">
            <h3>No Icon (input field only) [using default language of moment.js]:</h3>
            <div class="col-sm-3">
                <input type="text" class="form-control" id="check_input_no_icon" >
            </div>
            <script type="text/javascript">
                $(function () {
                    $('#check_input_no_icon').datetimepicker();
                });
            </script>
        </div>
        <hr>

    </div>
</body>
</html>