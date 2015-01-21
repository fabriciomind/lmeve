<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
include_once("percentage.php");

function dbhrefedit($nr) {
    echo("<a href=\"index.php?id=10&id2=1&nr=$nr\" title=\"Click to open database\">");
}

function towershrefedit($nr) {
    echo("<a href=\"index.php?id=2&id2=2&towerid=$nr\" title=\"Click to view Labs\">");
}

function labshrefedit($nr) {
    echo("<a href=\"index.php?id=2&id2=3&nr=$nr\"  title=\"Click to show kit for this Lab/Array\">");
}

function toonhrefedit($nr) {
    echo("<a href=\"index.php?id=9&id2=6&nr=$nr\" title=\"Click to open character information\">");
}

function outsiderhrefedit($characterName) {
    $cn=rawurlencode($characterName);
    echo("<a href=\"https://gate.eveonline.com/Profile/${cn}\" target=\"_blank\" title=\"Click to open character information\">");
}

function pocohrefedit($nr) {
    echo("<a href=\"index.php?id=2&id2=7&nr=$nr\" >");
}

function getControlTowers($where='TRUE') {
    global $LM_EVEDB;
    $sql="SELECT asl.*,apl.itemName,apl.x,apl.y,apl.z,itp.`typeName`,ssn.`itemName` AS `solarSystemName`,ssm.`itemName` AS `moonName` 
    FROM `apistarbaselist` asl
    JOIN $LM_EVEDB.`invNames` ssn
    ON asl.`locationID`=ssn.`itemID`
    JOIN $LM_EVEDB.`invNames` ssm
    ON asl.`moonID`=ssm.`itemID`
    JOIN $LM_EVEDB.`invTypes` itp
    ON asl.`typeID`=itp.`typeID`
    LEFT JOIN `apilocations` apl
    ON asl.`itemID`=apl.`itemID`
    WHERE $where;";
    //echo("DEBUG: $sql");
    $rawdata=db_asocquery($sql);
    return $rawdata;
}

function getLabs($where='TRUE') {
    global $LM_EVEDB;
    $sql_labs="SELECT apf.*,apl.itemName
    FROM `apifacilities` apf
    JOIN `apilocations` apl
    ON apf.`facilityID`=apl.`itemID`
    WHERE $where
    ORDER BY apl.itemName;";
    //echo("DEBUG: $sql_labs<br/>");
    $rawlabdata=db_asocquery($sql_labs);
    return $rawlabdata;
}

function getLabDetails($facilityID) {
    global $LM_EVEDB;
    $sql="SELECT apf.*,apl.*
    FROM `apifacilities` apf
    JOIN `apilocations` apl
    ON apf.`facilityID`=apl.`itemID`
    WHERE `facilityID`=$facilityID;";
    
    $raw=db_asocquery($sql);
    if (count($raw)>0) {
        $raw=$raw[0];
        $x=$raw['x']; $y=$raw['y']; $z=$raw['z']; 
        $ct=getControlTowers("SQRT(POW($x-apl.x,2)+POW($y-apl.y,2)+POW($z-apl.z,2)) < 30000");
        if (count($ct)>0) {
            //typeName solarSystemName moonName
            $raw['towerTypeName']=$ct[0]['typeName'];
            $raw['solarSystemName']=$ct[0]['solarSystemName'];
            $raw['moonName']=$ct[0]['moonName'];
        } else {
            $raw['towerTypeName']='Unknown';
            $raw['solarSystemName']='Unknown';
            $raw['moonName']='Unknown';
        }
        return $raw;
    } else {
        return false;
    }
}

function getSimpleTasks($where='TRUE') {
    $year=date("Y"); $month=date("m");
    global $LM_EVEDB;
    $sql="SELECT lmt.*,itp.`typeName`,acm.`name` AS characterName FROM `lmtasks` lmt
    JOIN $LM_EVEDB.`invTypes` itp
    ON lmt.`typeID`=itp.`typeID`
    LEFT JOIN `apicorpmembers` acm
    ON lmt.`characterID`=acm.`characterID`
    WHERE ((lmt.`singleton`=1 AND lmt.`taskCreateTimestamp` BETWEEN '${year}-${month}-01' AND LAST_DAY('${year}-${month}-01')) OR (lmt.`singleton`=0))
    AND $where";
    $raw=db_asocquery($sql);
    return($raw);
}

