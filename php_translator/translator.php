
<?php
// updated Nov 13, 2012 for Hubo+ model
//
// semi-automatic script to get hubo kinematic/dynamic info from DASL-provided xml file and convert it into DM format
// still have to manually add MDH data since lots of ZScrews are involved.
// and provide rotation matrix (for each link) from DASL coordinate to DH compliant coordiate


function insert_tabs($count=1)
{
	$output = ' ';
	for($x = 1; $x <= $count; $x++)
	{
	   $output .= "\t";
	}
    return $output;
}



function Print_Matrix($c)
{
	$rows = count($c);	//echo $rows;
	$cols = count($c[0]);	//echo $cols;
	for ($i=0; $i<$rows; $i++)
	{
		if ($cols>1)
		{
			for($j=0;$j<$cols;$j++)
			{
				echo insert_tabs(1).sprintf("%f",$c[$i][$j]);
			}
			echo "\n";
		}
		else
		{
			echo insert_tabs(1).sprintf("%f",$c[$i]);
			echo "\n";
		}
	}
	echo "\n";
}


function Print_Matrix_Special_Fmt($c)
{
	$rows = count($c);	//echo $rows;
	$cols = count($c[0]);	//echo $cols;
	for ($i=0; $i<$rows; $i++)
	{
		if ($cols>1)
		{
			if ($i == 0)
				echo insert_tabs(1);
			else
				echo insert_tabs(1)."                ".insert_tabs(1);

			for($j=0;$j<$cols;$j++)
			{
				echo insert_tabs(1).sprintf("%f",$c[$i][$j]);
			}
			echo "\n";
		}
		else
		{
			if ($i == 0)
				echo insert_tabs(1);
			else
				echo insert_tabs(1)."                ".insert_tabs(1);
			echo insert_tabs(1).sprintf("%f",$c[$i]);
			echo "\n";
		}
	}
	echo "\n";
}

function Matrix_Add($a,$b) //Matrix Add
{
	$m=count($a);
	$n=count($a[0]);
	$m1=count($b);
	$n1=count($b[0]);
	if(($m!=$m1)||($n!=$n1))
		exit( "\nError: Matrix_Add(): matrix dimension NOT matched !!!!!!\n\n\n");
	else
	{
		for($i=0;$i<$m;$i++)
		{
			if ($n > 1)
			{
	   			for($j=0;$j<$n;$j++)
	   			{
					$c[$i][$j]=$a[$i][$j]+$b[$i][$j];
					//echo $c[$i][$j]." ";
	   			}
			}
			else
			{
				$c[$i]=$a[$i]+$b[$i];
			}

		}
		return $c;

	}
}


function Matrix_Mul($a,$b) //Matrix_Multiply
{
	$k=count($a[0]);  //a_cols
	$k1=count($b);    //b_rows

	$m=count($a);	//a_rows
	$n=count($b[0]);	//b_cols

	if($k!=$k1)
		exit("\nError: Matrix_Mul: matrix dimension NOT matched !!!!!!\n\n\n");
	else
	{

		for($i=0;$i<$m;$i++) 	// i - a_row
		{
			if ($n >1)
			{ 
				for($j=0;$j<$n;$j++)	// j - b_col
			   	{  
					$c[$i][$j]=0;
					for($l=0;$l<$k;$l++)
					{
				 		$c[$i][$j]+=$a[$i][$l]*$b[$l][$j];
					}
			   	}
			}
			else //if b has only one column
			{
				for($j=0;$j<$n;$j++)	// j - b_col
			   	{  
					$c[$i]=0;
					for($l=0;$l<$k;$l++)
					{
				 		$c[$i]+=$a[$i][$l]*$b[$l];
					}
			   	}				
			}
   
		}

		return $c;
	}
}


function Transpose($a) 
{
	$m=count($a);   // rows
	$n=count($a[0]);	// cols

	if ($m>1)
	{
		for($i=0;$i<$m;$i++)
		{
			if ($n >1)
			{	
				for($j=0;$j<$n;$j++)
				{
		  	 		$b[$j][$i]=$a[$i][$j];
				}  	 
			}
			else
			{
				$b[0][$i]=$a[$i];
			}
		}
	}
	else // if transpose a 1 x n matrix
	{
		for($j=0;$j<$n;$j++)
		{
  	 		$b[$j]=$a[0][$j];
		}  	 
	}
	return $b;
}


function Scalar_Mul($a, $M)
{
	$rows = count($M);	// rows;
	$cols = count($M[0]);	//$cols;

	for($i=0;$i<$rows;$i++)
	{
		if ($cols > 1)
		{
			for($j=0;$j<$cols;$j++)
			{
				$aM[$i][$j]= $a * $M[$i][$j];
			}
		}
		else
		{
			$aM[$i]= $a * $M[$i];
		}
	}
	return $aM;

}

