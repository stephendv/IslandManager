<?php
	
	function drawGraph ($result,$header,$label,$unit,$key,$div) {
		$graphName = "g".$div;	
		echo "data = \"Date,".$label."\\n\" +";
		$labeldiv = $div . "label";	
		mysql_data_seek($result,0);
		$i = 0;
		while ($line = mysql_fetch_array($result)) {
			echo '"'.SMADB::dygraphTimeFormat($line).','.$line[$key].'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}

		echo <<<EOF
			$graphName = new Dygraph(
		document.getElementById('$div'),
		data,
		{
			labelsDiv: '$labeldiv',
			title: '$header',
			ylabel: '$unit',
			legend: 'always',
			drawPoints: true,
			labelsDivStyles: { 'textAlign': 'right' },
			showRangeSelector: true
			}
		);
EOF;
	}
	
	function writeData($result,$keys) {
		mysql_data_seek($result,0);
		$i = 0;
		while ($line = mysql_fetch_array($result)) {
			echo '"'.SMADB::dygraphTimeFormat($line).',';
			$x=0;
			foreach ($keys as $key) {
				echo $line[$key];
				$x++;
				if ($x < count($keys)) {
					echo ",";
				} else {
					echo "\\n\"";
				}
				
			}
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	}
	
	function writeDataWithProduct($result,$keys) {
		mysql_data_seek($result,0);
		$i = 0;
		while ($line = mysql_fetch_array($result)) {
			echo '"'.SMADB::dygraphTimeFormat($line).',';
			$x=0;
			$product = 1;
			foreach ($keys as $key) {
				$product = $product * $line[$key];
				echo $line[$key];
				$x++;
				if ($x < count($keys)) {
					echo ",";
				} else {
					echo ",".$product."\\n\"";
				}
				
			}
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}
	}
	
	function drawDoubleGraph ($result,$header,$label1,$label2,$unit,$key1,$key2,$div) {
		$graphName = "g".$div;
		$labeldiv = $div . "label";	
		echo "data = \"Date,".$label1.",".$label2."\\n\" +";

		mysql_data_seek($result,0);
		$i = 0;
		while ($line = mysql_fetch_array($result)) {
			echo '"'.SMADB::dygraphTimeFormat($line).','.$line[$key1].','.$line[$key2].'\n"';
			$i++;
			if ($i < mysql_num_rows($result)) {echo "+"; } else {echo ";";}
		}

		echo <<<EOF
			$graphName = new Dygraph(
		document.getElementById('$div'),
		data,
		{
			title: '$header',
			labelsDiv: '$labeldiv',
			ylabel: '$unit',
			legend: 'always',
			drawPoints: true,
			labelsDivStyles: { 'textAlign': 'right' },
			showRangeSelector: true
			}
		);
EOF;
	}
  ?>