<?php
$datapath = "data/iwater/";
$cookiename = 'clientid';
$userexpire = mktime(0, 0, 0, 4, 4, 2024);  # Apr 2024 - scandit renewal time - need to update clients then
$loctag = "loctag";
$showRegisterForm = false;

function checkuser() {
    global $cookiename;
    if (isset($_COOKIE[$cookiename])) {
        return($_COOKIE[$cookiename]);
    }
    else {
        return("");
        # Temp workaround for cookie issue
        # return("Unregistered_User");
    }
}

function setuser($clientid) {
    global $cookiename, $datapath, $userexpire;
    $fullfilename = $datapath . "clients/" . $clientid;
    #test skip file check to allow rereg
    #if (file_exists($fullfilename)) {
    setcookie($cookiename, $clientid, $userexpire, '/');
    $result = "New Client device confirmed: " . $clientid;
    #	unlink($fullfilename);
    #}
    #else {
    #	$result = "Client name NOT approved";
    #}
    return($result);
}

function deleteuser() {
    #Used to Delete cookie on this device
    global $cookiename;
    setcookie($cookiename, "", time() - 3600);
}

function checktag($tagnum) {
    #search for file
    global $datapath;
    $filesearch = $datapath . "tags/" . $tagnum . ".*";
    $tagfiles = glob($filesearch);
    if (!empty($tagfiles)) {
        # We only care about the first file found - should not be any duplicates
        $location[0] = pathinfo($tagfiles[0], PATHINFO_EXTENSION);
        #Now open the file for the location description
        $tagfile = fopen($tagfiles[0], "r");
        if ($tagfile) {  # opened ok for reading
            $location[1] = fgets($tagfile);  # Just one string per file
            fclose($tagfile);
        }
        else {
            $location[1] = "Problem Reading Tag File";
        }
    }
    else {
        $location[0] = "NOT_REGISTERED";
        $location[1] = "";
    }
    return($location);
}

function regtag($tagnum) {
    global $datapath, $showRegisterForm;
    $scriptname = basename($_SERVER["SCRIPT_FILENAME"]);
# show a form displaying tagnum and asking for location description - typed or from list

    $locname = $_GET["LOCNAME"]; #  This gets filled by the form and appears on resubmit
    # second time around it writes the tag file
    if ($locname) {
        $picornot = $_GET["WITHPIC"];
        if ($picornot != "withpic") {
            $picornot = "nopic";
        }
        $tagfile = fopen($datapath . "tags/" . $tagnum . "." . $picornot, "w") or die("Could not save file ". $tagnum);
        // nopic is the default - change to withpic if preferred
        fwrite($tagfile, $locname);
        fclose($tagfile);
        echo "<br>" . $tagnum . " Registered: " . $locname; //Muskan
    }
    else {
        # Display the form to capture Location Name
//        echo ("<br>Registering Tag<br>");
//        echo "<form method=\"get\" action=\"" . $scriptname . "\" autocomplete=\"off\">";
//        echo "<input type=\"text\" name=\"LOCTAG\" id=\"LOCTAG\" value=\"" . $tagnum . "\" readonly><br>";
//        echo "<br>Location Name<br><input type = \"text\" name=\"LOCNAME\" id=\"LOCNAME\" autofocus><br>";
//        echo 'Include Photos: <input type="checkbox" name="WITHPIC" id="WITHPIC" value="withpic" style="height:70px; width:90px; border-width:4px"><br>';
//        echo "<input type=\"submit\" name=\"submit\" value=\"Register this tag\"><br></form>";
        setcookie('loctag', $tagnum);
        $showRegisterForm = true;
    }
}

