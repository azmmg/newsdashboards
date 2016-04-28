<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Regio Dashboard</title>
  <style>
    body {padding-left:6px;}
    .list {margin-bottom:12px;}
    .list ul, li {list-style:none;}
    .error {color:red}
  </style>
</head>
<body>
<?php
   // Yesterday's date
   $date = new DateTime(); $date->sub(new DateInterval('P1D'));
?>
<h1>Top 10 Artikel vom <? echo $date->format('j. F Y') ?></h1>
<?php
$all_regions = array(
    'ALWS'   => 'Aarau, Lenzburg, Wyna-Suhre',
    'ZURZ'   => 'Zurzibet',
    'Kanton' => 'Kanton Aargau'
);
$region      = (isset($_GET["region"]) && isset($all_regions[$_GET["region"]])) ? $_GET["region"] : 'ALWS';
$sections    = array(
    "sectionname" => array(
        3 => $all_regions[$region],
        2 => 'Ressort Aargau',
        1 => 'Nordwestschweiz'
    ),
    "sectionfile" => array(
        3 => $region,
        2 => 'Ressort',
        1 => 'NWCH'
    )
);
function saveHtmlFrag($level, $sections, $html)
{
    $section_names = $sections["sectionname"];
    $section_files = $sections["sectionfile"];
    $creation_date = date(DATE_ATOM); // Now
    $today         = substr($creation_date, 0, 10);
    $file_path     = "../data/html/";
    $file_name     = $section_files[$level] . $today . '.html';
    // echo sprintf("<div>%s</div>",$file_name);
    if (file_exists($file_path . $file_name)) {
        //echo "File $file_name already exists";
    } else {
        //echo "File doesn't exist";
        $div_top    = sprintf("<div data-creationdate=\"%s\" class=\"listcontainer level%d\">\n<h2>%s</h2>\n<ul>\n", $creation_date, $level, $section_names[$level]);
        $div_bottom = "</ul>\n</div>\n";
        $html       = $div_top . $html . $div_bottom;
        //echo $html;
        $handle     = fopen($file_path . $file_name, "w");
        fwrite($handle, $html);
        fclose($handle);
    }
    ;
}
;
function readHtmlFrag($frag)
{
    $creation_date = date(DATE_ATOM); // Now
    $today         = substr($creation_date, 0, 10);
    $file_path     = "../data/html/";
    $file_name     = $frag . $today . '.html';
    if (file_exists($file_path . $file_name)) {
        //echo "File $file_name already exists";
        $html = file_get_contents($file_path . $file_name);
        echo $html;
    } else {
        echo "<div class=\"error\">HTML $file_name doesn't exist</div>";
    }
}
;
function topTenList($level, $sections)
{
    $section_names = $sections["sectionname"];
    $section_files = $sections["sectionfile"];
    $div_middle    = '';
    $pattern1      = '/^(.*) - ([^\-]+) - ([^\-]+) - ([^\-]+)/';
    $pattern2      = '/-([0-9]{7,9})$/';
    $pattern3      = '/^(aphone|iphone|atablet|ipad)\./';
    $accumulator   = array();
    $dir           = './';
    $file_name     = sprintf("../data/csv/ALWS4Export - %s.csv", $section_files[$level]);
    if (file_exists($file_name)) {
        $handle = fopen($file_name, "r");
        while (!feof($handle)) {
            $fields = fgetcsv($handle);
            if (preg_match($pattern1, $fields[0], $matches1, PREG_OFFSET_CAPTURE)) {
                $fields[0] = $matches1[1][0];
            }
            ;
            $fields[1] = preg_replace($pattern3, 'www.', $fields[1], -1);
            if (preg_match($pattern2, $fields[1], $matches2, PREG_OFFSET_CAPTURE)) {
                //printf("<li>%s</li>", $matches[1][0]);
                $index = $matches2[1][0];
                if (isset($accumulator[$index])) {
                    if ($accumulator[$index][0] == '(not set)' && $fields[0] != '(not set)') {
                        $accumulator[$index][0] = $fields[0];
                    }
                    ;
                    $accumulator[$index][1] = $fields[1];
                    $accumulator[$index][2] += $fields[2];
                } else {
                    $accumulator[$index] = array(
                        $fields[0],
                        $fields[1],
                        $fields[2]
                    );
                }
                ;
            } else {
                // echo "no match";
            }
        }
        ;
        fclose($handle);
        $counter = 0;
        // sort the array by field containing pageview values
        foreach ($accumulator as $name => $value) {
            $pvs[$name] = $value[2];
        }
        ;
        array_multisort($pvs, SORT_DESC, $accumulator);
        //
        foreach ($accumulator as $name => $value) {
            $div_middle .= sprintf("<li class=\"listitem\" data-articleid=\"%d\"> <a class=\"listlink\" href=\"http://%s\">%s</a> : %s</li>\n", $name, $value[1], $value[0], $value[2]);
            $counter++;
            if ($counter > 9) {
                break;
            }
        }
        saveHtmlFrag($level, $sections, $div_middle);
    } else {
        echo "<div class=\"error\">$file_name doesn't exist</div>";
    }
}

for ($i = 1; $i <= 3; $i++) {
    topTenList($i, $sections);
}
;

for ($j = 3; $j >= 1; $j--) {
    readHtmlFrag($sections["sectionfile"][$j]);
}
;
?>
</body>
</html>