function getLabsAndTasks($corporationID) {
    global $LM_EVEDB;
    $raw_towers=getControlTowers("asl.`corporationID`=$corporationID");
    
    $towers=array();
    $labs=array();
    if (count($raw_towers)>0) {
        $raw_tasks=getSimpleTasks();
        foreach($raw_towers as $tower) {
            //var_dump($tower);
            $x=$tower[x];
            $y=$tower[y];
            $z=$tower[z];
            $raw_labs=getLabs("SQRT(POW($x-apl.x,2)+POW($y-apl.y,2)+POW($z-apl.z,2)) < 30000");
            //var_dump($raw_labs);
            $towers[$tower['itemID']]=$tower;
            foreach($raw_labs as $lab) {
                $towers[$tower['itemID']]['labs'][$lab['facilityID']]=$lab;
                $labs[$lab['facilityID']]['towerID']=$tower['itemID'];
            }
        }
   
        
        foreach($raw_tasks as $task) {
            if (!is_null($task['structureID']) && array_key_exists($task['structureID'], $labs)) {
                $towerID=$labs[$task['structureID']]['towerID'];
                $towers[$towerID]['labs'][$task['structureID']]['users'][$task['characterID']]=$task['characterName'];
                $towers[$towerID]['labs'][$task['structureID']]['products'][$task['typeID']]=$task['typeName'];
            }
        }
        
    }
    //var_dump($towers);
    return $towers;
}

function showLabsAndTasks($towers) {
    $rights_viewallchars=checkrights("Administrator,ViewAllCharacters");
    $rights_editpos=checkrights("Administrator,EditPOS");
    if (count($towers)>0) foreach($towers as $tower) {
        //if (count($tower['labs'])>0) { 
        if (true) { 
        ?>
        <table class="lmframework" style="width: 70%; min-width: 608px;" id="">
            <tr><th colspan="6" style="text-align: center;">
                <?php echo($tower['moonName'].' ("'.$tower['itemName'].'")'); ?>
            </th>
            </tr>
            <tr><th style="width: 32px; min-width: 32px; padding: 0px; text-align: center;">
                Icon
            </th><th style="width: 27%; min-width: 160px;">
                Name
            </th><th style="width: 27%; min-width: 160px;">
                Structure Type
            </th><th style="width: 20%; min-width: 128px;">
                Users
            </th><th style="width: 20%; min-width: 128px;">
                Products
            </th><th style="width: 32px; min-width: 32px; padding: 0px; text-align: center;">
                Kit
            </th>
            </tr>
            <?php
            if (count($tower['labs'])>0) foreach ($tower['labs'] as $facilityID => $row) {
                ?>
                <tr><td width="32" style="padding: 0px; text-align: center;">
                    <?php dbhrefedit($row['typeID']); echo("<img src=\"ccp_img/${row['typeID']}_32.png\" title=\"${row['typeName']}\" />"); echo('</a>'); ?>
                </td><td>
                    <?php 
                    labshrefedit($facilityID); echo(stripslashes($row['itemName'])); echo('</a>');
                     ?>
                </td><td style="">
                    <?php
                    dbhrefedit($row['typeID']); echo(stripslashes($row['typeName']));  echo('</a>');
                     ?>
                </td><td style="">
                    <?php 
                    if (count($row['users'])>0) foreach ($row['users'] as $user => $name) {
                        if ($rights_viewallchars) toonhrefedit($user);
                        echo("<img src=\"https://image.eveonline.com/character/${user}_32.jpg\" title=\"$name\">");
			if ($rights_viewallchars) echo('</a>');
                    }
                    ?>
                </td><td>
                    <?php 
                    if (count($row['products'])>0) foreach ($row['products'] as $product => $name) {
                        dbhrefedit($product);
                        echo("<img src=\"ccp_img/${product}_32.png\" title=\"$name\">");
                        echo('</a>');
                    }
                    ?> 
                </td><td>
                    <?php 
                    labshrefedit($facilityID); echo("<img src=\"ccp_icons/12_64_3.png\" style=\"width: 24px; height: 24px;\" /></span>"); echo('</a>');
                     ?> 
                </td>
                </tr>
                <?php
            }
            ?>
                
            </table><br/>
        <?php
        }
    } else {
        echo("<h3>Corporation does not have any Control Towers.</h3>");
    }
}