function logsample($tagnum, $location, $clientid) {
    global $datapath, $locdate;
    echo '<div id="hideatend">';
//    echo "Location:<br> $location[1] <br>"; //MUSKAN
    $withpicchecked = "";
    if ($location[0] == "withpic") {
        $withpicchecked = " checked ";
    }

    $bottletag = scanbottle($tagnum, $withpicchecked); # $location[0] = withpic or nopic from filename to default the checkbox
    $bottletag = preg_replace( '/[\W]/', '', $bottletag);
    $bottletag = substr($bottletag, 0, 10); //these 2 lines allow testing with any barcode by filtering and chopping
    echo '</div>'; #End of hideatend
    #scannerstuff();
    if ($bottletag) {
        $datestamp = date("YmdHis");
        $locdate = $_GET["LOCDATE"]; # retrieve start date from tag
        #split the tagnum and bottletid if needed assuming format ididid_Extrabit where Extrabit is optional
        # - can be used to group locations or categorise bottles
        list($bottleid, $bottletype) = explode("_", $bottletag);
        list($locid, $locsite) = explode("_", $tagnum);
        $basefilename = $datapath . "samples/" . $clientid . "_" . $locdate . "_" . $datestamp . "_" . $locsite . "_" . $locid . "_" . $bottletype . "_" . $bottleid;
        #$picornot = $location[0];
        #We now get this from checkbox instead
        $picornot = $_GET["WITHPIC"]; #Note this collects by name not ID
        #echo "<script type='text/javascript'>alert('$picornot');</script>";
        saveresult($basefilename, $picornot); # We have location and bottle ID
        if ($picornot == "withpic") {  # Filetype JPG etc will be appended
            picproc($basefilename);
        }
        else {
            savefilenopic($basefilename, "NOPIC"); # result saved - just show last screen
        }
        #elseif ($picornot == "nopic") {
        #	savefilenopic($basefilename, "NOPIC"); # result saved - just show last screen
        #}
        #elseif ($picornot == "multiloc") {
        #	multilocproc($basefilename);  # also a txt file but with individual Location
        #}
    }
}


