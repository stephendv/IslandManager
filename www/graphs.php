<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php header("Cache-Control: no-cache, must-revalidate");  
ini_set('display_errors', 'On');
error_reporting(E_ALL);

include 'sma_db.php';
include 'drawgraph.php';
$result = SMADB::readData();

function writeCommonGraphProperties() {
	echo "legend: 'always',";
	echo "drawPoints: true,";
	echo "labelsSeparateLines: true,";
    echo "labelsDivStyles: { 'textAlign': 'right' },";
	//echo "showRangeSelector: true";
}

?>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <meta http-equiv="content-type" content="text/html; charset=utf-8" />
	
		<script type="text/javascript" src="dygraph-combined.js"></script>
		<script src="js/raphael.2.1.0.min.js"></script>
	    <script src="js/justgage.1.0.1.min.js"></script>
	    
		<link href="css/bootstrap.css" rel="stylesheet"></link>
	    
	<!-- Le styles -->
	    <link href="css/bootstrap-responsive.css" rel="stylesheet"></link>

	    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	    <!--[if lt IE 9]>
	      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	    <![endif]-->

	    <!-- Le fav and touch icons -->
	    <link rel="shortcut icon" href="ico/favicon.ico"></link>
	    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="ico/apple-touch-icon-144-precomposed.png"></link>
	    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="ico/apple-touch-icon-114-precomposed.png"></link>
	    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="ico/apple-touch-icon-72-precomposed.png"></link>
	    <link rel="apple-touch-icon-precomposed" href="ico/apple-touch-icon-57-precomposed.png"></link>
	    
		<style type="text/css">
	      body {
	        padding-top: 60px;
	        padding-bottom: 40px;
	      }
	      .sidebar-nav {
	        padding: 9px 0;
	      }
	      #bordered {
	         border: 1px solid red;
	      }	    
	    </style>
	
  <title>Off-Grid Manager</title>
  
	<?php 
	  mysql_data_seek($result, mysql_num_rows($result)-1);
	  $latest = mysql_fetch_array($result);
	?>

</head>

<body style="font-family: Arial;border: 0 none;">
	
	<?php include 'navbar.php'; ?>
    

    <div class="container-fluid">	  
      <div class="row-fluid"> 
		<div class="span12">
		  <a name="battery"><h2>Battery</h2></a>
          <div class="row-fluid">
            <div class="span4">
              	<table width="300" border=1>
					<tr><td>Charge phase</td><td><?php echo $latest['BatChrgOp'] ?></td></tr>
					<tr><td>Voltage</td><td><?php echo $latest['BatVtg'] ?>V </td></tr>
					<tr><td>Target voltage</td><td><?php echo $latest['BatChrgVtg'] ?>V</td></tr>
					<tr><td>Absorb time remaining</td><td><?php echo $latest['AptTmRmg'] ?></td></tr>
					<tr><td>Charging Current</td><td><?php echo -1*$latest['TotBatCur'] ?>A</td></tr>	
					<tr><td>Temperature</td><td><?php echo $latest['BatTmp'] ?></td></tr>	
					<tr><td>Time to full charge</td><td><?php echo $latest['RmgTmFul'] ?></td></tr>
					<tr><td>Time to EQ charge</td><td><?php echo $latest['RmgTmEqu'] ?></td></tr>
					<tr><td>SoC error</td><td><?php echo $latest['BatSocErr'] ?>%</td></tr>
					<tr><td>State of Health</td><td><?php echo $latest['Soh'] ?>%</td></tr>
				</table>
            </div><!--/span-->
            <div class="span4">
              	<div id="socgauge" style="width:260px; height:260px"></div>	
            </div><!--/span-->           
			<div class="span4">
				<div id="sohgauge" style="width:260px; height:260px"></div>					
            </div><!--/span-->
          </div><!--/row-->
		
		<div class="row-fluid"><br/></div>
		
          <div class="row-fluid">
            <div class="span8">
				<div id="bvtg" style="width:700px; height:360px;"></div>
            </div><!--/span-->
           <div class="span4">
				<div id="bvtglabel" style="position: relative; left: 20px; top: 150px"></div>
			</div>
		</div>
		
		 <div class="row-fluid"><br/></div>
		
		
		<div class="row-fluid">
            <div class="span8">
				<div id="soc" style="width:700px; height:360px;"></div>
            </div><!--/span-->
           <div class="span4">
				<div id="soclabel" style="position: relative; left: 20px; top: 150px"></div>
			</div>
		 </div> <!-- /row -->
			
		 <div class="row-fluid"><br/></div>
			
      
		
      <hr>
		
		<div class="row-fluid"> 
			<div class="span12">
			  <a name="inverter"><h2>Inverter</h2></a>
			
	          <div class="row-fluid">
	            <div class="span4">
	              	<table width="300" border=1>
						<tr><td>Power</td><td><?php echo $latest['InvPwrAt'] ?>kW</td></tr>
						<tr><td>Voltage</td><td><?php echo $latest['InvVtg'] ?>V</td></tr>
						<tr><td>Frequency</td><td><?php echo $latest['InvFrq'] ?>Hz</td></tr>
						<tr><td>External Power</td><td><?php echo $latest['ExtPwrAt'] ?>kW</td></tr>
						<tr><td>External Voltage</td><td><?php echo $latest['ExtVtg'] ?>V</td></tr>
						<tr><td>External Frequency</td><td><?php echo $latest['ExtFrq'] ?>Hz</td></tr>
					</table>
	            </div><!--/span-->  
			</div>
			<div class="row-fluid"><br/></div>
		 
			<div class="row-fluid">
	            <div class="span8">
					<div id="ipower" style="width:700px; height:360px;"></div>
	            </div><!--/span-->
	           <div class="span4">
					<div id="ipowerlabel" style="position: relative; left: 20px; top: 150px"></div>
				</div>     
	        </div><!--/row-->

