<?php
//echo "<style> * { font-size:80px; font-family:arial;} </style>"; //MUskan
require("arffunctions.php");
# Page should be called with the Location Tag Number as a parameter
# e.g. http://mmchugh.ie/iwater/arf.php?LOCTAG=iw123456
#  The tag would actually be coded with this entire string - website, page, location number
# The page gets resubmitted with each of the forms 
#  regtag or logsample look for their own params

## echo '<script type="text/javascript"> alert(document.cookie); </script> ';

if (isset($_POST["uploadpic"])) { # Script was called from the upload form
    uploadpic();
} else {
    $loctag = $_GET["LOCTAG"];
    $clientreg = $_GET["CLIENTREG"];
    if ($loctag != "") {
        $location = checktag($loctag);
        # $location[0] should be:
        #  NOT_REGISTERED  we don't have this loctag already, register it
        #  withpic   run the full process for individual location with photos
        #  nopic   run for individual location but without need for photos
        #  multiloc   special tag for multiple un-tagged locations
        # $location[1] is the location full name read from the file

        $clientid = checkuser();
        if ($clientid == "") {
            echo "<br>Not a valid user - please contact iWater <br>";
        } else {
//            echo "Client: " . $clientid;
            if ($location[0] == "NOT_REGISTERED") {
                regtag($loctag);
            } else {
                echo '<div id="scandit-barcode-picker"></div>';

                logsample($loctag, $location, $clientid);
            }
        }
    } elseif ($clientreg != "") {
        if ($clientreg == "DELETE") {
            echo "deleting user " . checkuser() . "<br>";
            deleteuser();
        } else {
            $confirmmessage = setuser($clientreg);
            echo $confirmmessage; //Muskan
        }
    } else {
        $clientid = checkuser();
        $datestamp = date("YmdHis");
        $csvfilename = $clientid . $datestamp . ".csv";
        listmysamples($csvfilename);
    }
}
?>
<script src="https://cdn.jsdelivr.net/npm/scandit-sdk@5.x"></script>
<script>
    var selectedImgBox = null; // no image is selected initially

    // Get all the img-box elements
    var imgBoxes = document.querySelectorAll('.img-box');

    // Add click event listeners to each img-box
    imgBoxes.forEach(function (imgBox) {
        imgBox.addEventListener("click", function () {
            // Deselect the previously selected img-box, if any
            if (selectedImgBox !== null) {
                selectedImgBox.style.backgroundColor = "";
            }

            // Select the new img-box and set its background color
            this.style.backgroundColor = "#7DBFBD";
            selectedImgBox = this;
        });
    });
    //EXIT Button at end uses BACK to bring you to the start so that BACK will Exit
    //This code allows the normal form to be hidden
    if (window.performance && window.performance.navigation.type === window.performance.navigation.TYPE_BACK_FORWARD) {
        //alert('Got here using the browser "Back" or "Forward" button.');
        document.getElementById("hideatend").style.display = "none";
        document.write('<p style="text-align:center;">Now use the  <br> < BACK <br>  Button to Close this Window</p>');
    }

    function scannerstuff() {
        document.getElementById("scanicon").style.display = "none"; //to prevent double launch
        document.getElementById("header").style.display = "none";
        document.getElementById("footer").style.display = "none";
        scanditkey = "AYvBYw1SKQb5DckwfQpJcGYN1lZiF0WX6F4Jz60BOqviYFuQPxKqNukNI0lUdvtnbCoUITw+Bm/odxKjrWNAlZI3sRGBLFR6uHHHFLEohN1GVT3NlhyHyElbP2/6cZxpIivn5TAAYAIlPyU9pAA3OssQcaWi9QVQbNULAO3EPm6KShlJZn8lqL3MNkRPxy9bvVzH3aVknVRmFcwqYxKlvndxGAeFwPL1SkCgDJYClQDKHAIOHH2DP55ascE6kKoxaaiPl4nWUMPOgYHWzoOm3683cMopPPtA54bodE3vAl3BHu+gKmSwQjNzSqK5KxzdturPQeSnUktOTgur3qYZvKaW80PWVyUAKWBzfOlS/MTJzx3llQZ8gJ/aPVjyjiQg2tdO/EM64oRZbvDbBDR8dY/joEi1iGaxIldZ5bo6j08epMtm0ymY11qU+u4JRj4e40at3as3I521RwhQejBrFmgtURlIQlqXlPq8vdbmP4r7hrRldi1sT0tPQVkDwBOSzKH4+noLMMkwFb2zoM7Lffm0uLzJejX0f25swhs5I0SsNeADPjwqhYzcgzuYYPJN6ImS0k7m1sqgatC603fWVg1XteGrnH7RhF/7YXOeELi6Lx9Ax4pq8SFEmFbSxC2SmtiPei4cXFMiV9BJ2JmR4JC2HeqgwQqS3f8+6pZ4p+8zEcC6sr/a+vHGPU527+a8I5ypLhjhUrxqI8YQouVz/bpBJZ5Rd1ECCYlc8Bb5V5+qmGxHJsy9wc6u6XU6P3dZn5QfVdsNCuyVFFsF0p9gdZSspyXyfcC5BVSGKPCISm5dG493yr+5zwPvOWIT/UgI31w="
        ScanditSDK.configure(scanditkey, {
            engineLocation: "https://cdn.jsdelivr.net/npm/scandit-sdk@5.x/build/",
        })
            .then(() => {
                return ScanditSDK.BarcodePicker.create(document.getElementById("scandit-barcode-picker"), {
                    // enable some common symbologies
                    //  https://docs.scandit.com/4.5/web/enums/barcode.symbology.html  for full list
                    scanSettings: new ScanditSDK.ScanSettings({enabledSymbologies: ["pdf417", "qr", "code39", "data-matrix", "upce", "ean13"]}),
                });
                device1 = ScanditSDK.ScanSettings.getDeviceName();
                alert(device1);

            })
            .then((barcodePicker) => {
                // barcodePicker is ready here, show a message every time a barcode is scanned
                barcodePicker.on("scan", (scanResult) => {
                    //alert(scanResult.barcodes[0].data);
                    bcr1 = scanResult.barcodes[0].data;
                    //device1 = ScanditSDK.ScanSettings.getDeviceName();
                    //bottleid1 = bcr1.substr(6, 10);
                    //alert(bottleid1);
                    //alert(device1);
                    bottleid1 = bcr1;
                    document.getElementById("BOTTLETAG").value = bottleid1;
                    document.getElementById("savebutton").style.display = "block";
                    document.getElementById("savebutton").click(); // auto-submit form

                    barcodePicker.pauseScanning();
                    barcodePicker.setVisible(false);
                });
            });
    }


    function gotostart() {
        window.history.go(-(window.history.length - 1));
        document.write("RESTART")
    }

    function hidescanicon() {
        document.getElementById("scanicon").style.display = "none";
    }

    function goback() {
        document.getElementById("scanicon").style.display = "none";
    }


    function cameraclick() {
        document.getElementById('takepic').click();
        document.getElementById('uploadicon').style.display = "block";
    }

    function onSelectImg() {
        var fileInput = document.getElementById("takepic");
        var selectedFile = fileInput.files[0];
        console.log(selectedFile, "selected file");
        var selectedImage = document.getElementById("selectedImage");
        selectedImage.src = window.URL.createObjectURL(selectedFile);
        selectedImage.style.display = "block"
        console.log(selectedImage.src);
    }

    function uploadclick() {
        document.getElementById('takepic').click();
        document.getElementById('cameraicon').style.display = "block";
        document.getElementById('uploadicon').style.display = "none";

    }


</script>