function showControlTowers($controltowers) {
    
        if (count($controltowers)>0) {
			?>
		    <table class="lmframework" style="" id="">
			<tr><th style="width: 32px; padding: 0px; text-align: center;">
				Icon
			</th><th style="">
				Name
			</th><th style="">
				Control Tower Type
			</th><th style="min-width: 120px;">
				Location
			</th><th style="width: 64px;">
				State
			</th><th style="width: 110px;">
				Online since
			</th>
			</tr>
			<?php
			foreach ($controltowers as $row) {
            ?>
            <tr><td width="32" style="padding: 0px; text-align: center;">
                <?php towershrefedit($row['itemID']); echo("<img src=\"ccp_img/${row['typeID']}_32.png\" title=\"${row['typeName']}\" />"); echo('</a>'); ?>
            </td><td>
                <?php towershrefedit($row['itemID']);
                echo($row['itemName']); echo('</a>'); ?>
            </td><td>
                <?php towershrefedit($row['itemID']);
                echo($row['typeName']); echo('</a>'); ?>
            </td><td style="">
                <?php towershrefedit($row['itemID']);
                echo($row['moonName']); echo('</a>'); ?> 
            </td><td style="">
                <?php towershrefedit($row['itemID']);
                switch($row['state']) {
                    case 1:
                        echo('anchored');
                        break;
                    case 4:
                        echo('online');
                        break;
                    default:
                        echo('unknown');
                }
                echo('</a>'); ?>
            </td><td>
                <?php towershrefedit($row['itemID']);
                echo($row['onlineTimestamp']); echo('</a>'); ?> 
            </td>
            </tr>
            <?php
            }
            ?>
			</table>
			<?php
        } else {
		echo('<table class="lmframework" style="width: 564px;"><tr><th style="text-align: center;">Corporation doesn\'t have any POSes</th</tr></table>');
        }
        
    
    
}
/*
 * Laurvier II = 40316877 (mapping: invNames)
 * Laurvier II planet typeID=2016 (mapping: invItems)
 * mapDenormalize: itemID typeID groupID solarSystemID constellationID regionID orbitID x y z radius itemName security celestialIndex orbitIndex  * 
 * 
 * Specific PoCo income -> apiwalletjournal column argID1=40316877 and argName1=Laurvier II
 * 
 * apilocations - itemID=1012675032345 "Customs Office (Laurvier II)" 70731768720.1602 -10758809884.6656 47766339694.8543 corporationID=414731375
 * apipocolist - itemID=1012675032345 locationID=30005002 locationName=Laurvier 19 1 1 -10 0 0 0 0.05 0.07 0.1 0.15 corporationID=414731375
 */
/*
CREATE FUNCTION hello (s CHAR(20))
RETURNS CHAR(50) DETERMINISTIC
RETURN CONCAT('Hello, ',s,'!');
 * 
select (pow(:x-x,2)+pow(:y-y,2)+pow(:z-z,2)) distance,itemName,itemID,typeID
from mapDenormalize
where solarsystemid=:solarsystemid
order by distance asc
limit 1
 */
function getPocos($where='TRUE') {
    global $LM_EVEDB;
    //refresh mapDenormalize VIEW for Stored Procedure
    db_uquery("CREATE OR REPLACE VIEW `mapDenormalize` AS SELECT * FROM `$LM_EVEDB`.`mapDenormalize`");
    //do the real select
    $sql="SELECT apo.*, thirtyDayIncome(`planetItemID`) AS `planetIncome`, ina.`itemName` AS `planetName`, ite.`typeID` AS `planetTypeID`, itp.`typeName` AS `planetTypeName`
    FROM 
        (SELECT apo1.*,apl.itemName, findNearest(apl.x, apl.y, apl.z, apo1.solarSystemID) AS `planetItemID`
        FROM `apipocolist` apo1
        LEFT JOIN `apilocations` apl
        ON apo1.`itemID`=apl.`itemID`) AS apo
    LEFT JOIN `$LM_EVEDB`.`invItems` AS ite
    ON apo.`planetItemID`=ite.itemID
    LEFT JOIN `$LM_EVEDB`.`invNames` AS ina
    ON apo.`planetItemID`=ina.itemID
    LEFT JOIN `$LM_EVEDB`.`invTypes` AS itp
    ON ite.`typeID`=itp.`typeID`
    WHERE $where";
    $raw=db_asocquery($sql);
    //echo("<pre>".print_r($raw,TRUE)."</pre>");
    return($raw);
}

function getPocoIncome($corporationID) {
    $year=date("Y"); $month=date("m");
    switch ($month) {
                case 1:
                        $PREVMONTH=12;
                        $PREVYEAR=$year-1;
                break;
                case 12:
                        $PREVMONTH=11;
                        $PREVYEAR=$year;
                break;
                default:
                        $PREVMONTH=$month-1;
                        $PREVYEAR=$year;
    }
    $sql="SELECT SUM(awj.amount) AS amount, 'current' AS month FROM
    apiwalletjournal awj
    JOIN apireftypes art
    ON awj.refTypeID=art.refTypeID
    WHERE awj.date BETWEEN '${year}-${month}-01' AND LAST_DAY('${year}-${month}-01')
    AND awj.corporationID = $corporationID
    AND awj.refTypeID IN (96, 97)
    UNION
    SELECT SUM(awj.amount) AS amount, 'previous' AS month FROM
    apiwalletjournal awj
    JOIN apireftypes art
    ON awj.refTypeID=art.refTypeID
    WHERE awj.date BETWEEN '".sprintf("%04d", $PREVYEAR)."-".sprintf("%02d", $PREVMONTH)."-01' AND LAST_DAY('".sprintf("%04d", $PREVYEAR)."-".sprintf("%02d", $PREVMONTH)."-01')
    AND awj.corporationID = $corporationID
    AND awj.refTypeID IN (96, 97);";
    $poco_raw=db_asocquery($sql);
    return $poco_raw;
}

