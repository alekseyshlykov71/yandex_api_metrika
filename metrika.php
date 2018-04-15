<?php
	$arrContextOptions=array(
		"ssl"=>array(
			"verify_peer"=>false,
			"verify_peer_name"=>false,
		),
	);  

	$query=mysqli_query($mc,'SELECT * FROM config') or die(mysqli_error($mc));
	$query_counter++;
	
	$access_token=mysqli_fetch_array($query)['access_token'];
	
	mysqli_query($mc,"DELETE * FROM counters");
	$query_counter++;
	
	$counters_result=file_get_contents('https://api-metrika.yandex.ru/management/v1/counters?oauth_token='.$access_token,false, stream_context_create($arrContextOptions));
	$counters_result=json_decode($counters_result);
	$counters=$counters_result->counters;
	
	
	$query=mysqli_query($mc,"SELECT * FROM counters ORDER BY id ASC");
	while($arr=mysqli_fetch_array($query)) {
		$counters[$arr['id']]['id_counter']=$arr['id_counter'];
		$counters[$arr['id']]['name']=$arr['name'];
	}

	for($i=0;$i<count($counters);$i++) {
		$id=$counters[$i]['id_counter'];
		$name=$counters[$i]['name']; 
		$name=str_replace('http://','',$name);
		$name=str_replace('/','',$name);
		$name=str_replace('www.','',$name);
		
		
		$query=mysqli_query($mc,"SELECT * FROM counters WHERE id_counter=$id");
		$query_counter++;
		if (mysqli_num_rows($query)==0) {
			mysqli_query($mc,"INSERT INTO counters (id_counter,name) VALUES ($id,'$name')");
			$query_counter++;
		}
		
	}

	if ((isset($_POST['date1'])) && isset($_POST['date2'])){
		
		$date1=$_POST['date1'];
		$date2=$_POST['date2'];
		$datetime1 = new DateTime($date1);
		$datetime2 = new DateTime($date2);
		$interval = $datetime1->diff($datetime2);
		$interval = (int)$interval->format('%R%a');
		
		$csv="Id;Counter;Site;";
		$csv.=date_format($datetime1, 'd m').";";
		for ($i=0;$i<=$interval-1;$i++) {
			$datetime1->add(new DateInterval('P1D'));
			$csv.=date_format($datetime1, 'd m').";";
		}
		
		$csv.="\n";
		$query=mysqli_query($mc,"SELECT *
			FROM `counters`
			ORDER BY
			counters.`name` ASC");
		$query_counter++;
		$total_visits=0;
		
		
		while($arr=mysqli_fetch_array($query)) {
			$counter_visits=file_get_contents('https://api-metrika.yandex.ru/stat/v1/data/bytime?id='.$arr['id_counter'].'&group=day&metrics=ym:s:visits&date1='.$date1.'&date2='.$date2.'&oauth_token='.$access_token,false, stream_context_create($arrContextOptions));
			$counter_visits=json_decode($counter_visits);
			
			$csv.=$arr['id'].";".$arr['id_counter'].";".$arr['name'].";".implode(";",$counter_visits->data[0]->metrics[0])."\n";
		}
		file_put_contents('report.csv',$csv);
	
		echo "<a href='report.csv'>Скачать файл</a>";
	}
	else {?>
		<form method="POST">
			<input type="date" name="date1">
			<input type="date" name="date2">
			<input type="submit">
		</form>
	<?php }
?>	