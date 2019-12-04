<body>
<div class="info">
<h1>PRM Aufbereiter</h1>
Dieses Tool bereitet CSV-Dateien aus dem PRM statistisch auf. Dazu die CSV-Werte in das Input-Feld eingeben und "Umwandeln" drücken. 
</div>

<style type="text/css" media="all">
    .btn:hover {
        background: black;
        color: white;
    }
    body {
        background: #ff8800;
        font-family: "Arial", sans-serif;
        font-weight: 700;
    }
    .btn {
        border-radius: 0.5rem;
        background: white;
        display: block;
        margin: auto;
        margin-top: 1rem;
    }
    .in {
        background: white;
    }
    .info {
        padding: 1rem;
        text-align: center;
        color: white;
    }
	/* Styles for all media */
	#print_helper {
	  display: none;
	}
	.important {
	  color: #330;
	  background: #ffd;
	  border: 2px solid #dd4;
	  padding: 1em;
	  margin: 1em 0;
	}
	.important a:link,
	.important a:visited {
	  color: #591;

	}
</style>
<style type="text/css" media="print">
  /* Styles for print */
	#print_helper {
		display: block;
		overflow: visible;
		font-family: Menlo, "Deja Vu Sans Mono", "Bitstream Vera Sans Mono", Monaco, monospace;
		white-space: pre;
		white-space: pre-wrap;
        
	}
	#the_textarea {
	  display: none;
	}
	#print_placeholder:after {
		content: "The print stylesheet has been applied. ✓";
		display: inline;
        
	}
</style>

<script type="text/javascript" src="jquery.min.js"></script>
<script type="text/javascript">
jQuery(function($){
  function copy_to_print_helper(){
    $('#print_helper').text($('#the_textarea').val());
  }
  $('#the_textarea').bind('keydown keyup keypress cut copy past blur change', function(){
    copy_to_print_helper(); // consider debouncing this to avoid slowdowns!
  });
  copy_to_print_helper(); // on initial page load
});
</script>

<form action="#" method="POST">
CSV INPUT:<br>
<textarea name="csvin" style="width: 100%; min-height: 30vh;background: white;"></textarea>

<button type="submit" class="btn" style="font-size: 150%; padding: 1rem; border: 2px solid black;">UMWANDELN</button>
</form>

