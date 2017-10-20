<?php 

$method = $_SERVER['REQUEST_METHOD'];

if($method == 'POST')
{
    $requestBody = file_get_contents('php://input');
	$json = json_decode($requestBody);
    $speech="";
    $expense1 = $json->result->parameters->expense1;
    $expense2= $json->result->parameters->expense2;
    $number1 = $json->result->parameters->number;
    $number2= $json->result->parameters->number1;
    $stat=$json->result->parameters->statspecific;
    $loan=$json->result->parameters->loan;
    $name=$json->result->parameters->any;
    $reset=$json->result->parameters->resetspecific;

    $link = mysqli_connect("sql12.freemysqlhosting.net","sql12200139","tL6VPjRFak","sql12200139");
    
    if(mysqli_connect_error())
    {
        $speech="There was an error connecting to the database.";
    }
    else
    {
        if($expense1!=NULL && $number1!=NULL)
        {
            $query1="select * from `users` where category='".$expense1."'";
            $result=mysqli_query($link,$query1);
            if(mysqli_num_rows($result)>0)
            {

                $row=mysqli_fetch_array($result);
                $new=$row['amount']+$number1;

                $query="UPDATE users SET amount='$new' WHERE category='".$expense1."'";
                mysqli_query($link,$query);
            }
            else
            {
                $query="INSERT INTO users (category, amount) VALUES('$expense1','$number1')";
                mysqli_query($link,$query);
            }
        
            if($expense2!=NULL && $number2!=NULL)
            {
                $speech="added ".$number1." rupees on ".$expense1." and ".$number2." rupees on ".$expense2;
                $query1="select * from `users` where category='".$expense2."'";
                $result=mysqli_query($link,$query1);
                if(mysqli_num_rows($result)>0)
                {

                    $row=mysqli_fetch_array($result);
                    $new=$row['amount']+$number2;

                    $query="UPDATE users SET amount='$new' WHERE category='".$expense2."'";
                    mysqli_query($link,$query);
                }
                else
                {

                    $query="INSERT INTO users (category, amount) VALUES('$expense2','$number2')";
                    mysqli_query($link,$query);
                } 
            }
            else
            {
                $speech="Got it! ".$number1." on ".$expense1." :D";
            }
        }
        if($stat!=NULL)
        {
            $strring="";
            if($stat=="expense" || $stat=="both")
            {
              $strring=$strring."EXPENSE STATS: ";
              $query="SELECT * FROM users";
              $result=mysqli_query($link,$query);
              $row=mysqli_fetch_array($result);
              $sum=0;
              while($row)
              {
                  $sum=$sum+$row['amount'];
                  $row=mysqli_fetch_array($result);
              }

              $result=mysqli_query($link,$query);
              $row=mysqli_fetch_array($result);
              while($row)
              {
                  $percent=($row['amount']/$sum)*100;
                  $strring=$strring.$row['category']." - ".round($percent,2)." % || ";
                  $row=mysqli_fetch_array($result);
              }
              $strring=$strring."Total - ".$sum." rupees";
              if($stat=="both")
                  $strring=$strring." |||| LOAN STATS: ";
              $speech=$strring;
            }
            if($stat=="loan" || $stat=="both")
            {
                  if($stat!="both")
                      $strring=$strring."LOAN STATS : ";
                  $query="SELECT * FROM loan";
                  $result=mysqli_query($link,$query);
                  $row=mysqli_fetch_array($result);
                  if(!$row)
                      $strring=$strring."All accounts settled!";
                  while($row)
                  {
                      if($row['amount']>0)
                        $strring=$strring."Take ".$row['amount']." from ".$row['name']." || ";
                      else
                        $strring=$strring."Give ".(-1*$row['amount'])." to ".$row['name']." || ";
                      $row=mysqli_fetch_array($result);
                  }
                  $speech=$strring; 
            }
            if($stat=="none")
            {
                $speech="Cancelled operation of displaying stats";
            }
            
            
        }
        if($loan!=NULL && $number1!=NULL)
        {
            $query1="select * from `loan` where name='".$name."'";$xx=0;
            $result=mysqli_query($link,$query1);
            if(mysqli_num_rows($result)>0)
            {
                $row=mysqli_fetch_array($result);
                if($loan=="gave")
                    $new=$row['amount']+$number1;
                else if($loan=="took")
                    $new=$row['amount']-$number1;
                $query="UPDATE loan SET amount='$new' WHERE name='".$name."'";
                mysqli_query($link,$query);
                $xx=$new;
            }
            else
            {
                if($loan=="took")
                    $number1=-1*$number1;
                $query="INSERT INTO loan (name, amount) VALUES('$name','$number1')";
                mysqli_query($link,$query);
                $xx=$number1;
            }
            if($xx>0)
                $speech="You have to take ".$xx." from ".$name;
            else if($xx<0)
            {
                $xx=-1*$xx;
                $speech="You have to give ".$xx." to ".$name;
            }
            else
            {
                $query="DELETE FROM loan WHERE name='".$name."'";
                mysqli_query($link,$query);
                $speech="You have settled accounts with ".$name;
            }
                
        }
        
        if($reset!=NULL)
        {
    		$speech="";
            if($reset=="loan" || $reset=="both")
            {
                $query = "DELETE FROM loan"; 
                if(mysqli_query($link, $query))
                {
                  $speech="Loan data reset!";  
                }
                else
                    $speech="Sorry we can't reset loan right now";//c
            }
            if($reset=="expense" || $reset=="both")
            {
                $query = "DELETE FROM users";    
                if(mysqli_query($link, $query))
                {
                    if($reset=="both")
                    {
                        $speech="Both Loan and expense data reset";
                    }
                    else
                        $speech="Expense data reset";
                }
                else
                {
                    if($speech=="")
                        $speech="Sorry we can't reset expense right now";
                    if($speech=="Sorry we can't reset loan right now")
                        $speech="Sorry we can't reset both loan and expense right now";
                    else
                        $speech=$speech." but we can't reset expense data right now";
                }
            }
        }
    }
	$response = new \stdClass();
	$response->speech = $speech;
	$response->displayText = $speech;
	$response->source = "webhook";
	echo json_encode($response);
}
else
{
	echo "Method not allowed";
}

?>