function getSinglePocoIncome($planetItemID) {
    $year=date("Y"); $month=date("m");
    switch ($month) {
                case 1:
                        $PREVMONTH=12;
                        $PREVYEAR=$year-1;
                break;
                case 12:
                        $PREVMONTH=11;
                        $PREVYEAR=$year;
                break;
                default:
                        $PREVMONTH=$month-1;
                        $PREVYEAR=$year;
    }
    $sql="SELECT SUM(awj.amount) AS amount, 'current' AS month FROM
    apiwalletjournal awj
    JOIN apireftypes art
    ON awj.refTypeID=art.refTypeID
    WHERE awj.date BETWEEN '${year}-${month}-01' AND LAST_DAY('${year}-${month}-01')
    AND awj.`argID1`=$planetItemID
    AND awj.refTypeID IN (96, 97)
    UNION
    SELECT SUM(awj.amount) AS amount, 'previous' AS month FROM
    apiwalletjournal awj
    JOIN apireftypes art
    ON awj.refTypeID=art.refTypeID
    WHERE awj.date BETWEEN '".sprintf("%04d", $PREVYEAR)."-".sprintf("%02d", $PREVMONTH)."-01' AND LAST_DAY('".sprintf("%04d", $PREVYEAR)."-".sprintf("%02d", $PREVMONTH)."-01')
    AND awj.`argID1`=$planetItemID
    AND awj.refTypeID IN (96, 97);";
    $poco_raw=db_asocquery($sql);
    return $poco_raw;
}

function showPocoIncome($raw) {
    $TABWIDTH='1016px';
    $day=date('j'); $days=date('t');
    global $DECIMAL_SEP, $THOUSAND_SEP;
    if (count($raw)==2) {
    ?>
    <table class="lmframework" style="width: <?php echo($TABWIDTH); ?>;" id="income">
        <tr><th>
                Previous month income
        </th><th>
                This month income
        </th>
        </tr>		
        <tr><td style="text-align: center;">
            <?php echo(number_format($raw[1]['amount'], 2, $DECIMAL_SEP, $THOUSAND_SEP)); ?> ISK
        </td><td style="text-align: center;">
            <?php echo(number_format($raw[0]['amount'], 2, $DECIMAL_SEP, $THOUSAND_SEP)); ?> ISK
            (Estimated: <?php echo(number_format($raw[0]['amount']/($day/$days), 2, $DECIMAL_SEP, $THOUSAND_SEP)); ?> ISK)
        </td>
    </table>
    <?php
    }
}

function getPocoClients($planetItemID) {
    $year=date("Y"); $month=date("m");
    $sql="SELECT MAX( date ) AS lastAccess, COUNT( * ) AS timesAccessed, SUM(amount) AS taxPaid, ownerID1 As characterID, ownerName1 AS characterName
FROM `apiwalletjournal`
WHERE `argID1`=$planetItemID
AND `date` BETWEEN '${year}-${month}-01' AND LAST_DAY('${year}-${month}-01')
GROUP BY `ownerID1`
ORDER BY `taxPaid` DESC;";
    return db_asocquery($sql);
}

function showPocoClients($clients) {
    global $DECIMAL_SEP, $THOUSAND_SEP;
    
    if (count($clients)>0) {
        ?>
        <table class="lmframework" id="pococlients">
        <tr><th style="width: 32px; padding: 0px; text-align: center;">

        </th><th style="text-align: center;">
                Character Name
        </th><th style="text-align: center;">
                Tax paid
        </th><th style="text-align: center;">
                Times accessed
        </th><th style="text-align: center;">
                Last access
        </th>
        </tr>
        <?php
        foreach ($clients as $row) {
            echo('<tr><td style="width: 32px; padding: 0px; text-align: center;">');
                outsiderhrefedit($row['characterName']);
                    echo("<img src=\"https://image.eveonline.com/character/${row['characterID']}_32.jpg\" title=\"${row['characterName']}\" />");
                echo('</a>');
            echo('</td><td style="text-align: left;">');
                outsiderhrefedit($row['characterName']);
                    echo($row['characterName']);
                echo('</a>');
            echo('</td><td style="text-align: right;">');
                    echo(number_format($row['taxPaid'], 2, $DECIMAL_SEP, $THOUSAND_SEP).' ISK');
            echo('</td><td style="text-align: center;">');
                    echo($row['timesAccessed']);
            echo('</td><td style="text-align: left;">');
                    echo($row['lastAccess']);
            echo('</td></tr>');
        }
        ?>
        </table>
        <?php
    } else {
        echo('No clients');
    }
}