function Cross($x)
{
	$rows = count($x);	// rows;
	$cols = count($x[0]);	//cols;

	if ($rows==3 && $cols == 1)
	{
	   	$M = array(array(0, 	-$x[2], 	$x[1]),
				   array($x[2], 	0, 		-$x[0]),
				   array(-$x[1], $x[0], 	0)		);
	}
	elseif ($rows==1 && $cols == 3)
	{
	   	$M = array(array(0, 	-$x[0][2], 	$x[0][1]),
				   array($x[0][2], 	0, 		-$x[0][0]),
				   array(-$x[0][1], $x[0][0], 	0)		);		
	}
	else
	{
		exit("\nError: Cross: matrix dimension WRONG !!!!!!\n\n\n");
	}
	return $M; 
}




// -------------------------------------------------------------
// Rotation: DASL -> DH 
// manual input 
$Rot = array(
"Body_Torso" => array( array(1,0,0), array(0,1,0), array(0,0,1) ),
"Body_Hip" => array( array(1,0,0), array(0,1,0), array(0,0,1) ),

"Body_LHY" => array( array(0,1,0), array(-1,0,0), array(0,0,1) ),
"Body_LHR" => array( array(0,1,0), array(0,0,1), array(1,0,0) ),
"Body_LHP" => array( array(0,0,1), array(1,0,0), array(0,1,0) ),
"Body_LKP" => array( array(0,0,1), array(1,0,0), array(0,1,0) ),
"Body_LAP" => array( array(0,0,1), array(1,0,0), array(0,1,0) ),
"Body_LAR" => array( array(0,0,1), array(0,-1,0), array(1,0,0) ),

"Body_LSP" => array( array(1,0,0), array(0,0,-1), array(0,1,0) ),
"Body_LSR" => array( array(0,0,-1), array(0,1,0), array(1,0,0) ),
"Body_LSY" => array( array(0,1,0), array(-1,0,0), array(0,0,1) ),
"Body_LEP" => array( array(1,0,0), array(0,0,-1), array(0,1,0) ),
"Body_LWY" => array( array(1,0,0), array(0,1,0), array(0,0,1) ),
"Body_LWP" => array( array(1,0,0), array(0,0,-1), array(0,1,0) ),



"Body_RHY" => array( array(0,1,0), array(-1,0,0), array(0,0,1) ),
"Body_RHR" => array( array(0,1,0), array(0,0,1), array(1,0,0) ),
"Body_RHP" => array( array(0,0,1), array(1,0,0), array(0,1,0) ),
"Body_RKP" => array( array(0,0,1), array(1,0,0), array(0,1,0) ),
"Body_RAP" => array( array(0,0,1), array(1,0,0), array(0,1,0) ),
"Body_RAR" => array( array(0,0,1), array(0,-1,0), array(1,0,0) ),

"Body_RSP" => array( array(1,0,0), array(0,0,-1), array(0,1,0) ),
"Body_RSR" => array( array(0,0,-1), array(0,1,0), array(1,0,0) ),
"Body_RSY" => array( array(0,1,0), array(-1,0,0), array(0,0,1) ),
"Body_REP" => array( array(1,0,0), array(0,0,-1), array(0,1,0) ),
"Body_RWY" => array( array(1,0,0), array(0,1,0), array(0,0,1) ),
"Body_RWP" => array( array(1,0,0), array(0,0,-1), array(0,1,0) ),
);


// -------------------------------------------------------------



$xml = simplexml_load_file("./huboplus.kinbody.xml") 
       or die("Error: Cannot create object");
	   