<?php
if (isset($_POST['csvin'])) {
    // initialize everything
    $stats = [];
    $stats["members_total"] = 0;
    $stats["members_vote"] = 0;
    $vals =[];

    // parse csv

    $lines = array_filter(explode("\n", trim($_POST['csvin'])));
    $lines[0] = trim(preg_replace('/\s+/', '',$lines[0]));

    $header = explode(",", $lines[0]);
    unset($lines[0]);

    foreach ($lines as $line) {
        $entry = [];
        $i = 0;
        $line = explode(",", trim($line));
        while ($i < sizeof($header)) {
            
            try {
                $entry[$header[$i]] = $line[$i];
                
            } catch (Exception $e) {
                $entry[$header[$i]] = "";
            }

            $i += 1;
        }
        array_push($vals, $entry);
    }

    // calc stats

    

        // enter all the fields for that the amount of members should be calculated => map them to a output-name
        $fields = array("comp_user_lv" => 'foreign_country_members', "comp_user_landkreis" => 'landkreis_members', "terr_caption" => 'gliederung_members', "comp_user_wahlkreisbtw" => 'wk-btw_members', "comp_user_wahlkreisltw" => 'wk-ltw_members', "comp_user_kreis" => 'kreis_members', "comp_user_bezirk" => 'bezirk_members', "comp_user_ov" => 'ov_members');

        foreach ($fields as $field => $value) {
            $stats[$value]=[];
            foreach ($vals as $entry) {
        
                if  (in_array($field, $header)) {
                    if  (array_key_exists($entry[$field], $stats[$value])) {
                        if (array_key_exists("total", $stats[$value][$entry[$field]])) {
                            $stats[$value][$entry[$field]]['total'] += 1;
                        } else {
                            $stats[$value][$entry[$field]]['total'] = 1;
                        }
                        
                    } else {
                        $stats[$value][$entry[$field]]['total'] = 1;
                    }
                } 

                if  (in_array($field, $header) && in_array("comp_user_zstimmberechtigung", $header)) {
                    $entry["comp_user_zstimmberechtigung"] = trim(preg_replace('/\s+/', '',$entry["comp_user_zstimmberechtigung"]));
                    if  (array_key_exists($entry[$field], $stats[$value]) ) {
                        
                        
                        if (array_key_exists("vote", $stats[$value][$entry[$field]])) {
                            if($entry["comp_user_zstimmberechtigung"] == "Ja"){
                                
                                $stats[$value][$entry[$field]]['vote'] += 1;
                            }
                            
                        } else {
                            if($entry["comp_user_zstimmberechtigung"] == "Ja"){
                                $stats[$value][$entry[$field]]['vote'] = 1;
                                
                            } else {
                                $stats[$value][$entry[$field]]['vote'] = 0;
                                
                            }
                        }
                        
                    } 
                }
            }
         

    }

}
$out = "";
if (isset($_POST['csvin'])) {
    // format output
    $out = "Stand: ".date("d.m.y")." \n## Nach Gliederung";

    foreach ($stats['foreign_country_members'] as $key => $val) {
        $name = replace_lv($key);
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {
            $out .= "\n".$name.": ".$val['total']." Mitglieder";
        }
    } 
    foreach ($stats['bezirk_members'] as $key => $val) {
        $name = replace_bz($key);
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {
            $out .= "\n".$name.": ".$val['total']." Mitglieder";
        }
    } 
    foreach ($stats['kreis_members'] as $key => $val) {
        $name = replace_kr($key);
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {
            $out .= "\n".$name.": ".$val['total']." Mitglieder";
        }
    } 

    $out .= "\n## Nach Kreis";

    
    foreach ($stats['landkreis_members'] as $key => $val) {
        $name = $key;
        if ($name == False) {
            continue;
        }
        if (isset($val['vote'])) {
            $out .= "\n".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {
            $out .= "\n".$name.": ".$val['total']." Mitglieder";
        }
    } 

    $out .= "\n## Nach Bundestagswahlkreis";
    ksort($stats['wk-btw_members']);
    ksort($stats['wk-ltw_members']);
    foreach ($stats['wk-btw_members'] as $key => $val) {
        $name = replace_btw($key);
        if ($name == False) {
            continue;
        }
        /*if (isset($val['vote'])) {
            $out .= "\n[".$key."] ".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {*/
            $out .= "\n[".$key."] ".$name.": ".$val['total']." Mitglieder";
        //}
    } 

    $out .= "\n## Nach Landtagsswahlkreis";

    foreach ($stats['wk-ltw_members'] as $key => $val) {
        $name = replace_ltw($key);
        if ($name == False) {
            continue;
        }
        /*if (isset($val['vote'])) {
            $out .= "\n[".$key."] ".$name.": ".$val['total']." Mitglieder, davon ".$val['vote']." (".round(($val['vote']/$val['total'])*100)." %) stimmberechtigt";
        } else {*/
            $out .= "\n[".$key."] ".$name.": ".$val['total']." Mitglieder";
        //}
    } 
}
    ?>

STATISTIK OUTPUT:<br>
    <textarea name="textarea" wrap="wrap" id="the_textarea" style="width:100%;min-height:40vh;background: white;">
<?php echo $out;?>
</textarea>
<div id="print_helper"><?php echo $out;?></div>

<?php

# mapping functions for the names

function replace_lv($short) {
    $map = array(
        "BW" => "Landesverband Baden-Württemberg",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}
function replace_bz($short) {
    $map = array(
        "BW.FR" => "Bezirksverband Freiburg",
        "BW.KA" => "Regierungsbezirk Karlsruhe",
        "BW.S" => "Bezirksverband Stuttgart",
        "BW.TÜ" => "Bezirksverband Südwürttemberg",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

function replace_kr($short) {
    $map = array(
        "BW.KA.BAD" => "Kreisgruppe Mittelbaden",
        "BW.KA.HD" => "Kreisverband Rhein-Neckar/Heidelberg",
        "BW.S.S" => "Kreisverband Stuttgart",
        "BW.TÜ.UL" => "Kreisverband Ulm/Alb-Donaukreis",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

function replace_btw($short) {
    $map = array(
        "258" => "Stuttgart I",
        "259" => "Stuttgart II",
        "260" => "Böblingen",
        "261" => "Esslingen",
        "262" => "Nürtingen",
        "263" => "Göppingen",
        "264" => "Waiblingen",
        "265" => "Ludwigsburg",
        "266" => "Neckar-Zaber",
        "267" => "Heilbronn",
        "268" => "Schwäbisch Hall",
        "269" => "Backnang",
        "270" => "Aalen",
        "271" => "Karlsruhe-Stadt",
        "272" => "Karlsruhe-Land",
        "273" => "Rastatt",
        "274" => "Heidelberg",
        "275" => "Mannheim",
        "276" => "Odenwald",
        "277" => "Rhein-Neckar",
        "278" => "Bruchsal - Schwetzingen",
        "279" => "Pforzheim",
        "280" => "Calw",
        "281" => "Freiburg",
        "282" => "Lörrach - Müllheim",
        "283" => "Emmendingen – Lahr",
        "284" => "Offenburg",
        "285" => "Rottweil – Tuttlingen",
        "286" => "Schwarzwald-Baar",
        "287" => "Konstanz",
        "288" => "Waldshut",
        "289" => "Reutlingen",
        "290" => "Tübingen",
        "291" => "Ulm",
        "292" => "Biberach",
        "293" => "Bodensee",
        "294" => "Ravensburg",
        "295" => "Zollernalb – Sigmaringen",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

function replace_ltw($short) {
    $map = array(
        "1" => "Stuttgart I",
        "2" => "Stuttgart II",
        "3" => "Stuttgart III",
        "4" => "Stuttgart IV",
        "5" => "Böblingen",
        "6" => "Leonberg",
        "7" => "Esslingen",
        "8" => "Kirchheim",
        "9" => "Nürtingen",
        "10" => "Göppingen",
        "11" => "Geislingen",
        "12" => "Ludwigsburg",
        "13" => "Vaihingen",
        "14" => "Bietigheim-Bissingen",
        "15" => "Waiblingen",
        "16" => "Schorndorf",
        "17" => "Backnang",
        "18" => "Heilbronn",
        "19" => "Eppingen",
        "20" => "Neckarsulm",
        "21" => "Hohenlohe",
        "22" => "Schwäbisch Hall",
        "23" => "Main-Tauber",
        "24" => "Heidenheim",
        "25" => "Schwäbisch Gmünd",
        "26" => "Aalen",
        "27" => "Karlsruhe I",
        "28" => "Karlsruhe II",
        "29" => "Bruchsal",
        "30" => "Bretten",
        "31" => "Ettlingen",
        "32" => "Rastatt",
        "33" => "Baden-Baden",
        "34" => "Heidelberg",
        "35" => "Mannheim I",
        "36" => "Mannheim II",
        "37" => "Wiesloch",
        "38" => "Neckar- Odenwald",
        "39" => "Weinheim",
        "40" => "Schwetzingen",
        "41" => "Sinsheim",
        "42" => "Pforzheim",
        "43" => "Calw",
        "44" => "Enz",
        "45" => "Freudenstadt",
        "46" => "Freiburg I",
        "47" => "Freiburg II",
        "48" => "Breisgau",
        "49" => "Emmendingen",
        "50" => "Lahr",
        "51" => "Offenburg",
        "52" => "Kehl",
        "53" => "Rottweil",
        "54" => "Villingen-Schwenningen",
        "55" => "Tuttlingen-Donaueschingen",
        "56" => "Konstanz",
        "57" => "Singen (Hohentwiel)",
        "58" => "Lörrach",
        "59" => "Waldshut",
        "60" => "Reutlingen",
        "61" => "Hechingen-Münsingen",
        "62" => "Tübingen",
        "63" => "Balingen",
        "64" => "Ulm",
        "65" => "Ehingen",
        "66" => "Biberach",
        "67" => "Bodenseekreis",
        "68" => "Wangen (im Allgäu)",
        "69" => "Ravensburg",
        "70" => "Sigmaringen",
        "" => False
    );

    if (isset($map[$short])) {
        return $map[$short];
    } else {
        return $short;
    }
}

    ?>
</body>