function showPocos($pocos, $income=null) {
    global $DECIMAL_SEP, $THOUSAND_SEP;
    $TABWIDTH='1016px';
        if (count($pocos)>0) {
            //find max monthly income for percentage scaling
            $maxIncome=0.0;
            foreach ($pocos as $row) {
                if ($row['planetIncome']>$maxIncome) $maxIncome=$row['planetIncome'];
            }
            //display header
			?>
			<table class="lmframework" style="width: <?php echo($TABWIDTH); ?>" id="pocos">
			<tr><th style="width: 64px; padding: 0px; text-align: center;" rowspan="2">
				Icon
			</th><th style="width: 100px; text-align: center;" rowspan="2">
				Location
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Reinforced Hours
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Allow Alliance
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Allow Standings
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Min Standings
			</th><th colspan="7" style="text-align: center;">
				Tax rates
			</th>
			</tr>
			<tr>
			<th style="width: 64px;">
				Alliance
			</th><th style="width: 64px; text-align: center;">
				Corp
			</th><th style="width: 64px; text-align: center;">
				Excellent Standing
			</th><th style="width: 64px; text-align: center;">
				Good Standing
			</th><th style="width: 64px; text-align: center;">
				Neutral Standing
			</th><th style="width: 64px; text-align: center;">
				Bad Standing
			</th><th style="width: 64px; text-align: center;">
				Horrible Standing
			</th>
			</tr>
            <?php
            //walk each PoCo
            foreach ($pocos as $row) {
            ?>
                <tr><td style="padding: 0px; text-align: center;">
                    <?php 
                    echo("<a href=\"?id=10&id2=1&nr=2233\"><img src=\"ccp_img/2233_32.png\" title=\"Customs Office\" /></a>");
                    echo("<a href=\"?id=10&id2=1&nr=".$row['planetTypeID']."\"><img src=\"ccp_img/".$row['planetTypeID']."_32.png\" title=\"".$row['planetTypeName']."\" /></a>");
                    ?>
                    
                </td>
                    <?php

                      $perc=round(100*$row['planetIncome']/$maxIncome);
                      $good=array(0,192,0,0.5);
                      $bad=array(192,0,0,0.5);
                      for ($i=0; $i<4; $i++) {
                          $color[$i] = round ($bad[$i] + ($good[$i]-$bad[$i])*$perc/100);
                          //echo("good[$i]=".$good[$i]." bad[$i]=".$bad[$i]." color[$i]=".$color[$i]."<br/>");
                      }
                      $bar_color='rgba('.$color[0].','.$color[1].','.$color[2].','.$color[3].')';
                      $empty_color='rgba(0,0,0,0.0)';
                      $perc.='%';
                      echo("<td style=\"background: -webkit-gradient(linear, left top, right top, color-stop($perc,$bar_color), color-stop($perc,$empty_color));
                            background: -moz-linear-gradient(left center, $bar_color $perc, $empty_color $perc);
                            background: -o-linear-gradient(left, $bar_color $perc, $empty_color $perc);
                            background: linear-gradient(to right, $bar_color $perc, $empty_color $perc);\">");                  
                      echo('<span title="Income in last 30 days: '.number_format($row['planetIncome'], 2, $DECIMAL_SEP, $THOUSAND_SEP).' ISK">');
                      pocohrefedit($row['planetItemID']);

                          echo($row['planetName']);
                          echo('</a></span>');
                    ?>
                </td><td style="text-align: center;">
                    <?php echo( ($row['reinforceHour']-1) .'-'. ($row['reinforceHour']+1 )); ?> 
                </td><td style="text-align: center;">
                    <?php if ($row['allowAlliance']==0) echo('No'); else echo('Yes'); ?>
                </td><td style="text-align: center;">
                    <?php if ($row['allowStandings']==0) echo('No'); else echo('Yes'); ?> 
                </td><td style="text-align: center;">
                    <?php echo($row['standingLevel']);  ?>
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateAlliance']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateCorp']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingHigh']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingGood']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingNeutral']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingBad']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingHorrible']);  ?>%
                </td>
                </tr>
            <?php
            }
            ?>
			</table>
			<?php
            if (!is_null($income)) showPocoIncome($income);
        } else {
		echo('<table class="lmframework" style="width: '.$TABWIDTH.';"><tr><th style="text-align: center;">Corporation doesn\'t have any POCOs</th</tr></table>');
        }
        
    
}

