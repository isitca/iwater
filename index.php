<?php
include ('php_code/arf.php');
if (isset($showRegisterForm) && $showRegisterForm){?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>iWater</title>
        <link href="assets/style.css" rel="stylesheet" />
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=swap" rel="stylesheet" />
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>

    </head>
    <body>
           <div class="container header header-reg">
               <div class="row">
                   <div class="col-sm-12 reg-logo text-center mt-5">
                       <img src="assets/images/logo.png" class="mt-5" />
                   </div>
                   <div class="col-sm-12 text-center mt-5">
                       <div class="mx-4 mt-4">
                           <img src="assets/images/icons/Client_icon.png" />
                           <span class="header-text1">Client: </span> <span class="header-text2"><?php echo $_COOKIE["clientid"]; ?></span>
                       </div>
                   </div>
               </div>


               <div class="row text-white reg-form">
                   <div class="col-sm-12">
                       <b>Registering Tag</b>
                       <form action="index.php" method="get">
                           <input type="text" name="LOCTAG" placeholder="cust11_room444" readonly value="<?php echo $_COOKIE['loctag']?>" id="LOCTAG" />
                           <b>Location Name</b>
                           <input type="text" name="LOCNAME"  id="LOCNAME" autofocus />
                           <label class="checkbox mt-3">
                               Include Photo
                               <input name="WITHPIC" id="WITHPIC" value="withpic"  type="checkbox" />
                               <span class="checkmark"></span>
                           </label>
                           <button class="reg-btn mt-5" type="submit">Register This Tag</button>
                       </form>
                   </div>
               </div>
           </div>
    </body>
</html>
<?php  } ?>