function scanbottle($tagnum, $withpicchecked) {
    $scriptname = basename($_SERVER["SCRIPT_FILENAME"]);
    $bottletag = $_GET["BOTTLETAG"]; # Filled by the previous cycle
    if ($bottletag) {
        return($bottletag);
    }
    else {
          echo '<html lang="en">';
          echo '<head>';
          echo '<meta charset="utf-8">';
          echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
          echo '<title>iWater</title>';
          echo '<link href="assets/style.css" rel="stylesheet"/>';
          echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">';
          echo '</head>';
          echo '<body>';
          echo '<form>';
          echo '<div class="container header" id="header">';
          echo '<div class="row">';
          echo '<div class="col-sm-12 logo">';
          echo '<img src="assets/images/logo.png"/>';
          echo '</div>';
          echo '<div class="col-sm-12">';
          echo '<div  class="mx-4 my-3">';
          echo '<img src="assets/images/icons/Client_icon.png">';
          echo "<span class='header-text1'>Client: </span> <span class='header-text2'>{$_COOKIE['clientid']}</span>";
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '<div class="row head">';
          echo '<div class="col py-3 d-flex justify-content-center">';
          echo '<div class="bg-icon mx-3">';
          echo '<img src="assets/images/icons/lab_icon.png">';
          echo '</div>';
          echo '<div class="icon-text">';
          echo '<p class="text-heading">iLabLocation ID:</p>';
          echo "<p class='text-paragraph'>{$tagnum}</p>";
          echo '</div>';
          echo '</div>';
          echo '<div class="col py-3 d-flex justify-content-start">';
          echo '<div class="bg-icon">';
          echo '<img src="assets/images/icons/photo_icon.png">';
          echo '</div>';
          echo '<div class="icon-text mx-3 d-flex">';
          echo '<label class="checkbox">';
          echo '<p class="text-heading">Include Photo</p>';
          echo '<input name="WITHPIC" id="WITHPIC" value="withpic"  type="checkbox" checked="checked" />';
          echo '<span class="checkmark"></span>';
          echo '</label>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '<div class="container footer" id="footer">';
          echo '<div class="row">';
          echo '<div class="col-sm-12">';
          echo '<div class="mx-4 first-row my-3">';
          echo '<img src="assets/images/icons/Location_icon.png">';
          echo '<span class="text1">Location: </span>';
          echo '<span class="text2">1194 Summit Park Avenue, Spearville, United States</span>';
          echo '</div>';
          echo '</div>';
          echo '<div class="col-sm-12">';
          echo '<div class="mx-4 first-row my-3 ">';
          echo '<img src="assets/images/icons/Bottle icon.png">';
          echo '<span class="text1">Select Bottle and Fill </span>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '<div class="row images-section">';
          echo '<div class="col">';
          echo '<div class="col img-box" id="img-box1">';
          echo '<img id="myImage" src="assets/images/bottle_1 3.png">';
          echo '</div>';
          echo '</div>';
          echo '<div class="col">';
          echo '<div class="col img-box" id="img-box2">';
          echo '<img id="myImage" src="assets/images/bottle_2 3.png">';
          echo '</div>';
          echo '</div>';
          echo '<div class="col">';
          echo '<div class="col img-box" id="img-box2">';
          echo '<img id="myImage" src="assets/images/bottle_3 1.png">';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '<div class="row images-section py-5">';
          echo '<div class="col-sm-12">';
          echo '<div class="col-sm-12 img-box1">';
          echo '<img src="assets/images/Rectangle 21.png">';
          echo '</div>';
          echo '</div>';
          echo '<input type="hidden"  name="LOCTAG" id="LOCTAG" value="' . $tagnum . '" >';
          echo '<input type="hidden"  name="BOTTLETAG" id="BOTTLETAG">';
          $startdate = date('YmdHis');
          echo '<input type="hidden" id="LOCDATE" name="LOCDATE" value="' . $startdate . '">';
          echo '<input type="submit"  name="savebutton" id="savebutton" style="display:none; background: url(saveicon.png) no-repeat; height: 700px; width: 500px;" >';
          echo '<div id="scandit-barcode-picker"></div>';
          echo '<div id="scanicon" align="center">';
          echo '<div class="col-sm-12 pt-4">';
          echo '<button onclick="scannerstuff()" class="btn-scan" type="button">';
          echo '<span>Tap to Scan Bottle</span>';
          echo '</button>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '</div>';
          echo '</form>';
          echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>';
          echo '</body>';
          echo '</html>';

//
//        echo '<form method="get" action="' . $scriptname . '" autocomplete="off">';
//        echo 'Location ID: <input type="text" name="LOCTAG" id="LOCTAG" value="' . $tagnum . '" readonly><br>';
//        echo 'Include Photos: <input type="checkbox" name="WITHPIC" id="WITHPIC" value="withpic" ';
//        echo $withpicchecked;
//        echo 'style="height:70px; width:90px; border-width:4px"><br>';
//        #Bottle ID Does not need to be visible - Scanned and goes directly to next screen
//        #echo 'Bottle ID<br> <input type="text" name="BOTTLETAG" id="BOTTLETAG" autofocus readonly >';
//        echo '<input type="hidden" name="BOTTLETAG" id="BOTTLETAG" >';
//        $startdate = date("YmdHis");
//        # This marks the time we start the operation - Script called by NFC Tag being read
//        # It is fixed by registeriing in an input tag
//        echo '<input type="hidden" id="LOCDATE" name="LOCDATE" value="' . $startdate . '"><br>';
//
//        echo '<input type="submit"  name="savebutton" id="savebutton" style="display:none; background: url(saveicon.png) no-repeat; height: 700px; width: 500px;" ></form>';
//
//        #scandit scan
//
//        echo '<div id="scandit-barcode-picker"></div>';
//        echo '<div id="scanicon" align="center" >'; # This is used to make these 2 disappear
//        # call scandit
//        echo 'Click to Scan Bottle<br><img src="phonescanner.jpg" name="scanicon" style="width:100%;" onclick="scannerstuff()">';
//        echo '<br>';
//        echo '</div>';
//        echo '<br>';
    }
}