foreach($xml->xpath('/kinbody/body') as $Body)
{
	$BodyName = $Body['name']; // note: this is an object, not a string
	//echo $BodyName;
	//$BodyName = trim($BodyName, "\"");
	$mass = (double)$Body->mass->total;

	$joint_limits_str = $xml->xpath("/kinbody/Joint[offsetfrom='". $BodyName ."']/limitsdeg");
	//var_dump($joint_limits_str);
	$joint_limits = preg_split('/\s+/', trim($joint_limits_str[0]));
	//var_dump($joint_limits);

	//echo "/KinBody/Joint[offsetfrom='". $BodyName ."']/anchor";
	$cg_temp = $xml->xpath("/kinbody/Joint[offsetfrom='". $BodyName ."']/anchor");
	//var_dump($cg_temp);
	//$c_g =explode(' ', $cg_temp[0]);
	$c_g = preg_split('/\s+/', trim($cg_temp[0]));
	foreach ($c_g  as $key => $var) 
	{
		$cg[$key] = -(double)$var; // note the '-' sign
	}
	// foreach (array_expression as $key => $val)
	// on each iteration, the value of the current element is assigned to $val
	// the current element's key is assigned to $key  
	// internal array pointer is advanced by one

	//var_dump($cg_temp);
	

	echo insert_tabs(1)."Name            ".insert_tabs(2). "\"".$BodyName."\""."\n";
	echo insert_tabs(1)."Graphics_Model  ".insert_tabs(2)."\""."\""."\n" ;
	echo insert_tabs(1)."Mass            " .insert_tabs(2).  $mass;
	echo "\n";
	echo insert_tabs(1)."Inertia         " ;
	//var_dump( explode( ' ', $Body->Mass->inertia ) );

	// $I_com = explode( ' ', $Body->mass->inertia) ); 	// cannot deal with extra spaces

	// preg_split - Split string by a regular expression	
	// trim - get rid of leading and trailing spaces
	$I_com = preg_split('/\s+/', trim($Body->mass->inertia));
	//var_dump($I_com);
	//echo count($I_com);

	$R = $Rot["".$BodyName];

	if (count($I_com)>1)
	{	
		foreach ($I_com  as $key => $var) 
		{
    		$I_com[$key] = (double)$var;
		}

		//for ($i = 0; $i<3; $i++)
		//{
		//	if ($i == 0)
		//	{
		//		echo insert_tabs(2); 
		//	}
		//	else
		//	{
		//		echo "                " .insert_tabs(3);
		//	}
		//
		//	echo $I_com[3*$i].insert_tabs(1).  $I_com[3*$i+1] .insert_tabs(1). $I_com[3*$i+2] . "\n";
		//}

		$Ibarcom = array(array($I_com[0], $I_com[1], $I_com[2]),
						array($I_com[3], $I_com[4], $I_com[5]),
						array($I_com[6], $I_com[7], $I_com[8]));

		/// paralell axis theorem
		$_cg = Scalar_Mul(-1, $cg);	// but sign is not important when converting inertia
		$Ibar = Matrix_Add($Ibarcom, Scalar_Mul($mass, Matrix_Mul(Cross($_cg), Transpose(Cross($_cg)) )  )	);	

		//$R = $Rot["".$BodyName];

		$IbarDH = Matrix_Mul( Matrix_Mul($R, $Ibar), Transpose($R) );

		//var_dump($Ibarcom);
		Print_Matrix_Special_Fmt($IbarDH);	
		//Print_Matrix($Ibar);
		//Print_Matrix($Rot["".$BodyName]);



	}
	echo "\n";

	if (count($c_g)>1)
	{
		$cgDH = Matrix_Mul($R, $cg);
		//var_dump($cgDH);
		//Print_Matrix(Transpose($cg));
		echo insert_tabs(1)."Center_of_Gravity   ".insert_tabs(1);
		Print_Matrix(Transpose($cgDH));
		//echo insert_tabs(1)."Center_of_Gravity   ".insert_tabs(2).$cg[0].insert_tabs(1).$cg[1] .insert_tabs(1).$cg[2]  ."\n";	
	}
	else 
	{
		echo insert_tabs(1)."Center_of_Gravity   ".insert_tabs(2)."NULL"."\n";
	}
	echo insert_tabs(1)."Number_of_Contact_Points"."\n";
	echo insert_tabs(1)."Contact_Locations   "."\n";
	echo insert_tabs(1)."MDH_Parameters  "."\n";	
	echo insert_tabs(1)."Initial_Joint_Velocity  ".	insert_tabs(1)."0"."\n";
	//echo insert_tabs(1)."Joint_Limits    ".	insert_tabs(1)."0".insert_tabs(1)."0"."\n";
	//echo insert_tabs(1)."Joint_Limits    ".	insert_tabs(1). (string)$joint_limits_str[0]."\n";
	echo insert_tabs(1)."Joint_Limits    ".	insert_tabs(1). $joint_limits[0].insert_tabs(1).$joint_limits[1]."\n";
	echo insert_tabs(1)."Joint_Limit_Spring_Constant ".	insert_tabs(1)."0"."\n";
	echo insert_tabs(1)."Joint_Limit_Damper_Constant ".	insert_tabs(1)."0"."\n";
	echo insert_tabs(1)."Actuator_Type   ".	insert_tabs(1)."0". "\n";
	echo insert_tabs(1)."Joint_Friction  ".	insert_tabs(1)."0". "\n";
	echo "\n";
	echo "\n";
}

//var_dump($Rot);

?>