<div class="row-fluid"><br/></div>

	        <div class="row-fluid">
	            <div class="span4">
					<div id="ivolts" style="width:350px; height:150px;"></div>
	            </div><!--/span-->
	            <div class="span4">
					<div id="ifreq" style="width:350px; height:150px;"></div>
	            </div><!--/span-->   
	        </div>
	
	</div><!--/span-->
	      </div><!--/row-->

	      <hr>

		  <div class="row-fluid">	
      <footer>
        <p>&copy; Nogal de las Brujas 2012</p>
      </footer>

    </div><!--/.fluid-container-->	
	
	<?php include 'bootstrapscripts.php' ?>
		
	<script type="text/javascript">
	var g1 = new JustGage({
	          id: "socgauge", 
	          value: <?php echo $latest['BatSoc'] ?>, 
	          min: 0,
	          max: 100,
	          title: "State of Charge",
	          label: "%",
			  valueFontColor: "#030303",
			  labelFontColor: "#040404",
	          levelColorsGradient: false,
				levelColors: [
				  "#ff0000",
				  "#f9c802",
				  "#a9d70b"
				]
	        });
			
	var graphs = [];
    var data = "";

    data = "Date,Voltage,Charging Current,Charging Power(kW)\n" +
	<?php
	  mysql_data_seek($result,0);
	  $i = 0;
	  while ($line = mysql_fetch_array($result)) {
	    	echo '"'.SMADB::dygraphTimeFormat($line).','.$line['BatVtg'].','.$line['TotBatCur'] * -1 .','.$line['BatVtg'] * $line['TotBatCur'] * -1/1000 .'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	 ?>	

	 graphs[0] = new Dygraph(
	          document.getElementById("bvtg"),
	          data,
	          {
				labelsDiv: 'bvtglabel',
	            title: 'Voltage / Charging Current / Charging Power',
	            ylabel: 'Volts / Amps / kVA',
	            <?php writeCommonGraphProperties() ?>
	          }
	 );
	
	data = "Date,Current,SoC (%)\n" +
	<?php
	  mysql_data_seek($result,0);
	  $i = 0;
	  while ($line = mysql_fetch_array($result)) {
	    	echo '"'.SMADB::dygraphTimeFormat($line).','.$line['TotBatCur']*-1 .','.$line['BatSoc'].'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	 ?>	

	 graphs[0] = new Dygraph(
	          document.getElementById("soc"),
	          data,
	          {
				labelsDiv: 'soclabel',
	            title: 'Charging Current / State of Charge',
	            ylabel: 'Amps / %',
	            <?php writeCommonGraphProperties() ?>
	          }
	 );
		
	data = "Date,Inverter Power (W), Battery Loads (VA)\n" +
	<?php
	  mysql_data_seek($result,0);
	  $i = 0;
	  while ($line = mysql_fetch_array($result)) {
			$batcur = 0;
			if ($line['TotBatCur'] > 0) $batcur = $line['TotBatCur'];
	    	echo '"'.SMADB::dygraphTimeFormat($line).','.$line['InvPwrAt']*1000 .','.$line['BatVtg'] * $batcur .'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	 ?>	
    
	 g4 = new Dygraph(
	          document.getElementById("ipower"),
	          data,
	          {
	            title: 'Inverter Power (W), Battery Loads (VA)',
	            ylabel: 'Watts / VA',
				labelsDiv: 'ipowerlabel',
	            <?php writeCommonGraphProperties() ?>
	          }
	 );
	
	data = "Date,Voltage\n" +
	<?php
	  mysql_data_seek($result,0);
	  $i = 0;
	  while ($line = mysql_fetch_array($result)) {
	    	echo '"'.SMADB::dygraphTimeFormat($line).','. $line['InvVtg'] .'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	 ?>	

	 g4 = new Dygraph(
	          document.getElementById("ivolts"),
	          data,
	          {
	            title: 'Voltage',
	            ylabel: 'Volts',
	            <?php writeCommonGraphProperties() ?>
	          }
	 );	
	
	data = "Date,Frequency\n" +
	<?php
	  mysql_data_seek($result,0);
	  $i = 0;
	  while ($line = mysql_fetch_array($result)) {
	    	echo '"'.SMADB::dygraphTimeFormat($line).','.$line['InvFrq'] .'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	 ?>	

	 g4 = new Dygraph(
	          document.getElementById("ifreq"),
	          data,
	          {
	            title: 'Frequency',
	            ylabel: 'Hertz',
	            <?php writeCommonGraphProperties() ?>
	          }
	 );
	
	</script>
	

</body>
</html>