function takepic($picfilename, $beforeorafter) {

echo '<html lang="en">';
echo '<head>';
   echo '<meta charset="utf-8">';
   echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
   echo '<title>iWater</title>';
   echo '<link href="assets/style.css" rel="stylesheet"/>';
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">';
   echo '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet" />';
   echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css" integrity="sha512-5A8nwdMOWrSz20fDsjczgUidUBR8liPYU+WymTZP1lmY9G6Oc7HlZv156XqnsgNUzTyMefFTcsFH/tnJE/+xBg==" crossorigin="anonymous" referrerpolicy="no-referrer" />';
echo '</head>';
echo '<body>';
echo '<div class="container header">';
   echo '<div class="row">';
       echo '<div class="col-sm-12 logo">';
          echo '<img src="assets/images/logo.png"/>';
       echo '</div>';
       echo '<div class="col-sm-12">';
           echo '<div class="mx-3 my-3">';
               echo '<img src="assets/images/icons/Client_icon.png">';
    echo "<span class='header-text1'>Client: </span> <span class='header-text2'>{$_COOKIE['clientid']}</span>";
    echo '</div>';
       echo '</div>';
   echo '</div>';
   echo '<div class="row head">';
        echo '<div class="col-6 py-3 d-flex justify-content-center">';
           echo '<div class="bg-icon mx-3">';
                echo '<img src="assets/images/icons/lab_icon.png">';
           echo '</div>';
            echo '<div class="icon-text">';
               echo '<p class="text-heading">iLabLocation ID:</p>';
               echo "<p class='text-paragraph'>{$_COOKIE['loctag']}</p>";
            echo '</div>';
       echo '</div>';
   echo '</div>';
echo '</div>';
echo '<div class="container footer">';
    echo '<div class="row">';
       echo '<div class="col-sm-12">';
           echo '<div class="mx-4 first-row my-3">';
              echo '<img src="assets/images/icons/Location_icon.png">';
               echo '<span class="text1">Location: </span>';
                echo '<span class="text2">1194 Summit Park Avenue, Spearville,
                        United States</span>';
           echo '</div>';
      echo '</div>';
   echo '</div>';
    echo '<form action="" method="post" enctype="multipart/form-data">';
  echo '<div class="row images-section camera" id="emptyBottleImage">';
       echo '<h2 id="bottle-label-empty">Upload photo of empty bottle</h2>';
       echo '<h2 id="bottle-label-filled" style="display: none">Upload photo of filled bottle</h2>';

       echo '<div class="upload-img">';
            echo '<div class="upload">';
               echo '<label class="upload-area">';
                   echo '<input type="file" id="takepic" name="takepic" accept="image/*" capture="camera" onchange="onSelectImg()">';
                   echo '<span class="upload-button" id="cameraicon" style="cursor:pointer;" onclick="cameraclick()">';
               echo '<i class="fa fa-camera" ></i>';
             echo '</span>';
    echo '</label>';
           echo '</div>';
           echo '<div class="mt-5 d-flex justify-content-center">';
    echo '<img src="" id="selectedImage" height="100px;" width="100px" style="display: none">';

           echo '</div>';
           echo '<div class="mx-5 pt-5">';
    echo '<input type="submit" value="Upload Picture" name="uploadpic" id="uploadicon" style="display: none">';
    echo "<input type=\"text\" id=\"savefilename\" name=\"savefilename\" value=\"$picfilename\" hidden >";
    echo "<input type=\"text\" id=\"beforeorafter\" name=\"beforeorafter\" value=\"$beforeorafter\" hidden >";
    echo '</div>';
       echo '</div>';

    echo '</div>';

echo '</form>';

//    echo '<form action="" method="post" enctype="multipart/form-data">';
//    echo '<div class="row images-section camera" id="filledBottleImage" style="display: none">';
//    echo '<h2>Upload photo of filled bottle</h2>';
//
//    echo '<div class="upload-img">';
//    echo '<div class="upload">';
//    echo '<label class="upload-area">';
//    echo '<input type="file" id="takepic" name="takepic" accept="image/*" capture="camera" onchange="onSelectImg()">';
//    echo '<span class="upload-button" id="cameraicon" style="cursor:pointer;" onclick="cameraclick()">';
//    echo '<i class="fa fa-camera" ></i>';
//    echo '</span>';
//    echo '</label>';
//    echo '</div>';
//    echo '<div class="mt-5 d-flex justify-content-center">';
//    echo '<img src="" id="selectedImage" height="100px;" width="100px" style="display: none">';
//
//    echo '</div>';
//    echo '<div class="mx-5 pt-5">';
//    echo '<input type="submit" value="Upload Picture" name="uploadpic" id="uploadicon" style="display: none">';
//    echo "<input type=\"text\" id=\"savefilename\" name=\"savefilename\" value=\"$picfilename\" hidden >";
//    echo "<input type=\"text\" id=\"beforeorafter\" name=\"beforeorafter\" value=\"$beforeorafter\" hidden >";
//    echo '</div>';
//    echo '</div>';
//
//    echo '</div>';
//
//    echo '</form>';
echo '</div>';
echo '</div>';
echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>';
echo '</body>';
echo '</html>';
    #echo "<br>$picfilename  xx  $beforeorafter xx <br>";
//    echo '<form action="" method="post" enctype="multipart/form-data">';
////    echo '<img src="camera.png" height="400" width="600" id="cameraicon" style="cursor:pointer; display:block" onclick="cameraclick()" />';
////    echo '<input type="file" id="takepic" name="takepic" accept="image/*" capture="camera" >';
//
//    #echo '<img src=$selectedfile/>';
//    echo '<br><br>';
////    echo '<input type="submit" value="upload picture" name="uploadpic" id="uploadicon" style="background: url(saveicon.png) no-repeat; height: 500px; width: 1000px;';
////    echo ' display: none ';
//    echo'" >';
//    echo "<input type=\"text\" id=\"savefilename\" name=\"savefilename\" value=\"$picfilename\" hidden >";
//    echo "<input type=\"text\" id=\"beforeorafter\" name=\"beforeorafter\" value=\"$beforeorafter\" hidden >";
//    echo "</form>";
}