function getCorp($corporationID) {
    $ret=db_asocquery("SELECT * FROM apicorps WHERE `corporationID`=$corporationID;");
    if (count($ret)>0) {
        $ret=$ret[0];
        return $ret;
    } else {
        return FALSE;
    }
}

function showPocoDetail($pocos,$income=null) {
    global $DECIMAL_SEP, $THOUSAND_SEP;
    $TABWIDTH='1016px';
        if (count($pocos)>0) {
            //find max monthly income for percentage scaling
            $maxIncome=0.0;
            foreach ($pocos as $row) {
                if ($row['planetIncome']>$maxIncome) $maxIncome=$row['planetIncome'];
            }
            //display header
			?>
                        <table class="lmframework" style="width: <?php echo($TABWIDTH); ?>" id="pocos">
			<tr><th style="width: 64px; padding: 0px; text-align: center;">
				Icon
			</th><th style="text-align: center;">
				Location
			</th>
                        <th style="text-align: center;">
				Owner corporation
			</th></tr>
                        <tr><td style="padding: 0px; text-align: center;">
                            <?php 
                            echo("<a href=\"?id=10&id2=1&nr=".$row['planetTypeID']."\"><img src=\"ccp_img/".$row['planetTypeID']."_64.png\" title=\"".$row['planetTypeName']."\" /></a>");
                            ?>
			</td><td style="text-align: center;">
                            <h2><?=$row['planetName']?></h2>
                            <?=$row['planetTypeName']?>
                        </td><td style="text-align: center;">
                            <h2><img src="https://image.eveonline.com/Corporation/<?=$row['corporationID']?>_32.png" style="vertical-align: middle;"> <?php $corp=getCorp($row['corporationID']); echo($corp['corporationName']); ?></h2>
				
			</td></tr>
                        </table>
            
			<table class="lmframework" style="width: <?php echo($TABWIDTH); ?>" id="pocos">
			<tr><th style="width: 64px; text-align: center;" rowspan="2">
				Reinforced Hours
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Allow Alliance
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Allow Standings
			</th><th style="width: 64px; text-align: center;" rowspan="2">
				Min Standings
			</th><th colspan="7" style="text-align: center;">
				Tax rates
			</th>
			</tr>
			<tr>
			<th style="width: 64px;">
				Alliance
			</th><th style="width: 64px; text-align: center;">
				Corp
			</th><th style="width: 64px; text-align: center;">
				Excellent Standing
			</th><th style="width: 64px; text-align: center;">
				Good Standing
			</th><th style="width: 64px; text-align: center;">
				Neutral Standing
			</th><th style="width: 64px; text-align: center;">
				Bad Standing
			</th><th style="width: 64px; text-align: center;">
				Horrible Standing
			</th>
			</tr>
            <?php
            //walk each PoCo
            foreach ($pocos as $row) {
            ?>
                <tr><td style="text-align: center;">
                    <?php echo( ($row['reinforceHour']-1) .'-'. ($row['reinforceHour']+1 )); ?> 
                </td><td style="text-align: center;">
                    <?php if ($row['allowAlliance']==0) echo('No'); else echo('Yes'); ?>
                </td><td style="text-align: center;">
                    <?php if ($row['allowStandings']==0) echo('No'); else echo('Yes'); ?> 
                </td><td style="text-align: center;">
                    <?php echo($row['standingLevel']);  ?>
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateAlliance']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateCorp']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingHigh']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingGood']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingNeutral']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingBad']);  ?>%
                </td><td style="text-align: center;">
                    <?php echo(100 * $row['taxRateStandingHorrible']);  ?>%
                </td>
                </tr>
                <tr>
                    <th colspan="3" style="text-align: center;">Income in the last 30 days</th>
                    <th colspan="3" style="text-align: center;">Previous month income</th>
                    <th colspan="5" style="text-align: center;">Current month income</th>
                </tr><tr>
                    <td colspan="3" style="text-align: center;"><?php echo(number_format($row['planetIncome'], 2, $DECIMAL_SEP, $THOUSAND_SEP)); ?> ISK</td>
                    <td colspan="3" style="text-align: center;">
                        <?php
                            if (!is_null($income)) echo(number_format($income[1]['amount'], 2, $DECIMAL_SEP, $THOUSAND_SEP).' ISK');
                        ?>
                    </td>
                    <td colspan="5" style="text-align: center;">
                        <?php
                            $day=date('j'); $days=date('t');
                            if (!is_null($income)) echo(number_format($income[0]['amount'], 2, $DECIMAL_SEP, $THOUSAND_SEP).' ISK');
                            if (!is_null($income)) echo(' (Estimated: '.number_format($income[0]['amount']/($day/$days), 2, $DECIMAL_SEP, $THOUSAND_SEP).' ISK)');
                        ?>
                    </td>
                </tr>
            <?php
            }
            ?>
	    </table>
	    <?php
            //if (!is_null($income)) showPocoIncome($income);
        } else {
		echo('<table class="lmframework" style="width: '.$TABWIDTH.';"><tr><th style="text-align: center;">Corporation doesn\'t have any POCOs</th</tr></table>');
        }
        
    
}

