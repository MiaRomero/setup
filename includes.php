<?php
	session_start();

	class ItemInCart {

		public $itemID;
		public $itemQuantity;
		
		public function __construct($itemID, $itemQuantity) {
              $this->itemID = $itemID;
              $this->itemQuantity = $itemQuantity;
      	}

	}

	function returnArrayOfProperties ($itemObject) {

  		$propertyArray = get_object_vars($itemObject);
  		return $propertyArray; 

	}

	class ordersPerDay {

		public $orderDate;
		public $numberOfOrdersPerDay;
		

		public function __construct($orderDate, $numberOfOrdersPerDay) {
 	
 			$this->orderDate = $orderDate; 
 			$this->numberOfOrdersPerDay = $numberOfOrdersPerDay;	

		}

	}

	function run_query($sqlStatement) {

		$url = $_SERVER["HTTP_HOST"];

		$urlCount = substr_count($url, "flaminghoopproductions");

		if ($urlCount > 0) {

			$dbConnection = mysql_connect("mariards.cxkafor7uu7y.us-west-2.rds.amazonaws.com", "maria_romero", "Litaly'sDB!");

			/*if (!$dbConnection) {

				die('Could not connect: ' . mysql_error());
			}
			*/
			$selectedDB = mysql_select_db("chessClub", $dbConnection);

			if (!$selectedDB){

				die('Could not connect: ' . mysql_error());
			}
		}

		else {

			$dbConnection = mysql_connect("localhost", "root", "");

			mysql_select_db("ChessClub", $dbConnection);

			//error_reporting(E_ALL);

			//ini_set("display_errors", 1);
		}	
		
		$query = mysql_query($sqlStatement) OR die("Error:".mysql_error());

		mysql_close($dbConnection);

		return $query; 
	}

	function run_Action_Query ($sqlStatement) {

		$url = $_SERVER["HTTP_HOST"];

		$urlCount = substr_count($url, "flaminghoopproductions");

		if ($urlCount > 0) {

			$dbConnection = mysql_connect("mariards.cxkafor7uu7y.us-west-2.rds.amazonaws.com", "maria_romero", "Litaly'sDB!");
		
			mysql_select_db("chessClub", $dbConnection);
		}

		else {

			$dbConnection = mysql_connect("localhost", "root", "");

			mysql_select_db("ChessClub", $dbConnection);
		}

		$query = mysql_query($sqlStatement) OR die("Error:".mysql_error());

		$lastIndex = mysql_insert_id();

		mysql_close($dbConnection);

		return $lastIndex;
	}


	function updateDbWithOrder ($customerID, $inStock) {

		$currentDate = date("Y-m-d");

		$sqlUpdateOrderTable = "INSERT INTO Orders (customerID, orderDate) VALUES (" . $customerID . ", '" . 
			$currentDate . "')";

		$orderID = run_Action_Query($sqlUpdateOrderTable);

		foreach ($inStock as $purchasedItemID) {

			$sqlUpdateOrderLineItem = "INSERT INTO OrderLineItem (orderID, itemID) VALUES (" . $orderID . "," . 
				$purchasedItemID . ")";

			run_Action_Query($sqlUpdateOrderLineItem);
		}
	}

	function listItems($listArray) {

		foreach ($listArray as $listID) {

			$sqlStock = "SELECT itemName FROM Inventory WHERE itemID = " . $listID;

			$queryStock = run_query($sqlStock);

			while ($row = mysql_fetch_array($queryStock)) {

				echo "<p>" . $row["itemName"] . "</p>";
			}
		}
	}

	function getIDsFromShoppingCartArray () {

		$cartIDs = "";

			foreach($_SESSION['shoppingCart'] as $itemObject) {

				$itemIDforCart = $itemObject->itemID;

				$cartIDs = $cartIDs . ", " . $itemIDforCart;	
			}
		
		$cartIDs = substr($cartIDs, 1);	
			
		return $cartIDs;
	}

	function calculateTotalItemsInCart () {

		$totalItemsInCart = 0;

		if (count($_SESSION['shoppingCart']) > 0) {

			foreach ($_SESSION['shoppingCart'] as $itemObject) {

				$propertyArray = returnArrayOfProperties($itemObject);

				$itemQuantity = $propertyArray['itemQuantity'];

				$totalItemsInCart += $itemQuantity;	
			}
		}

		return $totalItemsInCart;
	}

	function displayShoppingCartBar () {

		if(!isset($_SESSION['currentCustomerName'])) {
					
			$currentCustomerName = "";
		}
		else{

			$currentCustomerName = $_SESSION['currentCustomerName']; 
		}

		$itemsInCart = calculateTotalItemsInCart();

		echo "<div class=fixedShoppingCartBar>
				<div id='shoppingCustomer'><p>Welcome " . $currentCustomerName . "!</p></div>
				<div id='quantityInCart'><p>Items in Shopping Cart:  " . $itemsInCart . "</p></div>
			</div>";
		 
	}

	function fillOutOrderForm ($firstname, $lastname, $streetaddress, $city, $state, $zip){				

echo <<<_END

				<h3>Please verify your shipping information</h3>

				<form action= "Success.php" method="post">

					<div class="formSpacing">
						First name:
						<input name="first" type="text" value="$firstname">
					</div>

					<div class="formSpacing">
						Last Name:
						<input name="last" type='text' value="$lastname">
					</div>

					<div class="formSpacing">
						Address:
						<input name="address" type="text" value="$streetaddress">
					</div>

					<div class="formSpacing">
						City:
						<input name="city" type="text" value="$city">
					</div>

					<div class="formSpacing">
						State:
						<input name="state" type="text" value="$state">
					</div>

					<div class="formSpacing">
						Zip:
						<input name="zip" type="text" value="$zip">
					</div>

					<h3>Please fill in your payment information</h3>

					<div class="formSpacing">
						Credit Card Number:
						<input name="creditCardNumber" type="text">
					</div>

					<div class="formSpacing">
						Expiration Date:
						<input name="cardExpiration" type="text">
					</div>

					<button type='submit'>Submit</button>

				</form>	

				<a class="button" href="ShoppingCart.php">Return to Shopping Cart</a>

				<a class="button" href="List.php">Continue Shopping</a>
_END;
}

	function encrypt_password($password) {

		$salt1 = "%>uuA)";

		$salt2 = "Bj&ia*!}";

		$token = hash("ripemd160", "$salt1$password$salt2");

		return $token;
	}

	function calculateOrderTotal() {

		$orderTotal = 0;

			/*foreach ($_SESSION['shoppingCart'] as $itemObject) {

				$propertyArray = returnArrayOfProperties($itemObject);

				$itemID = $propertyArray['itemID'];

				$itemQuantity = $propertyArray['itemQuantity'];*/

		$itemIDs = getIDsFromShoppingCartArray ();


		$sqlItemsInShoppingCart = "SELECT itemID, itemPrice FROM Inventory WHERE itemID IN (" . $itemIDs . ")";

		$query = run_query($sqlItemsInShoppingCart);

		while($row = mysql_fetch_array($query)) { 
			foreach ($_SESSION['cart'] as $item) {
				if ($item->id == $row->itemID) {

				}
			}

			$totalItemPrice = $row['itemPrice'] * $row['itemQuantity'];		
			$orderTotal = $orderTotal + $totalItemPrice;
		}

				
			

		return $orderTotal;
	}	


?>