<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php header("Cache-Control: no-cache, must-revalidate");  
ini_set('display_errors', 'On');
error_reporting(E_ALL);

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
	
		<script type="text/javascript" src="../dygraph-combined.js"></script>
		<script src="../js/raphael.2.1.0.min.js"></script>
	    <script src="../js/justgage.1.0.1.min.js"></script>
	    <script src="../js/jquery-1.8.1.min.js"></script>
	
		<link href="../css/bootstrap.css" rel="stylesheet"></link>
	    
	<!-- Le styles -->
	    <link href="../css/bootstrap-responsive.css" rel="stylesheet"></link>

	    <!-- Le HTML5 shim, for IE6-8 support of HTML5 elements -->
	    <!--[if lt IE 9]>
	      <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
	    <![endif]-->

	    <!-- Le fav and touch icons -->
	    <link rel="shortcut icon" href="../ico/favicon.ico"></link>
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
  <script>
	var classicData = [];
	function readData() {
		var url = "classicws.php";
		    $.ajax({
		        cache: false,
		        type: "GET",
				async: false,
		        dataType: "text",
		        url: url,
		        success: function(response) {
		            classicData = jQuery.parseJSON(response);
		            
		        }
		    });
	
	}
		
  </script>	
	
  <title>Off-Grid Manager</title>

</head>

<body style="font-family: Arial;border: 0 none;">
	<div class="container-fluid">	  
      <div class="row-fluid"> 
		<div class="span12">
		  <a name="battery"><h2>Midnite Classic</h2></a>
		
          <div class="row-fluid">
            <div class="span8">
				<div id="bvtg" style="width:700px; height:360px;"></div>
            </div><!--/span-->
           <div class="span4">
				<div id="bvtglabel" style="position: relative; left: 20px; top: 150px"></div>
			</div>
		</div>

      <hr>
	      <hr>

		  <div class="row-fluid">	
      <footer>
        <p>&copy; Nogal de las Brujas 2012</p>
      </footer>

    </div><!--/.fluid-container-->	
	
	<?php include '../bootstrapscripts.php' ?>
		
	<script>		
		  var data = [];
		  readData();
		  data.push([new Date(),classicData.BATT_CUR,classicData.BATT_VOLTS]);
		alert(classicData.BATT_VOLTS);
	      //var x = new Date();
		  //readData();
	     // for (var i = 1; i >= 0; i--) {
	     //   var x = new Date(t.getTime() - i * 1000);
	     //  data.push([x, Math.random()]);
	     // }
	
	      var g = new Dygraph(document.getElementById("bvtg"), data,
	                          {
	                            drawPoints: true,
	                            //showRoller: true,
	                            //valueRange: [0.0,40],
	                            labels: ['Time', 'Current',"Volts"]
	                          });
	      window.intervalId = setInterval(function() {
			readData();
	        data.push([new Date(), classicData.BATT_CUR,classicData.BATT_VOLTS]);
	        g.updateOptions( { 'file': data } );
	      }, 1000);
	
	</script>
	
</body>
</html>