function getStock($where='TRUE') {
    global $LM_EVEDB;
    $sql="SELECT cfs.*,itp.`typeName`,apa.*,apl.`itemName` AS locationName,app.`max` as price,itp.`groupID`, igp.`groupName` 
        FROM `cfgstock` cfs
        JOIN $LM_EVEDB.`invTypes` itp
        ON cfs.`typeID`=itp.`typeID`
        JOIN $LM_EVEDB.`invGroups` igp
        ON itp.`groupID`=igp.`groupID`
        LEFT JOIN `apiprices` app
        ON cfs.`typeID`=app.`typeID`
        JOIN `apiassets` apa
        ON cfs.`typeID`=apa.`typeID`
        LEFT JOIN $LM_EVEDB.`mapDenormalize` apl
        ON apa.`locationID`=apl.`itemID`
        WHERE $where AND (app.type='buy' OR app.type IS NULL)
        ORDER BY itp.`groupID`, itp.`typeName`;";
    //echo("DEBUG: $sql");
    $rawdata=db_asocquery($sql);
    //Data transformation (rows -> structure)
    $inventory=array();
    foreach ($rawdata as $row) {
        $inventory[$row['groupID']]['groupID']=$row['groupID'];
        $inventory[$row['groupID']]['groupName']=$row['groupName'];
        $inventory[$row['groupID']]['types'][$row['typeID']]['typeID']=$row['typeID'];
        $inventory[$row['groupID']]['types'][$row['typeID']]['typeName']=$row['typeName'];
        $inventory[$row['groupID']]['types'][$row['typeID']]['amount']=$row['amount']; //required amount
        $inventory[$row['groupID']]['types'][$row['typeID']]['quantity']+=$row['quantity']; //actual amount
        if (!empty($row['price'])) {
            $inventory[$row['groupID']]['types'][$row['typeID']]['value']+=$row['price']*$row['quantity']; //value = price * actual amount
            $inventory[$row['groupID']]['types'][$row['typeID']]['price']=$row['price'];
        } else {
            $inventory[$row['groupID']]['types'][$row['typeID']]['value']+=0; //value = price * actual amount
            $inventory[$row['groupID']]['types'][$row['typeID']]['price']=0;
        }
        if (!empty($row['locationID']) && !empty($row['locationName'])) {
            $inventory[$row['groupID']]['types'][$row['typeID']]['locations'][$row['locationID']]['locationID']=$row['locationID']; //location
            $inventory[$row['groupID']]['types'][$row['typeID']]['locations'][$row['locationID']]['locationName']=$row['locationName']; //location name
        }
        //flags in future
    }
    return($inventory);
}