function uploadpic() { # This gets called when the camera icon is hit and form posted
    #echo "<br>$target_file <br>";
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($_FILES["takepic"]["name"],PATHINFO_EXTENSION));
    $beforeorafter = $_POST["beforeorafter"];
    $savefilename = $_POST["savefilename"];
    $target_file = $savefilename . "_" . $beforeorafter . "."  . $imageFileType;
    #echo "<br>$target_file <br>";
    // Check if image file is a actual image or fake image
    if(isset($_POST["submit"])) {
        $check = getimagesize($_FILES["takepic"]["tmp_name"]);
        if($check !== false) {
            echo "File is an image - " . $check["mime"] . ".";
            $uploadOk = 1;
        } else {
            echo "File is not an image.";
            $uploadOk = 0;
        }
    }
    // Check if file already exists
    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        $uploadOk = 0;
    }
    // Check file size
    if ($_FILES["fileToUpload"]["size"] > 500000) {
        echo "Sorry, your file is too large.";
        $uploadOk = 0;
    }
    // Allow certain file formats

    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
        echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        // if everything is ok, try to upload file
    } else {
        if (move_uploaded_file($_FILES["takepic"]["tmp_name"], $target_file)) {
//            echo "<br>The file ". htmlspecialchars( basename( $_FILES["takepic"]["name"])). " has been uploaded to " . basename($target_file) . "<br>";

            if ($beforeorafter == "BEFORE") {
//                echo "<br>Now Fill the Bottle before taking a second picture of the bottle<br>";
                echo '<script>window.onload = function() {';
                echo 'document.getElementById("bottle-label-empty").style.display = "none";';
                echo 'document.getElementById("bottle-label-filled").style.display = "block";};';
                echo '</script>';
                takepic($savefilename, "AFTER");
            }
            elseif ($beforeorafter == "AFTER") {
                lastscreen();
            }

        } else {
            echo "Sorry, there was an error uploading your file.";
        }
    }
}