function showStock($inventory, $corpID) {
    global $LM_BUYCALC_SHOWHINTS;
    $LM_HINTGREEN='We need this, and will be happy to buy it.';
    $LM_HINTYELLOW='We *can* buy this, but we would prefer something green instead.';
    $LM_HINTRED='We don\'t need this right now.';
    $LM_HINTGREENIMG='ccp_icons/38_16_183.png';
    $LM_HINTYELLOWIMG='ccp_icons/38_16_167.png';
    $LM_HINTREDIMG='ccp_icons/38_16_151.png';
    $LM_HINTLOW=100;
    $LM_HINTHIGH=200;
    global $DECIMAL_SEP, $THOUSAND_SEP;
    foreach($inventory as $groupID => $group) {
        $subtotal=0;
    ?>
    <table class="lmframework" style="width: 70%; min-width: 595px;" id="inv_group_name_<?php echo($corpID.'_'.$group['groupID']); ?>" title="Click to show/hide items in this group" onclick="div_toggler('inv_group_<?php echo($corpID.'_'.$group['groupID']); ?>')">
        <tr><th style="width: 100%; text-align: center;"><img src="img/plus.gif" style="float: left;"/> <?php echo($group['groupName']); ?></th></tr>
    </table>
    
<div id="inv_group_<?php echo($corpID.'_'.$group['groupID']); ?>" style="display: none;">
    <table class="lmframework" style="width: 70%; min-width: 595px;" id="">
        <script type="text/javascript">rememberToggleDiv('inv_group_<?php echo($corpID.'_'.$group['groupID']); ?>');</script>
        <tr><td style="width: 32px; padding: 0px; text-align: center;">
            Icon
        </td><td style="width: 30%; min-width: 119px;">
            Type Name
        </td><td style="width: 15%; min-width: 90px;"">
            Current Amount
        </td><td style="width: 15%; min-width: 90px;">
            Required Amount
        </td><td style="width: 110px;">
            Percentage
        </td><td style="width: 15%; min-width: 100px;">
            Value
        </td>
        </tr>
        <?php
        foreach ($group['types'] as $typeID => $row) {
            ?>
            <tr><td width="32" style="padding: 0px; text-align: center;">
                <?php dbhrefedit($row['typeID']); echo("<img src=\"ccp_img/${row['typeID']}_32.png\" title=\"${row['typeName']}\" />"); echo('</a>'); ?>
            </td><td>
                <?php dbhrefedit($row['typeID']);
                if (($LM_BUYCALC_SHOWHINTS) && (isset($inventory[$groupID]['types'][$typeID]['amount'])) && (isset($inventory[$groupID]['types'][$typeID]['quantity']))) {
                                        //if we have corresponding typeID with amount and quantity
                                        $amount=$inventory[$groupID]['types'][$typeID]['amount']; //required amount
                                        $quantity=$inventory[$groupID]['types'][$typeID]['quantity']; //actual quantity
                                        if ($amount>0) {
                                            $percent=100*$quantity/$amount;
                                            if ($percent < $LM_HINTLOW) {
                                                echo('<img src="'.$LM_HINTGREENIMG.'" style="display: inline; vertical-align:bottom;  margin: 0 5px;" title="'.$LM_HINTGREEN.'" />');
                                            } else if ($percent < $LM_HINTHIGH) {
                                                echo('<img src="'.$LM_HINTYELLOWIMG.'" style="display: inline; vertical-align:bottom; margin: 0 5px;" title="'.$LM_HINTYELLOW.'" />');
                                            } else {
                                                echo('<img src="'.$LM_HINTREDIMG.'" style="display: inline; vertical-align:bottom; margin: 0 5px;" title="'.$LM_HINTRED.'" />');
                                            }
                                        }
                                    }
                echo($row['typeName']); echo('</a>'); ?>
            </td><td style="text-align: right;">
                <?php dbhrefedit($row['typeID']); echo(number_format($row['quantity'], 0, $DECIMAL_SEP, $THOUSAND_SEP)); echo('</a>'); //actual amount ?> 
            </td><td style="text-align: right;">
                <?php dbhrefedit($row['typeID']); echo(number_format($row['amount'], 0, $DECIMAL_SEP, $THOUSAND_SEP)); echo('</a>'); //required amount ?>
            </td><td><center>
                <?php if ($row['amount'] > 0) {
                        $percent1=round(100*$row['quantity']/$row['amount']);
                      }  else {
                        $percent1=0;
                      }
                      percentbar($percent1,"$percent1 %"); ?>
            </center></td><td style="text-align: right;">
                <?php dbhrefedit($row['typeID']); echo(number_format($row['value'], 2, $DECIMAL_SEP, $THOUSAND_SEP)); $subtotal+=$row['value']; echo('</a>'); ?>
            </td>
            </tr>
            <?php
        }
        ?>
        </table>
</div>
    <table class="lmframework" style="width: 70%; min-width: 595px;" id="group_subtotal_<?php echo($group['groupID']); ?>">
        <tr><td style="width: 32px; min-width: 32px; padding: 0px; text-align: center;">

        </td><td style="width: 30%; min-width: 119px;">
            
        </td><td style="width: 15%; min-width: 90px;"">
            
        </td><td style="width: 15%; min-width: 90px;">
            
        </td><td style="width: 110px; min-width: 100px;">
            
        </td><td style="width: 15%; min-width: 100px; text-align: right;">
            <?php echo(number_format($subtotal, 2, $DECIMAL_SEP, $THOUSAND_SEP)); $total+=$subtotal; ?>
        </td>
        </tr>
    </table>
    <?php
    }
    ?>
    <table class="lmframework" style="width: 70%; min-width: 595px;">
        <tr><th style="width: 75%;">
              Total
        </th><th style="width: 25%; text-align: right;">
             <?php echo(number_format($total, 2, $DECIMAL_SEP, $THOUSAND_SEP)); ?>   
        </th></tr>
    </table>
    <?php
    
}
?>