function lastscreen() {
    #echo '<img src="exit.png" height="400" width="600" style="cursor:pointer" onclick="gotostart()" />';
    #No Go directly to download screen
    listmysamples();
}

function listmysamples($csvfilename = "") {
    # Show a table with all samples collected by current user with option to download (move to folder for collection)
    # When Called with filename, it archives the individual sample files and creates the CSV File
    # and places the table and a link to the CSV file into an email
    global $datapath, $clientid, $loctag;
    $clientid = checkuser();
    $myfiles = $datapath . "samples/" . $clientid . "*.dat";
    $headerfilename = $datapath . "samples/" . "header.txt";
    $archivepath = $datapath . "samples/" . "archive/";
    $sentpath = "./sent/"; #destination for CSV Files

    if (glob($myfiles)) {
        $myfiletable = "<thead>";
        $mycsvfile = "";


        $headerfile = fopen($headerfilename, "r") or die("Cannot open Header File");
        $header = fgets($headerfile);
        fclose($headerfile);
        #First Table Line - Column Headers read from header.txt
        $myfiletable = $myfiletable . "<tr>";
        $tr = explode(",", $header);
        foreach ($tr as $td) {
            $myfiletable = $myfiletable . "<th>" . $td . "</th>";
            $mycsvfile = $mycsvfile . $td . ",";
        }
        $myfiletable = $myfiletable . "</tr></thead><tbody>";
        $mycsvfile = $mycsvfile . "location Name\n"; #Add an extra column for locatrion name

        $samplefilelist = glob($myfiles);  #list all sample files saved
        #$grandtotal = 0;

        foreach ($samplefilelist as $thisfilename) {
            # Dont actually need to open the file - data is in the filename
            $thissample = basename($thisfilename, ".dat");
            $myfiletable = $myfiletable . "<tr>";
            $fieldnum = 0;
            $tr = explode("_", $thissample);
            $location = checktag($tr[4]);
            $locationname = $location[1];
            #$lastitem = count($tr);
            $itemnum = 0;
            foreach ($tr as $td) {
                $myfiletable = $myfiletable . "<td>" . $td . "</td>";
                $mycsvfile = $mycsvfile . $td . ",";
            }
            $myfiletable = $myfiletable . "</tr>";
            if ($csvfilename != "") {
                #archive off the sample file so we don't see it again
                rename($thisfilename, $archivepath . basename($thisfilename));
            }
            $mycsvfile = $mycsvfile . $locationname . "\r\n";
        }
        $myfiletable = $myfiletable . "</tbody>";

echo '<html lang="en">';
  echo '<head>';
   echo '<meta charset="utf-8">';
   echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
   echo '<title>iWater</title>';
    echo '<link href="assets/style.css" rel="stylesheet"/>';
   echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">';
   echo '<link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet" />';
echo '</head>';
 echo '<body>';
    echo '<div class="container header">';
        echo '<div class="row">';
           echo '<div class="col-sm-12 logo">';
               echo '<img src="assets/images/logo.png"/>';
            echo '</div>';
           echo '<div class="col-sm-12">';
               echo '<div  class="mx-3 my-3">';
                   echo '<img src="assets/images/icons/Client_icon.png">';
                    echo "<span class='header-text1'>Client: </span> <span class='header-text2'>{$_COOKIE['clientid']}</span>";
               echo '</div>';
            echo '</div>';
        echo '</div>';
       echo '<div class="row head">';
            echo '<div class="col-6 py-3 d-flex justify-content-center">';
                echo '<div class="bg-icon mx-3">';
                echo '<img src="assets/images/icons/lab_icon.png">';
               echo '</div>';
                echo '<div class="icon-text">';
                   echo '<p class="text-heading">iLabLocation ID:</p>';
                    echo "<p class='text-paragraph'>{$_COOKIE['loctag']}</p>";
                echo '</div>';
           echo '</div>';
        echo '</div>';
    echo '</div>';
    echo '<div class="container footer">';
        echo '<div class="row">';
            echo '<div class="col-sm-12">';
                echo '<div class="mx-4 first-row my-3 ">';
                   echo '<img src="assets/images/icons/Location_icon.png">';
                   echo '<span class="text1">Location: </span>';
                   echo ' <span class="text2">1194 Summit Park Avenue, Spearville,
                        United States</span>';
               echo '</div>';
           echo '</div>';
        echo '</div>';
        echo '<div class="row images-section">';
            echo '<div class="table-responsive">';
            echo '<table class="table table-striped">';
            echo $myfiletable;
            echo '</table>';
            echo '</div>';

        echo '</div>';
        echo '<div class="row images-section py-3">';
            echo '<div class="col-sm-12">';
        if ($csvfilename != "") {
            $csvurl = "http://mmchugh.ie/iwater/sent/" . $csvfilename;
            $csvsavefilename = $sentpath .  $csvfilename;
            $csvsavefile = fopen($csvsavefilename, "w") or die("Unable to save CSV File" . $csvsavefilename);
            fwrite($csvsavefile, $mycsvfile);
            fclose($csvsavefile);
            $myfiletable = $myfiletable . '<br><a href=' . $csvurl . '>Download or view CSV file ' . $csvurl . '</a><br>';
            $htmlheader = '<html><head></head><body>';
            $htmlfooter = '</body></html>';
            echo "<br><a href=\"mailto:maurice@mmchugh.ie?subject=samples&body=" . $htmlheader . $myfiletable . $htmlfooter . "\">Mail this </a>";
        }
        else {
            $scriptname = basename($_SERVER["SCRIPT_FILENAME"]);
            echo '<a href="' . $scriptname . '?LOCTAG=' . $loctag .  '"> <button class="btn-scan-bottle" type="button">';
            echo 'Scan Another Bottle at this Location';
            echo '</button></a>';
            echo '<div class="title mt-4">';
            echo '<h1>Or</h1>';
            echo '</div>';
            echo '<a href="' . $scriptname . '?LOCTAG=' . $loctag .  '"><button class="btn-scan-location mt-2" type="button">';
            echo '<img src="assets/images/location.png" class="me-2"><p>Tap NFC at New location</p>';
            echo '</button></a>';
            echo '<button class="btn-scan-save mt-4" type="button">';
            echo '<img src="assets/images/lab.png"  class="me-2"><span>Save and submit to Lab</span>';
            echo '</button>';

//            echo '<br><a href=' . $scriptname . '?LOCTAG=' . $loctag .  '>Scan Another Bottle at this Location</a><br><br>';
//            echo 'or<br>';
//            #This link, when clicked will call te page with no arguments which calls this function with a csvfilename
//            echo '<br><a href=' . $scriptname .  '>Save and Archive</a>';
        }

            echo '</div>';
        echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>';
 echo '</body>';
echo '</html>';

    }
    else {
        echo "<br>No Files to Download<br>";
        $thispage = "http://".$_SERVER[HTTP_HOST].$_SERVER[REQUEST_URI];
        echo "<br><a href=\"" . $thispage . "\"> Check Again </a>";
    }
}


function saveresult($basefilename, $picornot) {
    $resultfilename = $basefilename . ".dat";
    $resultfile = fopen($resultfilename, "w") or die("Unable to save results ". $resultfilename);
    fwrite($resultfile, "SAMPLE_TAKEN;" . $picornot);
    fclose($resultfile);
}

function picproc($savefilename) {
//    echo "<br>Click the Camera icon to Take a Picture of the EMPTY bottle<br><br>";
    takepic($savefilename, "BEFORE"); #  This then calls itself AFTER filing

}

function savefilenopic($savefilename) {
    lastscreen();
}

function multilocproc($savefilename) {
    lastscreen();
}

?>