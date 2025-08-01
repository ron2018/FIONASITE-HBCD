    <meta name="mobile-web-app-capable" content="yes">
    <link rel="icon" sizes="192x192" href="images/touch/chrome-touch-icon-192x192.png">

    <!-- Add to homescreen for Safari on iOS -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-title" content="Material Design Lite">
    <link rel="apple-touch-icon-precomposed" href="apple-touch-icon-precomposed.png">

    <!-- Tile icon for Win8 (144x144 + tile color) -->
    <meta name="msapplication-TileImage" content="images/touch/ms-touch-icon-144x144-precomposed.png">
    <meta name="msapplication-TileColor" content="#3372DF">

    <!-- SEO: If your mobile URL is different from the desktop URL, add a canonical link to the desktop page https://developers.google.com/webmasters/smartphone-sites/feature-phones -->
    <!--
    <link rel="canonical" href="http://www.example.com/">
    -->

<?php
   session_start();

   include("php/AC.php");
   $user_name = check_logged();
   echo('<script type="text/javascript"> user_name = "'.$user_name.'"; </script>'."\n");
   // print out all the permissions
   $permissions = list_permissions_for_user($user_name);
   $p = '<script type="text/javascript"> permissions = [';
   foreach($permissions as $perm) {
     $p = $p."\"".$perm."\",";
   }
   echo ($p."]; </script>\n"); 

   // what is the current project name?
   $project = getUserVariable( $user_name, "project_name");
   if ($project === FALSE) {
      $project = "HBCD";
   }
   echo('<script type="text/javascript"> project_name = "' . $project . '";</script>');

   $filter_pattern = "";
   if (is_readable("php/config.json")) {
      $config = json_decode(file_get_contents("php/config.json"),True);
      if ( isset($config['HIDEPATTERN']) ) {
         $filter_pattern = $config['HIDEPATTERN'];
         echo('<script type="text/javascript"> hidepattern = "' . $filter_pattern . '";</script>');
      }
   }


   // For each $permissions: "SiteHBCD", "SitePCGC"
   // get the site name: "HBCD", "PCGC"
   // and create and array of $sites
   $sites = array();
   foreach ($permissions as $perm) {
      $parts = explode("Site", $perm);
      if (count($parts) > 1) {
         $sites[] = $parts[1];
      }
   }
   $p = '<script type="text/javascript"> sites = [';
   foreach($sites as $s) {
     $p = $p."\"".$s."\",";
   }
   echo ($p."]; </script>\n");   

   $admin = false;
   if (check_role( "admin" )) {
      $admin = true;
   }
   if (check_permission( "developer" )) {
      $developer = true;
   }
   if (check_permission( "see-scanner" )) {
      $seescanner = true;
   }
?>

    <style>
.study {
   width: 72px;
   height: auto;
   border: 1px solid gray;
   border-radius: 3px;
   color: blue;
   display: inline-flex;
   margin: 2px;
}
.item {
   width: 10px;
   height: 10px;
   border: 1px solid gray;
   border-radius: 3px;
   background-color: white;
   margin: 0px;
   margin-top: 2px;
   margin-left: 2px;
}
.no-item {
   width: 10px;
   height: 10px;
   border: 1px solid white;
   background-color: white;
   margin: 0px;
   margin-top: 2px;
   margin-left: 2px;
}
.group-archive .item {
   background-color: rgba(226,87,30,.5);
}
.group-archive .item-heigh {
   background-color: rgba(226,87,30,.5);
}
.raw.item {
   background-color: rgba(255,255,0,.5);
}
.quarantine.item {
   background-color: rgba(150,191,51,.5);
}
.outbox.item {
   background-color: rgba(0,0,255,.5);
}
.DAIC.item {
   background-color: rgba(139,0,255,.5);
}
.series-group {
   font-size: 18px;
   line-height: 12px;
   margin-bottom: 2px;
}
.serie {
   display: inline-flex;
}
#modal-data-flow {
   width: 90%;
}
.item-heigh {
   width: 10px;
   height: 50%;
   min-height: 10px;
   border: 1px solid gray;
   border-radius: 3px;
   color: white;
   margin: 2px;
}
.group-archive {
   width: 14px;
   height: auto;
}
.group-raw {
   width: 16px;
   height: auto;
}
.group-quarantine {
   width: 16px;
   height: auto;
}
.group-outbox {
   width: 16px;
   height: auto;
}

#modal-kspace-status {
   width: 90%;
}
.item-heigh {
   width: 10px;
   height: 50%;
   min-height: 10px;
   border: 1px solid gray;
   border-radius: 3px;
   color: white;
   margin: 2px;
}
.group-archive {
   width: 14px;
   height: auto;
}
.group-raw {
   width: 16px;
   height: auto;
}
.group-quarantine {
   width: 16px;
   height: auto;
}
.group-outbox {
   width: 16px;
   height: auto;
}

@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 100;
  src: local('Roboto Thin'), local('Roboto-Thin'), url(font/roboto/Jzo62I39jc0gQRrbndN6nfesZW2xOQ-xsNqO47m55DA.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 300;
  src: local('Roboto Light'), local('Roboto-Light'), url(font/roboto/Hgo13k-tfSpn0qi1SFdUfaCWcynf_cDxXwCLxiixG1c.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 400;
  src: local('Roboto'), local('Roboto-Regular'), url(font/roboto/zN7GBFwfMP4uA6AR0HCoLQ.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 500;
  src: local('Roboto Medium'), local('Roboto-Medium'), url(font/roboto/RxZJdnzeo3R5zSexge8UUaCWcynf_cDxXwCLxiixG1c.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 700;
  src: local('Roboto Bold'), local('Roboto-Bold'), url(font/roboto/d-6IYplOFocCacKzxwXSOKCWcynf_cDxXwCLxiixG1c.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: normal;
  font-weight: 900;
  src: local('Roboto Black'), local('Roboto-Black'), url(font/roboto/mnpfi9pxYH-Go5UiibESIqCWcynf_cDxXwCLxiixG1c.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: italic;
  font-weight: 400;
  src: local('Roboto Italic'), local('Roboto-Italic'), url(font/roboto/W4wDsBUluyw0tK3tykhXEfesZW2xOQ-xsNqO47m55DA.ttf) format('truetype');
}
@font-face {
  font-family: 'Roboto';
  font-style: italic;
  font-weight: 700;
  src: local('Roboto Bold Italic'), local('Roboto-BoldItalic'), url(font/t6Nd4cfPRhZP44Q5QAjcC50EAVxt0G0biEntp43Qt6E.ttf) format('truetype');
}
@font-face {
  font-family: 'Material Icons';
  font-style: normal;
  font-weight: 400;
  src: local('Material Icons'), local('MaterialIcons-Regular'), url(font/2fcrYFNaTjcS6g4U3t-Y5StnKWgpfO2iSkLzTz-AABg.ttf) format('truetype');
}

.material-icons {
  font-family: 'Material Icons';
  font-weight: normal;
  font-style: normal;
  font-size: 24px;
  line-height: 1;
  letter-spacing: normal;
  text-transform: none;
  display: inline-block;
  white-space: nowrap;
  word-wrap: normal;
  direction: ltr;
}
.mark {
  background-color: DarkSlateGray;
}
.detail-information {
    font-size: 10px;
    left: 10px;
    bottom: 0px;
    position: absolute;
    color: white;
    white-space: no-wrap;
}
.scan-date {
    color: white;
    font-size: 10px; 
    position: absolute; 
    right: 10px; 
    bottom: 15px;
    white-space: no-wrap;
}
</style>
    <!-- <link href="https://fonts.googleapis.com/css?family=Roboto:regular,bold,italic,thin,light,bolditalic,black,medium&amp;lang=en" rel="stylesheet"> -->
    <!-- <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet"> -->
    <link rel="stylesheet" href="css/material.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/fullcalendar.min.css">
    <link rel="stylesheet" media="print" href="css/fullcalendar.print.css">
    <link rel="stylesheet" href="css/jquery.bonsai.css">
    <link rel="stylesheet" href="css/select2.min.css">
   
    <style>
     #view-source {
       position: fixed;
       display: block;
       right: 0;
       bottom: 0;
       margin-right: 40px;
       margin-bottom: 40px;
       z-index: 900;
     }
     .green-background {
       background-color: lightgreen;
     }
     .red-background {
       background-color: red;
     }
     .drop-down-item{

     }
     .form-control {
        width: 300px;
        margin-bottom: 10px;
        margin-top: 5px;
     }
     .control-label {
        color: black;
        font-size: 12pt;
     }
     .form-control option {
        height: 25px;
        background-color: white;
        padding-top: 5px;
     }
     #detected-scans {
        height: 300px;
        overflow-y: scroll;
     }
     .SeriesName {
	 font-size: 20px;
	 font-weight: 500;
	 line-height: 1;
	 letter-spacing: .02em;
     }
     .select2-container {
        margin-bottom: 10px;
        margin-top: 5px;
     }
     #select2-session-participant-results li {
         border-bottom: 0px;
         margin-bottom: 0px;
         padding: 0px;
     }

     li {
	 border-bottom: 1px solid gray;
	 margin-bottom: 5px;
	 list-style-type: none;
	 margin-left: 0px;
	 padding: 5px;
     }
     #detected-scans {
	 padding-left: 0px;
     }
     .send-series-button {
	 margin-bottom: 10px;
     }
     .status0 {
	 background: linear-gradient(to right, white, white 80%, lightgreen);
     }
     .status1 {
	 background: linear-gradient(to right, white, white 80%, green);
     }
     .status2 {
	 background: linear-gradient(to right, white, white 80%, red);
     }
     .unknown-type {
       color: yellow !important;
     }
     .transferred-type {
       color: 'rgba(255,255,255,0.56)';
     }
     .demo-drawer {
       overflow-x: hidden;
     }
    </style>
    <link rel="stylesheet" href="css/dialog-polyfill.min.css">
  </head>
  <body>
    <div class="demo-layout mdl-layout mdl-js-layout mdl-layout--fixed-drawer mdl-layout--fixed-header">
      <header class="demo-header mdl-layout__header mdl-color--white mdl-color--grey-100 mdl-color-text--grey-600">
        <div class="mdl-layout__header-row">
          <span class="mdl-layout-title hostname" title="Flash I/O Network Appliance">&nbsp;&nbsp;HBCD's FIONA</span>
	  <nav class="mdl-navigation mdl-menu--top-right">
	    <a class="mdl-navigation__link" style="color: gray;">User: <?php echo($user_name); ?></a>
	  </nav>
          <div class="mdl-layout-spacer"></div>
          <div class="mdl-layout-spacer"></div>
          <button class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--icon" id="hdrbtn">
            <i class="material-icons">more_vert</i>
          </button>
          <ul class="mdl-menu mdl-js-menu mdl-js-ripple-effect mdl-menu--bottom-right" for="hdrbtn">
            <li class="mdl-menu__item" id="dialog-about-button">About</li>
            <li class="mdl-menu__item"><a href="applications/viewer/" title="Image viewer for FIONA DICOM images">Image Viewer</a></li>
<?php if ($developer) : ?>
            <li class="mdl-menu__item"><a href="invention.html">Development</a></li>
            <li class="mdl-menu__item" id="dialog-data-flow-button">Data Flow</li>
<?php endif; ?>
<?php if ($admin) : ?>
            <li class="mdl-menu__item" onclick="document.location.href = '/applications/User/admin.php';">Admin Interface</li>
            <li class="mdl-menu__item" id="dialog-clean-quarantine-button">Quarantine Data</li>
            <li class="mdl-menu__item" id="dialog-setup-button">Setup</li>
<?php endif; ?>
            <li class="mdl-menu__item" id="dialog-mrs-status-button">MRS Status </li>
            <li class="mdl-menu__item" id="dialog-kspace-status-button">Kspace Status </li>
            <li class="mdl-menu__item" id="dialog-change-password-button">Change Password</li>
            <li class="mdl-menu__item" onclick="logout();">Logout</li>
          </ul>
        </div>
      </header>
      <div class="demo-drawer mdl-layout__drawer mdl-color--blue-grey-900 mdl-color-text--blue-grey-50">
        <header class="demo-drawer-header" style="position: relative;">
          <img src="images/user.jpg" class="demo-avatar">
	  <!-- PCGC -->
	  <!-- Dropdown selector to pick a project -->
	  <div style="position: absolute; right: 10px; top: 0px;" id="project-dropdown-section">
            <div class="demo-avatar-dropdown" style="z-index: 99;">
              <!-- <button id="projbtn" class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--icon"> -->
              <button id="projbtn" class="mdl-button mdl-js-button mdl-button--icon">
		<i class="material-icons" role="presentation">arrow_drop_down</i>
              </button>
              <!--<ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" for="projbtn">-->
              <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu" for="projbtn">
		<!-- <li class="mdl-menu__item clickable-project-name" id="projAAAA"> </li>
		<li class="mdl-menu__item clickable-project-name" id="projBBBB"> </li> -->
		<?php
		   // Add a menu item for each project
		   foreach ($sites as $site) {
		   echo ("<li class=\"mdl-menu__item clickable-project-name\" id=\"proj".$site."\">".strtoupper($site)."</li>");
		   }
		   ?>
              </ul>
	      <div>
		<h3 id="projname"></h3>
	      </div>
	    </div>
	  </div>
          <div class="demo-avatar-dropdown">
            <span>Data Views</span>
            <div class="mdl-layout-spacer"></div>
            <button id="accbtn" class="mdl-button mdl-js-button mdl-js-ripple-effect mdl-button--icon">
              <i class="material-icons" role="presentation">arrow_drop_down</i>
              <span class="visuallyhidden">Accounts</span>
            </button>
            <ul class="mdl-menu mdl-menu--bottom-right mdl-js-menu mdl-js-ripple-effect" for="accbtn">
              <li class="mdl-menu__item" id="load-subjects">Subjects</li>
              <li class="mdl-menu__item" id="load-studies">Studies</li>
<?php if ($seescanner) : ?>
              <li class="mdl-menu__item" id="load-scanner">Scanner</li>
<?php endif; ?>
            </ul>
          </div>
        </header>
        <nav class="demo-navigation  mdl-color--blue-grey-900 mdl-color-text--blue-grey-50">
           <center><span id="view-name"></span></center>
           <div class="mdl-layout-spacer"></div>
           <div class="" style="color: white; width: 100%;">
               <div class="mdh-expandable-search mdl-cell--hide-phone">
                   <i class="material-icons">search</i>
                   <form action="#">
                        <input id="search-list" type="text" placeholder="Search" size="1">
                   </form>
               </div>

               <!-- Displayed on mobile -->
               <div class="mdl-layout-spacer mdl-cell--hide-tablet mdl-cell--hide-desktop"></div>
                 <!-- Search button -->
                <button class="mdh-toggle-search mdl-button mdl-js-button mdl-button--icon mdl-cell--hide-tablet mdl-cell--hide-desktop">
                    <i class="material-icons">search</i>
                </button>
           </div>

        </nav>
        <nav id="list-of-subjects" class="demo-navigation mdl-navigation mdl-color--blue-grey-800">
           <div class="mdl-layout-spacer"></div>
          <a class="mdl-navigation__link" href=""><i class="mdl-color-text--blue-grey-400 material-icons" role="presentation">help_outline</i><span class="visuallyhidden">Help</span></a>
        </nav>
      </div>

      <main class="mdl-layout__content mdl-color--grey-100">
        <div class="mdl-grid demo-content">
          <div class="demo-charts mdl-color--white mdl-shadow--2dp mdl-cell mdl-cell--12-col mdl-grid">
            <div id="system-load" class="mdl-cell mdl-cell--2-col">
	      <div class="mdl-spinner mdl-js-spinner is-active" style="width: 110px; height: 110px;margin-left: 10px;"></div>
	    </div>
            <div id="system-space" class="mdl-cell mdl-cell--2-col"></div>
            <div id="system-memory" class="mdl-cell mdl-cell--2-col"></div>
            <!-- <div class="mdl-cell mdl-cell--2-col"></div> --><div class="mdl-layout-spacer"></div>
            <div class="mdl-cell mdl-cell--2-col minw">
<label for="receive-dicom" id="receive-dicom-label" class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect">
  <input type="checkbox" id="receive-dicom" class="mdl-checkbox__input" checked />
  <span class="mdl-checkbox__label">receive DICOM</span>
</label>

<label for="receive-mpps" id="receive-mpps-label" class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect">
  <input type="checkbox" id="receive-mpps" class="mdl-checkbox__input" checked disabled readonly/>
  <span class="mdl-checkbox__label">auto-pull DICOM</span>
</label>

<label for="anonymize" id="anonymize-label" class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect">
  <input type="checkbox" id="anonymize" class="mdl-checkbox__input" checked disabled readonly />
  <span class="mdl-checkbox__label">anonymize files</span>
</label >
            </div>
          </div>
          <div class="demo-graphs mdl-shadow--2dp mdl-color--white mdl-cell mdl-cell--12-col">
	      <div id="calendar-loc"></div>
          </div>
          <div class="demo-graphs mdl-shadow--2dp mdl-color--white mdl-cell mdl-cell--12-col" style="display: none;">
              <div style="position: relative;">
                <div style="font-size: 22pt; position: absolute; top: 20px;color: #757575; left: 10pt;">Studies present on this system</div>
	        <div id="circles"></div>
              </div>
          </div>
          <div class="demo-graphs mdl-shadow--2dp mdl-color--white mdl-cell mdl-cell--12-col" style="display: none;">
              <div style="position: relative;">
                <div style="font-size: 22pt; position: absolute; bottom: 20px;color: #757575; left: 10pt;">Studies present on this system</div>
	        <div id="bars"></div>
              </div>
          </div>
        </div>
      </main>
    </div>

<dialog class="mdl-dialog" id="modal-change-password">
    <div class="mdl-dialog__content">
        <div style="font-size: 32pt; margin-bottom: 25px;">
            Change Password
        </div>
	<form>
          <input class="mdl-textfield__input" type="password" autocomplete="new-password" id="password-field1" placeholder="*******" autofocus><br/>
          <input class="mdl-textfield__input" type="password" id="password-field2" autocomplete="new-password" placeholder="type again">
	</form>
    </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="change-password-cancel">Cancel</button>
        <button type="button" class="mdl-button" id="change-password-save" onclick="changePassword();">Save</button>
    </div>
</dialog>



<dialog class="mdl-dialog" id="modal-setup">
    <div class="mdl-dialog__content">
        <div style="font-size: 32pt; margin-bottom: 25px;">
            Configuration
        </div>
        <div id="setup-text">
loading configuration file...
        </div>
    </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="setup-dialog-cancel">Cancel</button>
        <button type="button" class="mdl-button" id="setup-dialog-save">Save</button>
    </div>
</dialog>

<dialog class="mdl-dialog" id="modal-clean-quarantine">
    <div class="mdl-dialog__content">
	<div style="position: absolute; right: 40px;">
	  <label for="show-suid-only">Show SUID entries only</label>
	  <input type="checkbox" id="show-suid-only">
	</div>
        <div style="font-size: 32pt; margin-bottom: 25px;">
            Quarantine Data
        </div>
        <div style="height: 500px; overflow-y: scroll;">
  	  <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
            <thead><tr>
	         <th>#Files</th>
		 <th>Action</th>
   	         <th style="mdl-data-table__cell--non-numeric">Name</th>
		 <th class="mdl-data-table__cell--non-numeric sort" data-sort="StudyDate">StudyDate</th>
                 <th>Size (MB)</th>
                 <th>parts pushed to DAIC as</th>
              </tr>
            </thead>
            <tbody id="cleanQuarantine">

            </tbody>
          </table>
          <div class="loading"><img style="position: absolute; top: 50%; left: 50%;" src="/images/loader.gif"></div>
        </div>
      </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="clean-quarantine-close">Close</button>
    </div>
</dialog>


<dialog class="mdl-dialog" id="modal-data-flow">
    <div class="mdl-dialog__content">
        <div style="font-size: 32pt; margin-bottom: 25px;">
            Data Flow
        </div>
	<div>
	  <p style="line-height: 1.2em;">Experimental visualization of the data flow for each study. Studies are represented by rectangular regions, where each study region is filled with rows of smaller squares that represent image series. The color of each individual image series square indicates its current state. States are ordered according to the data flow in columns of archive (red), raw (yellow), quarantine (green), outbox (blue), and DAIC (violet).</p>
        </div>
	<div id="data-flow-container" style="position: relative;"></div>
    </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="data-flow-dialog-cancel">ok</button>
    </div>
</dialog>


<dialog class="mdl-dialog" id="modal-kspace-status">
    <div class="mdl-dialog__content">
        <div style="font-size: 32pt; margin-bottom: 25px;">
           Problematic KSPACE Data:
        </div>
        <div>

        </div>
        <div id="kspace-status-container" style="position: relative;"></div>
          <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
            <thead><tr>
                 <th style="mdl-data-table__cell--non-numeric">Types</th>
                 <th class="mdl-data-table__cell--non-numeric">Study Name</th>
                 <th>Action</th>
              </tr>
            </thead>
            <tbody id="kspaceData">

            </tbody>
          </table>

    </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="data-kspace-dialog-cancel">ok</button>
    </div>
</dialog>

<dialog class="mdl-dialog" id="modal-mrs-status">
    <div class="mdl-dialog__content">
        <div style="font-size: 32pt; margin-bottom: 25px;">
           Problematic MRS Data:
        </div>
        <div>

        </div>
        <div id="kspace-status-container" style="position: relative;"></div>
          <table class="mdl-data-table mdl-js-data-table mdl-shadow--2dp">
            <thead><tr>
                 <th style="mdl-data-table__cell--non-numeric">Types</th>
                 <th class="mdl-data-table__cell--non-numeric">Study Name</th>
                 <th>Action</th>
              </tr>
            </thead>
            <tbody id="mrsData">

            </tbody>
          </table>

    </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="data-mrs-dialog-cancel">ok</button>
    </div>
</dialog>

<dialog class="mdl-dialog" id="modal-study-info">
  <div class="mdl-dialog__content">
    <div style="font-size: 32pt; margin-bottom: 20px;">
      Study Transfer
    </div>
    <div class="row">
      <div class="mdl-cell mdl-cell--12-col"  id="study-info-text"></div>
    </div>
    <div class="row">
      <div class="mdl-cell mdl-cell--12-col">
        <div id="header-section"></div>
      </div>
    </div>
    <div class="row">
      <div class="mdl-cell mdl-cell--12-col"  id="identify-section">
	<h4>Identify your imaging session</h4>

        <div class="form-group">
           <div class="mdl-cell mdl-cell--12-col"  id="imaging-info-text"></div>
        </div>
        <div class="form-group">
              <label for="session-participant" class="control-label" id="session-participant-label">Participant (PSCID_CANDID_VISIT)</label><br/>
              <select class="form-control select2-list" id="session-participant">
	         <option></option>
	      </select>
             <input class="form-control" id="new-session-age" hidden>
             <input class="form-control" id="new-session-sex" hidden>
             <input class="form-control" id="new-session-dob" hidden>
             <input class="form-control" id="modify-participant-name" value="0" hidden>
        </div>
           
        
        <div class="class="mdl-dialog__actions mdl-dialog__actions--full-width">
             <label for="session-participant" class="control-label" id="new-session-participant-label">New Participant (PSCID_CANDID_VISIT)</label><br/>
             <input class="form-control" id="new-session-participant">
        </div>
       	
       
        <div class="form-group">
          <label for="session-run" class="control-label" id="session-run-label">Imaging Session Type</label><br/>
          <select class="form-control" id="session-run">
            <option value="SessionC" title="A Complete Session: T2, T1, rsFMRI, DWI, b1map, qMRI and FieldMaps ">SessionC: A Complete Session (T2, T1, rsFMRI, DWI, b1map, qMRI and FieldMaps)</option>
            <option value="SessionA" title="Part 1 of multiple sessions: T1 or T2 plus others,">SessionA: Part 1 of multiple sessions (T1 or T2 plus others)</option>
            <option value="SessionB" title="Part 2 of multiple sessions: T1 or T2 plus others">SessionB: Part 2 of multiple sessions: (T1 or T2 plus others)</option>
            <option value="SessionD" title="Part 3 of multiple sessions: T1 or T2 plus others">SessionD: Part 3 of multiple sessions: (T1 or T2 plus others)</option>
            <option value="SessionPHANTOM" title="Phantom scan">P (Phantom scan)</option>
          </select>
        </div>
        
        <div class="row">
           <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
              <button type="button" class="mdl-button" id="imaging-info-dialog-cancel">Cancel Checking</button>
              <button type="button" class="mdl-button" id="imaging-info-recheck">Re-Check Imaging information</button>
            </div>
        </div>

    </div>    

    <div class="row">
      <div class="mdl-cell mdl-cell--12-col">
        <div id="detected-scans-summary"></div>
        <ul id="detected-scans">
        </ul>
      </div>
    </div>
    <div class="row">
      <div class="mdl-cell mdl-cell--12-col"  id="additional-scans"></div>
    </div>
    <div class="row">
      <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button" id="study-info-dialog-cancel">Cancel</button>
        <button type="button" class="mdl-button" id="study-info-dialog-sendall">Send All Series</button>
      </div>
    </div>
  </div>
</dialog>

<div class="messagepop pop">
   <div class="messagepop-title"></div>
   <div class="messagepop-content"></div>
<!--     <form method="post" id="new_message" action="/messages">
        <p><label for="email">Your email or name</label><input type="text" size="30" name="email" id="email" /></p>
        <p><label for="body">Message</label><textarea rows="6" name="body" id="body" cols="35"></textarea></p>
        <p><input type="submit" value="Send Message" name="commit" id="message_submit"/> or <a class="close" href="/">Cancel</a></p>
    </form> -->
</div>


<dialog class="mdl-dialog" id="modal-series-info">
    <div class="mdl-dialog__content">
        <div style="font-size: 32pt; margin-bottom: 20px;">
            Series Information
        </div>
        <div id="series-info-text">
loading information...
        </div>
    </div>
    <div class="mdl-dialog__actions">
        <button type="button" class="mdl-button" id="series-info-dialog-cancel">ok</button>
    </div>
</dialog>

<dialog class="mdl-dialog" id="modal-repush">
    <div class="mdl-dialog__content">
        <div style="font-size: 24pt; margin-bottom: 20px;">
            Recreate send buttons for this study
        </div>
        <div>
<p style="line-height: 1.1em;">Do you want to recreate the send buttons for this study?</p>
<p style="line-height: 1.1em;">Upon request from the central data repository you may use this functionality to re-create the data package for this participant. Once started this operation may take a long time - at most 1 hour depended on the load on the machine. Please be patient and wait until all the Send buttons appear again on the Study Transfer dialog before using the "Send all series" button.</p>
        </div>
    </div>
    <div class="mdl-dialog__actions">
        <button type="button" class="mdl-button" id="repush-cancel">cancel</button>
        <button type="button" class="mdl-button" id="repush-ok">ok</button>
    </div>
</dialog>

<dialog class="mdl-dialog" id="modal-about">
    <div class="mdl-dialog__content">
        <div style="font-size: 22pt; margin-bottom: 20px;">
            Flash-based Input/Output Network Appliance
        </div>
        <div>
          <p>
            Learn more about this project by visiting <a href="https://abcd-workspace.ucsd.edu">abcd-workspace.ucsd.edu</a>.
          </p>
        </div>
    </div>
    <div class="mdl-dialog__actions mdl-dialog__actions--full-width">
        <button type="button" class="mdl-button close-dialog">OK</button>
    </div>
</dialog>

<div id="message-window" class="mdl-shadow--2dp mdl-color--white" style="display: none;">
   <div id="message-window-header"></div>
   <div id="message-window-body" style="height: 60px; overflow-y: scroll;"></div>
</div>

      <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" style="position: fixed; left: -1000px; height: -1000px;">
        <defs>
          <mask id="piemask" maskContentUnits="objectBoundingBox">
            <circle cx=0.5 cy=0.5 r=0.49 fill="white"></circle>
            <circle cx=0.5 cy=0.5 r=0.40 fill="black"></circle>
          </mask>
          <g id="piechart">
            <circle cx=0.5 cy=0.5 r=0.5></circle>
            <path d="M 0.5 0.5 0.5 0 A 0.5 0.5 0 0 1 0.95 0.28 z" stroke="none" fill="rgba(255, 255, 255, 0.75)"></path>
          </g>
        </defs>
      </svg>
    <script src="js/dialog-polyfill.min.js"></script>
    <script src="js/material.min.js"></script>
    <script src="js/d3.js"></script>
    <script src="js/circles.js"></script>

    <!-- <script src="js/sankey.js"></script>
    <script src="js/d3.chart.min.js"></script>
    <script src="js/d3.chart.sankey.min.js"></script>    -->
    <script src="js/bars.js"></script>

    <script src="js/jquery-2.1.4.min.js"></script>
    <script src="js/select2.min.js"></script>
    <script src="js/wookmark.min.js"></script>
    <script src="js/radialProgress.js"></script>
    <script src="js/moment-with-locales.min.js"></script>
    <script src="js/fullcalendar.min.js"></script>
    <script src="js/ace-min-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="js/jquery.bonsai.js" type="text/javascript"></script>
    <script src="js/md5-min.js" type="text/javascript"></script>
    <script type="text/javascript">

     // PCGC
      // ABCD is the default project name
      var projname = project_name;

      // logout the current user
      function logout() {
        jQuery.get('/php/logout.php', function(data) {
          if (data == "success") {
            // user is logged out, reload this page
            location.reload();
          } else {
            alert('something went terribly wrong during logout: ' + data);
          }
        });
      }

      // change the current user's password
      function changePassword() {
        var password = jQuery('#password-field1').val();
        var password2 = jQuery('#password-field2').val();
        if (password == "") {
          alert("Error: Password cannot be empty.");
          return; // no empty passwords
        }
        // minimum password length
        if (password.length < 8) {
          alert("Error: Password has to be at least 8 characters in length.");
          return;
        }
        // user name should not be part of password
        if (password.toLowerCase().indexOf(user_name.toLowerCase()) != -1) {
          alert("Error: username should not be part of the password.");
          return;
        }
        if (! /[a-z]+/.test(password) ) {
          alert("Error: password should contain lower-case characters a-z");
          return;
        }
        if (! /[A-Z]+/.test(password) ) {
          alert("Error: password should contain upper case characters A-Z");
          return;
        }
        if (! /[0-9]+/.test(password) ) {
          alert("Error: password should contain number 0-9");
          return;
        }
        if (! /[\! @#*+-:.,\?\^_\/\\\s]+/.test(password) ) {
          alert("Error: password should contain punctuation or non-alphanumeric character");
          return;
        }

       hash = hex_md5(password);
        hash2 = hex_md5(password2);
        if (hash !== hash2) {
          alert("Error: The two passwords are not the same, please type again.");
          return; // do nothing
        }
        jQuery.getJSON('/php/getUser.php?action=changePassword&value=' + user_name + '&value2=' + hash, function(data) {
            alert("Success: you have changed the password for user " + user_name);
        }).fail(function() {
            alert("Error: an error was returned trying to set your new password (" + user_name + ")");
        });
      }



// make sure that we list if a scan has been send previously (when and under what pGUID)
// do this for all participants in the open-study-info that are currently visible on screen, cache the results
var sendInformationCache = {}; // use study instance uid as key in this cache
var sendInformationQueue = [];
function updateSendInformation() {
    // get all currently visible entries
    function checkVisible(elm) {
        var style = window.getComputedStyle(elm);
        if (style.display === 'none')
            return false;
        var rect = elm.getBoundingClientRect();
        var viewHeight = Math.max(document.documentElement.clientHeight, window.innerHeight);
        return !(rect.bottom < 0 || rect.top - viewHeight >= 0);
    }
    // todo: we get this call a lot, each time there we get values for another series. Would be better to delay the render - not a big issue
    function renderResult(studyinstanceuid) {
        if (sendInformationCache[studyinstanceuid].status == "transferred") {
            // find the right entry on screen
            var a = null;
            jQuery('#list-of-subjects').children().each(function(i,v) {
                if (jQuery(v).attr('studyinstanceuid') == studyinstanceuid) {
                    a = v;
                }
            });
            if (a !== null) {
            jQuery(a).find('a i.unknown-type').removeClass('unknown-type').addClass('transferred-type');
                    jQuery(a).find('a div.detail-information').remove();
                    jQuery(a).find('a').append("<div class='detail-information'>" + sendInformationCache[studyinstanceuid].event + " " + sendInformationCache[studyinstanceuid].pGUID + " (" + sendInformationCache[studyinstanceuid].date.format('MMM Do YYYY') + ")" );
            }
        }
    }
  jQuery.each(jQuery('.open-study-info'), function(i,a) {
        if (checkVisible(jQuery(a)[0])) {
            var studyinstanceuid = jQuery(a).attr('studyinstanceuid');
            // do we have an entry in the cache for this already?
            if (typeof sendInformationCache[studyinstanceuid] == 'undefined') {
                // we will get a value for this at some point (don't request this a second time)
                sendInformationCache[studyinstanceuid] = { 'status': 'fileNotFound' };

                // get the data for this studyinstanceuid
                (function(studyinstanceuid) { // make studyinstanceuid a local variable in the scope
                    var options = {
                        "action": "getStudy",
                        "study": studyinstanceuid,
                        "project": projname,
                        "skipComplianceReRun": 1
                    };
                    // get a list of the series for this study
                    jQuery.getJSON('/php/existingData.php', options, function(data) {
                        dataSec1 = {};
                        dataSec2 = {};
                        dataSec3 = {};
                        var keys = Object.keys(data);
                        for (var i = 0; i < keys.length; i++) {
                            var value = data[keys[i]];
                            if (Array.isArray(value)) {
                                dataSec3[keys[i]] = value;
                            } else if (typeof value === 'object') {
                                dataSec2[keys[i]] = value;
                            } else if (typeof value === 'string') {
                                dataSec1[keys[i]] = value;
                            } /* else {
                                alert("Error: No existing data, perhaps protocol compliance check was not run.");
                            } */
                        }
                        // we are looking for the data in dataSec2+dataSec3 (series that have been detected)
                        // we need to call fileStatus.php for them to find out which once are in DAIC
                        var allFilePaths = [];
                        for (var k in dataSec2) {
                            if (dataSec2[k] === null || typeof dataSec2[k]['file'] == 'undefined') {
                                // if there is no 'file' it can still be in a key one down
                                var keys = Object.keys(dataSec2[k]);
                                for (var j = 0; j < keys.length; j++) {
                                    if (typeof dataSec2[k][keys[j]]['file'] !== 'undefined') {
                                        for (var i = 0; i < dataSec2[k][keys[j]]['file'].length; i++) {
                                            if (typeof dataSec2[k][keys[j]]['file'][i]['path'] !== 'undefined' && dataSec2[k][keys[j]]['file'][i]['path'] !== "") {
                                                allFilePaths.push(dataSec2[k][keys[j]]['file'][i]['path']);
                                            }
                                        }
                                    }
                                }
                                continue;
                            }
                            for (var i = 0; i < dataSec2[k]['file'].length; i++) {
                                if (typeof dataSec2[k]['file'][i]['path'] !== 'undefined' && dataSec2[k]['file'][i]['path'] !== "") {
                                    allFilePaths.push(dataSec2[k]['file'][i]['path']);
                                }
                            }
                        }
                        if ( allFilePaths.length == 0 ) {
                            // don't ask for this studyinstanceuid again
                            sendInformationCache[studyinstanceuid] = { 'status': 'fileNotFound' };
                        }
                        for (var i = 0; i < allFilePaths.length; i++) {
                            filePath = allFilePaths[i]; // for each file path search and return if its transferred, in transit, or readyToSend, if its transferred extract the pGUID and date
                            jQuery.getJSON('/php/fileStatus.php?filename=' + filePath + '&project='+projname, (function(studyinstanceuid) {
                                // return a function that knows about our series Instance UID variable
                                return function(data) {
                                    // now add the information returned to the sendInformationCache[studyinstanceuid]
                                    // we get back data here that looks like this:
                                    //      $val[] = array( "ok" => 1, "message" => "readyToSend", "filename" => $qv[0], "filemtime" => $qv[1] );
                                    //      $val[] = array( "ok" => 1, "message" => "transit", "filename" => $qv[0], "filemtime" => $qv[1] );
                                    //      $val[] = array( "ok" => 1, "message" => "transferred", "filename" => $qv[0], "filemtime" => $qv[1] );
                                    if (data.length == 0) {
                                        sendInformationCache[studyinstanceuid] = { 'status': 'fileNotFound' };
                                        return;
                                    }
                                    var sendAsGUID = "";
                                    var sendWhen = "";
                                    var sendWhat = "";
                                    var sendEvent = "";
                                    for (var i = 0; i < data.length; i++) {
                                        // find the newest transferred dataset
                                        if (data[i].message == "transferred") {
                                            // found one!
                                            if (sendWhen == "") { // first time
                                                sendWhen   = data[i].filemtime;
                                                if (data[i].filename.split("/")[3].split("_")[0] == "NDAR") {
                                                    sendAsGUID = data[i].filename.split("/")[3].split("_").slice(0,2).join("_");
                                                    sendEvent = data[i].filename.split("/")[3].split("_").slice(2,4).join("_");
                                                } else {
                                                    sendAsGUID = data[i].filename.split("/")[3].split("_").slice(0,1);
                                                    sendEvent = data[i].filename.split("/")[3].split("_").slice(1,3).join("_");
                                                }
                                                sendWhat   = data[i].filename;
                                            } else {
                                                // not the first time, is this a later date? Use the latest data for all send operations ( January 21 2018 14:45:10 )
                                                var date1 = moment(sendWhen, 'MMMM D YYYY H:m:s');
                                                var date2 = moment(data[i].filemtime, 'MMMM D YYYY H:m:s');
                                                if ( moment().diff(date1, 'minutes') > moment().diff(date2, 'minutes') ) {
                                                    sendWhen   = data[i].filemtime;
                                                    if (data[i].filename.split("/")[3].split("_")[0] == "NDAR") {
                                                        sendAsGUID = data[i].filename.split("/")[3].split("_").slice(0,2).join("_");
                                                        sendEvent = data[i].filename.split("/")[3].split("_").slice(2,4).join("_");
                                                    } else {
                                                        sendAsGUID = data[i].filename.split("/")[3].split("_").slice(0,1);
                                                        sendEvent = data[i].filename.split("/")[3].split("_").slice(1,3).join("_");
                                                    }

                                                    sendWhat   = data[i].filename;
                                                } // otherwise we have an old send for this study
                                            }
                                        }
                                    }
                                    if (sendWhen !== "") {
                                        var date = moment(sendWhen, 'MMMM D YYYY H:m:s');
                                        sendInformationCache[studyinstanceuid] = { 'status': 'transferred', 'pGUID': sendAsGUID, 'date': date, 'filename': sendWhat, 'event': sendEvent };
                                        renderResult(studyinstanceuid);
                                    }
                                };
                            })(studyinstanceuid) );
                        }
                    });
                })(studyinstanceuid);
            } else {
                // if we have an entry already show it
                renderResult(studyinstanceuid);
            }
        }
    });

}



var subjectData = [];
var scrollTimer, lastScrollFireTime = 0; // for handling the scroll event (throdle)


function loadSubjects() {
    //
    // hidepattern
    //
    console.log("loadSubjects");
    jQuery('#list-of-subjects').find('.data').remove();
    jQuery('#view-name').text('Subjects');
    // PCGC
    jQuery.getJSON('/php/subjects.php', {'project': projname }, function(data) {

         // we should re-sort the participants list and have them first sorted by most recent and secondly by name
         // use the initial sorting order for the participant names
         // color the fields that belong together
         var lightTag = false;
         for (var i = data.length-1; i > 0; i--) {
             lightTag = !lightTag;
             var name1 = data[i].PatientName;
             var name2 = data[i].PatientID;
             data[i].lightTag = (lightTag?"1":"0");
             if ((name1+name2).toLowerCase().indexOf('phantom') > -1 || (name1+name2).toLowerCase().indexOf('geservice') > -1 ||
                 (name1+name2).toLowerCase().indexOf('technical') > -1 ||
                 (name1+name2).toLowerCase().indexOf('qa') > -1 || (name1+name2).toLowerCase().indexOf('test') > -1)
                 continue; // don't resort Phantom scans

             // get the indices for the same participant and add them here
             var count = 0;
             for (var j = i-1; j >= 0; j--) {
                 if ((data[j].PatientName.length > 0 && data[j].PatientName == name1) || (data[j].PatientID.length > 0 && data[j].PatientID == name2)) {
                     var tmp = data.splice(j,1);
                     tmp[0].lightTag = (lightTag?"1":"0");
                     data.splice(i-1, 0, tmp[0]);
                     count++;
                 }
             }
             i = i - count;
         }

        subjectData = data; // we can re-use those
            for (var i = 0; i < data.length; i++) {
            var shortname = data[i].PatientName + "-" + data[i].PatientID;
                shortname = shortenName( shortname );
                // if we have a hidepattern use it to not display some participants
//console.log("hidepattern: " + hidepattern);
                if (typeof hidepattern !== "undefined") {
                   if (shortname.match(new RegExp(hidepattern)) !== null) {
//console.log("filter out " + shortname);
                      continue; // skip this item
                   }
                }

                jQuery('#list-of-subjects').prepend('<div class="data open-study-info tag-' + data[i].lightTag + '" style="position: relative;" studyinstanceuid="'+data[i].StudyInstanceUID+'"><a class="mdl-navigation__link" href="#" title=\"' + data[i].PatientName + '-' + data[i].PatientID + '\"><i class="mdl-color-text--blue-grey-400 material-icons unknown-type" role="presentation">accessibility</i><div class="scan-date">scan date: ' + data[i].StudyDate.replace( /(\d{4})(\d{2})(\d{2})/, "$2/$3/$1") + ' ' + data[i].StudyTime.split('.')[0].replace(/(.{2})/g,":$1").slice(1) + '</div><div class="mono" style="position: absolute; bottom: 30px; right: 10px;">'+shortname+'</div></a></div>');
            }

        // if an element is in view get the detailed information for the last send for it
        updateSendInformation();

            jQuery('.demo-drawer').on("scroll", function() {
                var minScrollTime = 200;
                var now = new Date().getTime();

                if (!scrollTimer) {
                        if (now - lastScrollFireTime > (3 * minScrollTime)) {
                            // processScroll();   // fire immediately on first scroll
                            updateSendInformation();
                            lastScrollFireTime = now;
                        }
                        scrollTimer = setTimeout(function() {
                            scrollTimer = null;
                            lastScrollFireTime = new Date().getTime();
                            // processScroll();
                            updateSendInformation();
                        }, minScrollTime);
                }
            });
        jQuery(window).on('resize', function() {
                    updateSendInformation();
        });
            // also on search we need to updateSendInformation
    });
}

function shortenName( name ) {
   var l = 21;
   if (name !== null && name.length > l) {
       // take the first part
       return name.substring(0,16) + "..." + name.substring(name.length -5, name.length);
   }
   return name;
}

var studyData = [];
function loadStudies() {
    jQuery('#list-of-subjects').find('.data').remove();
    jQuery('#view-name').text("Studies");
    // PCGC
    jQuery.getJSON('/php/series.php', {'project': projname }, function(data) {
        studyData = data;
        // sort those by date

        // here we get all series below each study, need a tree view, get rid of stuff already on display
        // create a nested list for bonsai
        str = "<ul id=\"study-list-bonsai\" class=\"data\">";
        var studies = Object.keys(data);
        for (var i = 0; i < studies.length; i++) {
            if (typeof data[studies[i]][0] === 'undefined' || data[studies[i]][0] === null) {
               continue;
            }
            str = str + "<li title=\""+data[studies[i]][0]['PatientName']+"\">" + data[studies[i]][0]['PatientName'] + "-" + data[studies[i]][0]['PatientID'] + "<ul>";
            var seriesList = data[studies[i]];
            seriesList.sort(function(a,b) {
                if ( parseInt(a['SeriesNumber']) == parseInt(b['SeriesNumber'])) {
                    return 0;
                }
                if ( parseInt(a['SeriesNumber']) < parseInt(b['SeriesNumber'])) {
                    return -1;
                }
                return 1;
            });
            for (var j = 0; j < seriesList.length; j++) {
                str = str + "<li class=\"open-series-info\" key=\"" + studies[i] + "\" entry=\""+ j +"\" title=\""+ seriesList[j]['SeriesDescription'] + "\">" + seriesList[j]['SeriesNumber'] + " " + shortenName(seriesList[j]['SeriesDescription']) + "</li>";
            }
            str = str + "</ul></li>";
        }
        str = str + "</ul>";
        jQuery('#list-of-subjects').prepend(str);
        jQuery('#study-list-bonsai').bonsai();
    });
}



// load the list of scans from the scanner
function loadScanner() {
    jQuery('#list-of-subjects').find('.data').remove();
    jQuery('#view-name').text("Scanner");
    // PCGC
    jQuery.getJSON('/php/scanner.php', {'project': projname }, function(data) {
        str = "<ul id=\"scanner-list-bonsai\" class=\"data\">";
        for (var i = 0; i < data.length; i++) {
           var na = data[i].PatientName + "-" + data[i].PatientID;
           na = shortenName(na);
           str = str + "<li><span title=\"" + data[i].PatientName + "-" + data[i].PatientID + "\">" + na + "</span><br/><small style=\"padding-top: -10px;\">"+data[i].StudyDate+" "+data[i].StudyTime+"</small>&nbsp;&nbsp;<button style=\"margin-top: -20px;\" class=\"mdl-button mdl-js-button mdl-button--icon pull-study\" study=\""+data[i].StudyInstanceUID+"\" title=\"Downlaod to FIONA\"><i class=\"material-icons\">touch_app</i></button><ul>";
           for (var j = 0; j < data[i].Series.length; j++) {
              str = str + "<li class=\"open-scanner-series-info\" key=\"" + data[i].Series[j].SeriesInstanceUID + "\" title=\""+data[i].Series[j].SeriesDescription + " [" + data[i].Series[j].ImagesInAcquisition +"]\">" + shortenName(data[i].Series[j].SeriesDescription) + "</li>";
           }
           str = str + "</ul></li>";
        }
        str = str + "</ul>";
        jQuery('#list-of-subjects').prepend(str);
        jQuery('#scanner-list-bonsai').bonsai();
    });
}


var rp1 = "";
var rp2 = "";
var rp3 = "";


function loadSystem() {
    //jQuery('#system-load').children().remove();
    jQuery.getJSON('/php/stats.php', function(data) {
        jQuery('.hostname').text(data.hostname);
        //var load=d3.select(document.getElementById('system-load'));
        if (rp1 == "") {
            jQuery('#system-load').children().remove();
            rp1 = radialProgress(document.getElementById('system-load'))
                .label("load")
                .diameter(150)
                .value(data.load_avg * 100)
                .render();
        } else {
            rp1.value(data.load_avg * 100).render();
        }
        if (rp2 == "") {
            jQuery('#system-space').children().remove();
            rp2 = radialProgress(document.getElementById('system-space'))
                .label("space")
                .diameter(150)
                .value(100-data.disk_free_percent)
                .render();
        } else {
            rp2.value(100-data.disk_free_percent).render();
        }
        if (rp3 == "") {
            jQuery('#system-memory').children().remove();
            rp3 = radialProgress(document.getElementById('system-memory'))
                .label("memory")
                .diameter(150)
                .value(100-data.memory_free_percent)
                .render();
        } else {
            rp3.value(100-data.memory_free_percent).render();
        }
    });
    jQuery.get('/php/startstop.php?project=' + projname, function(data) {
        console.log('change checked to reflect system status ' + data);
        // we expect two values here
        var vals = data.split('');
        if (vals[0] == "0") {
           document.querySelector('#receive-dicom-label').MaterialCheckbox.uncheck();
        } else {
           document.querySelector('#receive-dicom-label').MaterialCheckbox.check();
        }
        if (vals[1] == "0") {
           document.querySelector('#receive-mpps-label').MaterialCheckbox.uncheck();
        } else {
           document.querySelector('#receive-mpps-label').MaterialCheckbox.check();
        }
        //if (vals[2] == "0") {
        //   document.querySelector('#anonymize-label').MaterialCheckbox.uncheck();
        //} else {
        //   document.querySelector('#anonymize-label').MaterialCheckbox.check();
       // }
    });
    //jQuery('#calendar-loc').fullCalendar('refetchEvents');
}

function setTimeline(view) {
    var parentDiv = jQuery(".fc-time-grid-container:visible").parent();
    var timeline = parentDiv.children(".timeline");
    if (timeline.length == 0) { //if timeline isn't there, add it
        timeline = jQuery("<hr>").addClass("timeline");
        parentDiv.prepend(timeline);
    }

    var curTime = new Date();

    var curCalView = jQuery("#calendar-loc").fullCalendar('getView');
    if (curCalView.intervalStart < curTime && curCalView.intervalEnd > curTime) {
        timeline.show();
    } else {
        timeline.hide();
        return;
    }

    var curSeconds = (curTime.getHours() * 60 * 60) + (curTime.getMinutes() * 60) + curTime.getSeconds();
    var percentOfDay = curSeconds / 86400; //24 * 60 * 60 = 86400, # of seconds in a day
    var topLoc = Math.floor(parentDiv.height() * percentOfDay);

    timeline.css("top", topLoc + "px");

    if (curCalView.name == "agendaWeek") { //week view, don't want the timeline to go the whole way across
        var dayCol = jQuery(".fc-today:visible");
        var left = dayCol.position().left + 1;
        var width = dayCol.width() - 2;
        timeline.css({
            left: left + "px",
            width: width + "px"
        });
    }

}



function createCalendar() {
    jQuery('#calendar-loc').fullCalendar('destroy');
    var cal = jQuery('#calendar-loc').fullCalendar({
        header: {
            left: 'prev,next today',
            center: 'title',
            right: 'month,agendaWeek,agendaDay'
        },
        defaultView: 'month', // only month is working here, would be good to switch to agendaDay instead
        timezone: 'America/Los_Angeles',
        eventSources: [ { url: "php/events.php", data: { project: projname }, color: '#dddddd', textColor: 'black' } ],
        eventResize: function(calEvent, jsEvent, view) {
            alert("eventResize: function(calEvent, jsEvent, view)");
            if (!updateEvent(calEvent)) {
                jQuery('#calendar-loc').fullCalendar('refetchEvents');
            }
        },
        viewRender: function(view) {
           console.log("ViewRender for calendar called");
           try {
              //setTimeline(view);
           } catch( err ) {}
        },
        eventAfterRender: function(event, element, view) {
            var title = event['title'];
            var colored = false;
            // change the background based on the type of event
            m = title.match(/NDAR_INV[A-Z0-9][A-Z0-9][A-Z0-9][A-Z0-9][A-Z0-9][A-Z0-9][A-Z0-9][A-Z0-9]/);
            if (m !== null) {
                jQuery(element).css('background-color', '#a1d99b');
                colored = true;
            }
            m = title.match(/ABCDPhantom/);
            if (m !== null) {
                jQuery(element).css('background-color', '#a6bddb');
                colored = true;
            }
            if (!colored) {
                jQuery(element).css('background-color', '#fff7bc');
            }
        },
    });

}


function changeSystemStatus() {
   var a = jQuery('#receive-dicom')[0].checked ? 1:0;
   var b = jQuery('#receive-mpps')[0].checked ? 1:0;
   var c = jQuery('#anonymize')[0].checked ? 1:0;
   jQuery.get('/php/startstop.php?project='+projname+'&enable='+a+""+b+""+c);
}

function displayHeaderSection(data) {

         if (data["status"] == null) {
             console.log("ERROR: displayHeaderSection: status not found");
         }

         if (data["message"] == null) {
             console.log("ERROR: displayHeaderSection: message not found");
         }

         if (data["shortmessage"] == null) {
             console.log("ERROR: displayHeaderSection: shortmessage not found");
         }

         if (data["PatientID"] == null) {
             console.log("ERROR: displayHeaderSection: PatientID not found");
         }

         if (data["PatientName"] == null) {
             console.log("ERROR: displayHeaderSection: PatientName not found");
         }

         if (data["PatientSex"] == null) {
             console.log("ERROR: displayHeaderSection: PatientSex not found");
         }

         if (data["StudyDate"] == null) {
             console.log("ERROR: displayHeaderSection: StudyDate not found");
         }

         if (data["StudyTime"] == null) {
             console.log("ERROR: displayHeaderSection: StudyTime not found");
         }

         if (data["StudyInstanceUID"] == null) {
             console.log("ERROR: displayHeaderSection: StudyInstanceUID not found");
         }

         if (data["Manufacturer"] == null) {
             console.log("ERROR: displayHeaderSection: Manufacturer not found");
         }

         if (data["ManufacturerModelName"] == null) {
             console.log("ERROR: displayHeaderSection: ManufacturerModelName not found");
         }

         jQuery('#study-info-text').text(JSON.stringify(data["message"]));
         if (data["status"] == 1) {
             jQuery('#study-info-text').css({'background-color':'lightgreen'});
         } else {
             jQuery('#study-info-text').css({'background-color':'PaleVioletRed'});
         }

         jQuery('#header-section').children().remove();
         var str = "";
         str = str.concat("<li class=\"status"+data["status"]+"\">");
         str = str.concat("<div class='SeriesName' title='Study information entered at the scanner.'>Study Information</div>");
         if (data["shortmessage"] != "")
            str = str.concat("<div class='shortmessage'>Short Message: " + data["shortmessage"] + "</div>");
         str = str.concat("<div class='PatientID'>Patient ID: " + data["PatientID"] + "</div>");
         str = str.concat("<div class='PatientName'>Patient Name: " + data["PatientName"] + "</div>");
         str = str.concat("<div class='PatientSex'>Patient Sex: " + data["PatientSex"] + "</div>");
         str = str.concat("<div class='StudyDate'>Study Date: " + data["StudyDate"] + "</div>");
         str = str.concat("<div class='StudyTime'>Study Time: " + data["StudyTime"] + "</div>");
         str = str.concat("<div class='StudyInstanceUID'>Study Instance UID: " + data["StudyInstanceUID"] + "</div>");
         str = str.concat("<div class='Manufacturer'>Manufacturer: " + data["Manufacturer"] + "</div>");
         str = str.concat("<div class='ManufacturerModelName'>Manufacturer Model Name: " + data["ManufacturerModelName"] + "</div>");
         str = str.concat("</li>");
         str = str.concat("");
         jQuery('#header-section').append(str);
}


function displayDetectedScans(data, StudyInstanceUID) {

         var keys = Object.keys(data);
         console.log("displayDetectedScans: keys: " + keys);
         jQuery('#detected-scans').children().remove();

         var str = "<ul>";
         for (var i = 0; i < keys.length; i++) {
             var value = data[keys[i]];

             // check if this is a series or a block of series
             if (typeof value !== 'undefined' && value !== null && value["file"] == null) {

                 // this JSON object does not contain the "file" field, so it must be a block
                 // iterate through the JSON objects contained within this block.
                 var keys2 = Object.keys(value);
                 console.log("displayDetectedScans: keys2: " + keys2);
                 console.log("displayDetectedScans: status value: " + value["status"]);
                 str = str.concat("<li class=\"status" + value["status"] + "\">");
                 str = str.concat("<div class='SeriesName'>" + keys[i] + "</div>");
                 str = str.concat("<div class='message'>" + value["message"] + "</div>");
                 str = str.concat("</li>");
                 str = str.concat("");

                 str = str.concat("<ul>");
                 for (var j = 0; j < keys2.length; j++) {
                     var value2 = value[keys2[j]];
                     if (value2 !== null && typeof value2["file"] !== "undefined") {
                         // found a series inside a block
                         str = str.concat(displaySeries(value2, keys2[j], StudyInstanceUID));
                     }
                 }
                 str = str.concat("</ul>");

             } else {
                 // SeriesNumber found, this is a series
                 str = str.concat(displaySeries(value, keys[i], StudyInstanceUID));
             }
         }
         str = str.concat("</ul>");
         jQuery('#detected-scans').append(str);
}

function displaySeries(series, seriesName, StudyInstanceUID) {

         if (series === null || series["status"] === null) {
             console.log("ERROR: displaySeries: status not found");
             return;
         }

         if (series["SeriesNumber"] == null) {
             console.log("ERROR: displaySeries: SeriesNumber not found");
         }

         if (series["SeriesInstanceUID"] == null) {
             console.log("ERROR: displaySeries: SeriesInstanceUID not found");
         }

         //if (series["SeriesInstanceUID"] == null) {
         //    console.log("ERROR: displaySeries: SeriesInstanceUID not found");
         //}

         if (series["message"] == null) {
             console.log("ERROR: displaySeries: message not found");
         }

         // status workflow
         // acquired  (not in /quarantine)
         // readyToSend (in /quarantine)
         // transit (in /outbox)
         // transferred (in /DAIC)

         var transferStatus = "acquired";
         var filePath = null;
         if (series["file"] == null) {
             console.log("ERROR: displaySeries: file not found");
         } else {
             console.log(series);
             if (series["file"][0] == null ) {
                 console.log("ERROR: displaySeries: file not found");
             } else {
                 filePath = series["file"][0]["path"];
                 transferStatus = filePath.substring(0,filePath.lastIndexOf("/")+1);
                 console.log("GOT transferStatus as : " + transferStatus + " from : " + filePath);
             }
         }

        var str = "";
        console.log("displaySeries: status value: " + series["status"]);
         str = str.concat("<li class=\"status"+series["status"]+"\">");
         str = str.concat("<div class='SeriesName'>" + seriesName + "</div>");
         str = str.concat("<div class='message'><p>" + series["message"] + "</p></div>");
         var id = "transferStatus"+createUUID();
         str = str.concat("<div id=\""+id+"\" class='TransferStatus'>TransferStatus: " + transferStatus + "</div>");
         if (typeof series["SeriesNumber"] != 'undefined') {
           str = str.concat("<div class='SeriesNumber'>SeriesNumber: " + (series["SeriesNumber"]==null?"":series["SeriesNumber"]) + "</div>");
         }
console.log('transferStatus: ' + transferStatus);
//         if (transferStatus == "/quarantine/" && project_name == "ABCD") { // this might be ok for ABCD, but not for other projects
//             str = str.concat("<button type='button' class='mdl-button send-series-button mdl-js-button mdl-button--raised pull-right' filename=\"" + filePath + "\" StudyInstanceUID =" + StudyInstanceUID + " SeriesInstanceUID=" + series['SeriesInstanceUID'] + ">Send</button></div>");
  //       }
         str = str.concat("</li>");
         str = str.concat("");
         //jQuery('#detected-scans').append(str);

         // update transfer status based on what fileStatus.php returns for this series (acquired, readytosend, transit, transfer)
         console.log(" display Series filepath :" + filePath);
         jQuery.getJSON('/php/fileStatus.php?filename=' + filePath + '&project='+projname, (function(ids) {
             // return a function that knows about our series Instance UID variable
             return function(data) {
                 if (data.length == 0) {
                     jQuery('#'+id).text("TransferStatus: FILE_NOT_FOUND_ERROR");
                 }
                 for (var i = 0; i < data.length; i++) {
                     console.log("series instance uid: " + id + "  " + data[i].message + " " + data[i].filename);
                     var fname = data[i].filename.replace(/^.*[\\\/]/, '').split("_");
                     if (fname.length > 2)
                         fname = fname.slice(0,2).join("_");
                     else
                         fname = "unknown";
                     jQuery('#'+id).html("TransferStatus: " + data[i].message + " <span title=\"" + data[i].filename + "\" >as " + fname + " (path, " + data[i].filemtime + ")</span>");
                     // here we would also need to add the button - if it does not exist yet
                     console.log("Display Series:  TransferStatus: " + data[i].message + " filename as " + data[i].filename  + " filemTime as : " + data[i].filemtime );
                     if (data[i].message == "readyToSend" && jQuery('#'+id).parent().find('button').length === 0 ) {
                         //jQuery('#'+id).parent().append("Should show send button here " + data[i].filename);
                         jQuery('#'+id).parent().append("<button type='button' class='mdl-button send-series-button mdl-js-button mdl-button--raised pull-right' filename=\"" + data[i].filename + "\" StudyInstanceUID =" + StudyInstanceUID + " SeriesInstanceUID=" + series['SeriesInstanceUID'] + ">Send</button>");
                     }
                 }
             };
           })(id)
         );
         return str;
}


function createUUID() {
    // http://www.ietf.org/rfc/rfc4122.txt
    var s = [];
    var hexDigits = "0123456789abcdef";
    for (var i = 0; i < 36; i++) {
        s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
    }
    s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
    s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
    s[8] = s[13] = s[18] = s[23] = "-";

    var uuid = s.join("");
    return uuid;
}



function displayAdditionalScans(data, StudyInstanceUID) {

         var str = "";
         str = str.concat("<li>");
         str = str.concat("<div class='SeriesName'>Additional Series</div>");
         str = str.concat("</li>");
         str = str.concat("");

         var item;
         if (typeof data["AdditionalSeries"] == 'undefined') {
                 return;
         }
         var array = data["AdditionalSeries"];
         for (var i = 0; i < array.length; i++) {
             var item = array[i];
             console.log("item[ClassifyType]: " + JSON.stringify(item["ClassifyType"]));
             var classifyType = item["ClassifyType"];
             var seriesName = "ClassifyType: " + JSON.stringify(classifyType);
             str = str.concat(displaySeries(item, seriesName, StudyInstanceUID));
         }
         jQuery('#detected-scans').append(str);
     }


function getSessionNamesFromREDCap(patientname, studydate) {
        jQuery.getJSON('/php/getLorisEvents.php?project=' + projname, function(data) {
console.log(data)
       
        getParticipantNamesFromLoris(patientname, studydate);
    });
}
var no_match = false;

function getParticipantNamesFromLoris(patientname, studydate) {
    year =  studydate.slice(0,4);
    month = studydate.slice(4,6);
    day = studydate.substr(6,7);
    console.log('year='+ year +' month='+ month+ ' day='+ day );
    studydateNew = year+"-"+month+"-"+day;
    console.log('i='+ patientname+ ' s='+ studydateNew );
    //convert studydate from 20220916 to 2022-09-16
    jQuery.getJSON('/php/getParticipantNamesFromLoris.php?i='+patientname+ '&s='+ studydateNew, function(data) {


console.log("getParticipantNamesFromLoris **** "+projname);
console.log(data);
        //for (var i = 0; i < data.length; i++) {
        //    val = "";
        //    if (i == 0) {
                val = "selected=\"selected\"";
        //    }
        jQuery('#session-participant').append("<option " + val + " value=\"" + data[0] + "\">" + data[0] + "</option>");
        jQuery('#session-participant').value = data[0] 
        // }
        

console.log(data[0]);
        
        if((!data[0]) || (data[0] === 0) || data[0][0].includes("Unknown") == true || data[0][0].includes("Invalid") == true || data[0][0].includes("LORIS") == true 
           || data[0][0].includes("Incomplete") == true || data[0].includes("does not match") == true 
           || data[0][0].includes("PSCID_DCCID_VISIT") == true || data[0].includes("LORIS") == true) {
            no_match = true;          
            
            jQuery('#imaging-info-text').text(JSON.stringify(data[0]) + "Please review the information and re-enter the correct" );
            jQuery('#imaging-info-text').css({'background-color':'PaleVioletRed'});
            jQuery('#session-participant').hide();
            jQuery('#session-run').hide();
            jQuery('#session-participant-label').hide();
            jQuery('#session-run-label').hide();

            jQuery('#new-session-participant-label').show();
            jQuery('#new-session-participant').show();
            jQuery('#imaging-info-dialog-cancel').show();
            jQuery('#imaging-info-recheck').show();
            
            jQuery('#study-info-dialog-sendall').attr("disabled", true);
            jQuery('#session-name').hide();
            jQuery('#session-name-label').hide();
            
            jQuery('#imaging-info-text').text(JSON.stringify(data[0]));
            jQuery('#imaging-info-text').css({'background-color':'lightred'});
            

        } else {
            jQuery('#imaging-info-text').text(JSON.stringify("A match is found in PII/LORIS database!"));
            jQuery('#imaging-info-text').css({'background-color':'lightgreen'});
            jQuery('#new-session-participant-label').hide();
            jQuery('#new-session-participant').hide();
            jQuery('#imaging-info-dialog-cancel').hide();
            jQuery('#imaging-info-recheck').hide();
       
            jQuery('#session-participant').show();
            jQuery('#session-name').show();
            jQuery('#session-run').show();
            jQuery('#session-participant-label').show();
            jQuery('#session-name-label').show();
            jQuery('#session-run-label').show();
console.log(data[1]);
   
            // data[1] contains sex_dob_age as following "M_2022-10-13_044W"
            const lorisArray = data[1].split("_"); 
            sex_loris = lorisArray[0];
            age_loris = lorisArray[2];
            dob_loris = lorisArray[1];
            jQuery('#new-session-age').val(age_loris);
            jQuery('#new-session-sex').val(sex_loris);
            jQuery('#new-session-dob').val(dob_loris);

            	console.log(jQuery('#modify-participant-name').val());
		console.log(jQuery('#new-session-age').val());
		console.log(jQuery('#new-session-sex').val());
		console.log(jQuery('#new-session-dob').val());
            jQuery('#study-info-dialog-sendall').removeAttr("disabled");
        }
		

//	jQuery('#session-participant').select2({
//	    dropdownParent: jQuery('#modal-study-info'),
//	    placeholder: 'Select a HBCD participant',
//	    data: data.map(function(v,i) { return { id:v, text:v }; })
//	});
    }).fail(function(jqxhr, textStatus, error) {
        alert("could not get participants names - not JSON? " + error);
    });
}

function traverse(elem, s) {
  $(elem).children().each(function(i,e){
    var title = jQuery(e).attr('title');
    if (typeof title !== typeof undefined && title !== false) {
       s = s + title;
    }
    s = jQuery(e).text() + " " + traverse($(e), s);
  });
  return s;
}

function deselect(e) {
  $('.pop').slideFadeToggle(function() {
    e.removeClass('selected');
  });    
}
jQuery.fn.slideFadeToggle = function(easing, callback) {
    return this.animate({ opacity: 'toggle', height: 'toggle' }, 'fast', easing, callback);
};

function getReadableFileSizeString(fileSizeInBytes) {
    var i = -1;
    var byteUnits = [' kB', ' MB', ' GB', ' TB', 'PB', 'EB', 'ZB', 'YB'];
    do {
        fileSizeInBytes = fileSizeInBytes / 1024;
        i++;
    } while (fileSizeInBytes > 1024);

    return Math.max(fileSizeInBytes, 0.1).toFixed(1) + byteUnits[i];
};

function goodHeader( header ) {
    if (header == "") {
	return false;
    }
    if (header.indexOf("NDAR") === 0) {
	return true;
    }
    return false;
}

function checkMessageWindow() {
  jQuery.ajax({
       url: '/php/repush.jobs',
       dataType: 'text'     
  }).done(function(data) {
       data = data.split("\n");
       if (data.length > 1) {
  	  jQuery('#message-window').show();
          jQuery('#message-window-header').html("List of currently queued repush jobs");
          jQuery('#message-window-body').html(data.join("<br>"));
       } else {
	  jQuery('#message-window').hide();
       }	    
  });			     
  setTimeout(checkMessageWindow, 5000);
}

var quarantineDataTmp = []; // temporarily store the quarantine data for lookup
var editor = "";    // one for setup
var editor2 = "";   // one for series informations
jQuery(document).ready(function() {

    checkMessageWindow();

    if (sites.length < 1) {
        // hide project-dropdown-section
        jQuery('#project-dropdown-section').hide();
    }
    // Set the project name label
    jQuery('#projname').text(projname);

    jQuery('.pop').on('click', function(e) {
	deselect(jQuery(this));
    });

    jQuery('#show-suid-only').on('change', function() {
       var onlySUID = this.checked;
       // hide rows for which the entries don't start with SUID
       jQuery('#cleanQuarantine tr').each(function(a) {
            var t = jQuery(this).find('td').first().attr('title');
            if (onlySUID) {
               if (t.indexOf('SUID') !== -1) {
                   jQuery(this).show();
               } else {
                   jQuery(this).hide(); 
               }
            } else {
	       jQuery(this).show();
            }
       });
    });   

    jQuery('#calendar-loc').on('click', '.fc-title', function() {
       jQuery('#search-list').val(jQuery(this).text()).trigger('keyup');		    
    });
  
    jQuery('#modal-kspace-status').on('click', '.item', function(e) {
        // create a popover for this item
        if (jQuery(this).hasClass('selected')) {
            deselect(jQuery(this));
        } else {
            jQuery(this).addClass('selected');
            jQuery('.pop').slideFadeToggle();
            // move the window to the mouse position
            jQuery('.pop').css('top', jQuery(this).offset().top + "px");
            jQuery('.pop').css('left', jQuery(this).offset().left + 'px');
            jQuery('.pop').css('z-index', 100003);
            // fill in the contents
            var title = jQuery(this).attr('title');
            jQuery('.pop .messagepop-title').html( title.split(' ')[0] );
            jQuery('.pop .messagepop-content').html( 'SeriesInstanceUID:</br>' + title.split(" ")[1] + '</br>StudyInstanceUID:</br>' + jQuery(this).parent().parent().parent().attr('title'));
        }
        return false;
    }); 


    jQuery('#modal-data-flow').on('click', '.item', function(e) {
	// create a popover for this item
	if (jQuery(this).hasClass('selected')) {
	    deselect(jQuery(this));
	} else {
	    jQuery(this).addClass('selected');
	    jQuery('.pop').slideFadeToggle();
	    // move the window to the mouse position
	    jQuery('.pop').css('top', jQuery(this).offset().top + "px");
	    jQuery('.pop').css('left', jQuery(this).offset().left + 'px');
	    jQuery('.pop').css('z-index', 100003);
            // fill in the contents
	    var title = jQuery(this).attr('title');
	    jQuery('.pop .messagepop-title').html( title.split(' ')[0] );
	    jQuery('.pop .messagepop-content').html( 'SeriesInstanceUID:</br>' + title.split(" ")[1] + '</br>StudyInstanceUID:</br>' + jQuery(this).parent().parent().parent().attr('title'));
	}
	return false;
    }); 
    
    jQuery('.mdh-toggle-search').click(function() {
	// No search bar is currently shown
	if ($(this).find('i').text() == 'search') {
	    $(this).find('i').text('cancel');
	    $(this).removeClass('mdl-cell--hide-tablet mdl-cell--hide-desktop'); // Ensures the close button doesn't disappear if the screen is resized.
	    
	    $('.mdl-layout__drawer-button, .mdl-layout-title, .mdl-badge, .mdl-layout-spacer').hide();
	    $('.mdl-layout__header-row').css('padding-left', '16px'); // Remove margin that used to hold the menu button
	    $('.mdh-expandable-search').removeClass('mdl-cell--hide-phone').css('margin', '0 16px 0 0');
	    
	}
	// Search bar is currently showing
	else {
	    $(this).find('i').text('search');
	    $(this).addClass('mdl-cell--hide-tablet mdl-cell--hide-desktop');
	    
	    $('.mdl-layout__drawer-button, .mdl-layout-title, .mdl-badge, .mdl-layout-spacer').show();
	    $('.mdl-layout__header-row').css('padding-left', '');
	    $('.mdh-expandable-search').addClass('mdl-cell--hide-phone').css('margin', '0 50px');
	}
    
    });

  jQuery('#search-list').keyup(function() {
         //console.log('new search...' + jQuery('#search-list').val());
         search = jQuery('#search-list').val();
         if (search.trim() == "") {
             jQuery('#list-of-subjects div.open-study-info').each(function(i,v) {
                 jQuery(this).show();
             });
         }
         var re = new RegExp(search,'i');
         jQuery('#list-of-subjects div.open-study-info').each(function(i,v) {
             var str = traverse(v);
             if (re.test(str)) {
                 jQuery(v).show();
             } else {
                 jQuery(v).hide();
             }
             //console.log('found strings: ' + str);
         });
         jQuery('#list-of-subjects > ul > li').each(function(i,v){
             var str = jQuery(v).text();  // gets us all the text elements we will need
             if (re.test(str)) {
                 jQuery(v).show();
             } else {
                 jQuery(v).hide();
             }
         });
         updateSendInformation(); // for all visible entries
   });

   jQuery('#list-of-subjects').on('click', '.pull-study', function() {
       // pull this study over from FIONA
       var study = jQuery(this).attr('study');
       if (study != "") {
	  // ask the scanner to send us this studies images
	  jQuery.get('/php/scanner.php?action=getStudy&study=' + study);
          /*jQuery(this).parent().find("li").each(function() {
	    var series = jQuery(this).attr('key');
            if (series != "") {
     	      jQuery.get('/php/scanner.php?action=get&series=' + series);
            }
          }); */
       }
    });

    loadSubjects();
    // TODO: temporarily disabled this until I am done debugging
    setTimeout(function() { loadSystem(); }, 100);
    //setInterval(function() { loadSystem(); }, 5000);
    createCalendar();

    // disable the circles for now
    //createCircles();
    // disable the bars for now			
    //createBars();

    // PCGC
    jQuery('.clickable-project-name').click(function() {
        var value = jQuery(this).text();
	jQuery('#projname').text(value);
        projname = value;
	// store this choice
	jQuery.get('/php/setProject.php?project_name=' + projname, function(data) {
	   console.log("got back: " + JSON.stringify(data));
	});
	loadSubjects();
        loadSystem(); 
        createCalendar();
    });

    jQuery('#load-subjects').click(function() {
	loadSubjects();
    });
    jQuery('#load-studies').click(function() {
	loadStudies();
    });
    jQuery('#load-scanner').click(function() {
	loadScanner();
    });

    jQuery('#receive-dicom-label').change(function() {
       changeSystemStatus();
     });
    jQuery('#receive-mpps-label').change(function() {
       changeSystemStatus();
    });
    //jQuery('#anonymize-label').change(function() {
    //   changeSystemStatus();
    //});

    var dialog = document.querySelector('#modal-series-info');
    if (!dialog.showModal) {
       dialogPolyfill.registerDialog(dialog);
    }
    jQuery('#list-of-subjects').on('click', '.open-series-info', function() {
      var dialog = document.querySelector('#modal-series-info');
      dialog.showModal();
      if (editor2 == "") {
             editor2 = ace.edit("series-info-text");
             editor2.setTheme("ace/theme/chrome");
             editor2.getSession().setMode("ace/mode/javascript");
             editor2.setValue("try to load series info...\n");
      }
      if (editor2 !== "") {
         editor2.setValue( JSON.stringify( studyData[ jQuery(this).attr('key') ][ jQuery(this).attr('entry') ], null, 4 ) );
         editor2.selection.moveTo(1,0);
      }

    });

    var dialog = document.querySelector('#modal-data-flow');
        if (!dialog.showModal) {
        dialogPolyfill.registerDialog(dialog);
    }
    var dialog = document.querySelector('#modal-kspace-status');
        if (!dialog.showModal) {
        dialogPolyfill.registerDialog(dialog);
    }
    var dialog = document.querySelector('#modal-study-info');
        if (!dialog.showModal) {
        dialogPolyfill.registerDialog(dialog);
    }


    jQuery('#list-of-subjects').contextmenu(function(e) {
        e.preventDefault();
        var studyinstanceuid  = jQuery(e.target).parent().attr('studyinstanceuid');
        if (studyinstanceuid == undefined) { // try the parent
	       studyinstanceuid = jQuery(e.target).parent().parent().attr('studyinstanceuid');
        }
        if (studyinstanceuid == undefined) {
           alert("Error: could not find the study instance uid for this study. Try again?");
           return false;
        }
        jQuery('#repush-ok').attr('studyinstanceuid', studyinstanceuid);
        var dialog = document.querySelector('#modal-repush');
        if (!dialog.showModal) {
            dialogPolyfill.registerDialog(dialog);
        }
        dialog.showModal();
        return false;
    });
    var dialogRepush = document.querySelector('#modal-repush');
    var closeButton = dialogRepush.querySelector('#repush-cancel');
    var closeClickHandler = function (event) {
       dialogRepush.close();
    }
    closeButton.addEventListener('click', closeClickHandler);
    jQuery('#repush-ok').on('click', function() {
	var dialogRepush = document.querySelector('#modal-repush');
	dialogRepush.close();
	jQuery.post('/php/repush.php', { 'studyinstanceuid': jQuery(this).attr('studyinstanceuid'), 'project': projname }, function(data) {

	});
    });

    var studyinstanceuid;
    var seriesinstanceuid;
    var PatientName;
    var studydate;

    jQuery('#list-of-subjects').on('click', '.open-study-info', function() {
        console.log("clicked on study: ");

        // clean the displayed information before opening the dialog
        jQuery('#study-info-text').text("loading...");
        jQuery('#header-section').children().remove();
        jQuery('#detected-scans').children().remove();

	// we should highlight the current row in this case until the next hover event		     
        var dialog = document.querySelector('#modal-study-info');
        dialog.showModal();
        jQuery('#session-participant').val(null);
	//jQuery('#session-name').val(null);			     
	jQuery('#session-run').val(null);


        studyinstanceuid  = jQuery(this).attr('studyinstanceuid');
        seriesinstanceuid = jQuery(this).attr('seriesinstanceuid');
        console.log("studyinstanceuid: " + studyinstanceuid);
        console.log("seriesinstanceuid: " + seriesinstanceuid);


	jQuery('#list-of-subjects').children().each(function() { jQuery(this).removeClass('mark'); } );

        jQuery(this).addClass('mark');

        // to fix incomplete series bug:  send button appear while tgz file in quarantine folder is still transfereing data.
        var options = {
            "suid": studyinstanceuid,
            "project": projname
        };
        
        console.log(" Get suid.json file status "); 
        jQuery.getJSON('/php/checkFileReady.php', options, function(data) {
               // return a filemtime of /data/quarantine/suid.json 

              // if /data/quarantine/suid.json has not been changed for 5 min. the files are ready to send.
              console.log(" suid.json file is not changing, ready to send status " + data['message']); 
              if ( data.message === 'readToSend' ){  //enabled  sendall button 
                 jQuery('#study-info-dialog-sendall').removeAttr("disabled");

              } else {
                 jQuery('#study-info-dialog-sendall').attr("disabled", true);
              }
        });


        var options = {
            "action": "getStudy",
            "study": studyinstanceuid,
            "project": projname
        };
        jQuery.getJSON('/php/existingData.php', options, function(data) {
            dataSec1 = {};
            dataSec2 = {};
            dataSec3 = {};
            var keys = Object.keys(data);
            console.log("keys: " + keys);
            for (var i = 0; i < keys.length; i++) {

                var value = data[keys[i]];
                if (Array.isArray(value)) {
                    dataSec3[keys[i]] = value;
                } else if (typeof value === 'object') {
                    dataSec2[keys[i]] = value;
                } else if (typeof value === 'string') {
                    dataSec1[keys[i]] = value;
                } else {
                    alert("Error: No existing data, perhaps protocol compliance check was not run.");
                }
            }
            patientname=dataSec1.PatientName;
            studydate=dataSec1.StudyDate;


            console.log("patient name: " + patientname);
            console.log("study date: " + studydate);
	    // get list of valid participant names from our database	
            getSessionNamesFromREDCap(patientname, studydate);
            
  
            console.log(dataSec1);
	
            displayHeaderSection(dataSec1);
            displayDetectedScans(dataSec2, studyinstanceuid);
            displayAdditionalScans(dataSec3, studyinstanceuid);
            console.log("#list-of-subjects: On click: " + dataSec1["status"])

             
            if (dataSec1["status"] == 0) {
                //jQuery('#study-info-dialog-sendall').removeAttr("disabled");
               alert("Please review the series in RED, the series is either missing or incomplete, please re-push the series from the source if the series is scanned and is a complete series"); 
            } 
                //else {
                //jQuery('#study-info-dialog-sendall').attr("disabled", true);
                //}

        });
        console.log(jQuery('#session-participant').val());


    });

    var dialogDF = document.querySelector('#modal-data-flow');
    var closeButton = dialogDF.querySelector('#data-flow-dialog-cancel');
    var closeClickHandler = function (event) {
       dialogDF.close();
    }
    closeButton.addEventListener('click', closeClickHandler);

    var dialogKS = document.querySelector('#modal-kspace-status');
    var closeButtonKS = dialogKS.querySelector('#data-kspace-dialog-cancel');
    var closeClickHandler = function (event) {
       dialogKS.close();
    }
    closeButtonKS.addEventListener('click', closeClickHandler);

    var dialogMS = document.querySelector('#modal-mrs-status');
    var closeButtonMS = dialogMS.querySelector('#data-mrs-dialog-cancel');
    var closeClickHandler = function (event) {
       dialogMS.close();
    }
    closeButtonMS.addEventListener('click', closeClickHandler);


    var dialogCP = document.querySelector('#modal-change-password');
    if (!dialogCP.showModal) {
       dialogPolyfill.registerDialog(dialogCP);
    }
    var closeButton = dialogCP.querySelector('#change-password-cancel');
    var closeClickHandler = function (event) {
       dialogCP.close();
    }
    closeButton.addEventListener('click', closeClickHandler);

    var closeButton = dialogCP.querySelector('#change-password-save');
    var closeClickHandler = function (event) {
       dialogCP.close();
    }
    closeButton.addEventListener('click', closeClickHandler);

    var dialog = document.querySelector('#modal-setup');
    if (!dialog.showModal) {
       dialogPolyfill.registerDialog(dialog);
    }
    var dialogCQ = document.querySelector('#modal-clean-quarantine');
    if (!dialogCQ.showModal) {
       dialogPolyfill.registerDialog(dialogCQ);
    }
    var closeButtonCQ = dialogCQ.querySelector('#clean-quarantine-close');
    var closeClickHandlerCQ = function (event) {
       dialogCQ.close();
    }
    closeButtonCQ.addEventListener('click', closeClickHandlerCQ);


    jQuery('#dialog-change-password-button').click(function() {
      var dialog = document.querySelector('#modal-change-password');
      dialog.showModal();
    });

    jQuery('#dialog-mrs-status-button').click(function() {
      var dialog = document.querySelector('#modal-mrs-status');
      dialog.showModal();
      // we need to collect data about which files are in which directories on the system
      jQuery.getJSON('php/getMRSStatus.php', function(data) {
	  items = Object.keys(data);
          jQuery('#mrsData').children().remove();

	  for (var i = 0; i < items.length; i++) {
            var mrs = data[items[i]];
            console.log(mrs);

            var it = "<tr data=\" MRS STATUS: \">";
 
            if ("DICOM without MRS data" in mrs) {
                it = it +  "<td\>"  + " Missing MRS data:   " + " </td>" ;
                it = it +  "<td\>" + mrs['DICOM without MRS data'] + "</td>";
                it = it + 
                  // "<td>" + "<button class=\"btn mrs-lost\" >Mark it Lost</button>" + "</td>"  +
                  "<td>" + "<button class=\"btn mrs-not-acquired\" disabled>Not Acquired</button>" + "</td>"   + "</tr>";
            } 
            else {
                it = it + "<td\>"  + " Unprocessed MRS data:   " + " </td>" ;
                it = it + "<td\>" + mrs['Problematic MRS data'] + "</td>";
                it = it + "</tr>";
            }

	    jQuery('#mrsData').append(it);
          //jQuery('#kspace-status-container').woodmark();

          }
          var wookmark = new Wookmark('#kspace-status-container');
      });
    });


    jQuery('#mrsData').on('click', '.mrs-not-acquired', function() {
        // we got a click on a button that asks us to mark it as not acquired
        var study = jQuery(this).parent().attr('data');
        console.log("Action: mark this data as not acquired : " + study);
        var row = jQuery(this).parent();
        console.log(row)
        var suid = JSON.stringify(quarantineDataTmp[study]['Missing MRS data']); 
        jQuery.ajax({
                      url: 'php/markMRSnotAcquired.php',
                      data: { "suid": suid},
                      dataType: 'json',
                      type: "POST",
                      success: function(data) {
                                // request that the files are moved to DAIC with the specified header
                                console.log(" This MRS has marked as not acquired: " + JSON.stringify(data));
                                row.hide(); // hide this column
                      }
        });
    });


    jQuery('#dialog-kspace-status-button').click(function() {
      var dialog = document.querySelector('#modal-kspace-status');
      dialog.showModal();
      // we need to collect data about which files are in which directories on the system
      jQuery.getJSON('php/getKSPACEStatus.php', function(data) {
	  items = Object.keys(data);
          jQuery('#kspaceData').children().remove();

	  for (var i = 0; i < items.length; i++) {
            var kspace = data[items[i]];
            console.log(kspace);

            var it = "<tr data=\" KSPACE STATUS: \">";
 
            if ("DICOM without KSPACE data" in kspace) {
                it = it +  "<td\>"  + " Missing KSPACE data:   " + " </td>" ;
                it = it +  "<td\>" + kspace['DICOM without KSPACE data'] + "</td>";
            } 
            else {
                it = it + "<td\>"  + " Unprocessed KSPACE data:   " + " </td>" ;
                it = it + "<td\>" + kspace['Problematic KSPACE data'] + "</td>";
            }
            // it = it + "<td>" + "<button class=\"btn kspace-detail\" disabled>Detail</button>" + "</td>"    + "</tr>";
            it = it + "</tr>";
	    jQuery('#kspaceData').append(it);
          //jQuery('#kspace-status-container').woodmark();
	  }
          var wookmark = new Wookmark('#kspace-status-container');
      });
    });

    jQuery('#dialog-data-flow-button').click(function() {
      var dialog = document.querySelector('#modal-data-flow');
      dialog.showModal();
      // we need to collect data about which files are in which directories on the system
      jQuery.getJSON('php/getDataFlow.php', function(data) {
	  studies = Object.keys(data);
          jQuery('#data-flow-container').children().remove();

	  for (var i = 0; i < studies.length; i++) {
            var series = data[studies[i]];
	    var str = "<div class=\"study\" title=\""+studies[i]+"\">";
    	    str = str + "<div class=\"group-archive\">";
            if (typeof series['archive'] != 'undefined' && series['archive'] == 1)
		str = str + "<div class=\"item-heigh\" title=\"archive "+studies[i]+"\"></div>";
            else
		str = str + "<div class=\"no-item\" title=\"archive\"></div>";
            str = str + "</div>";

	    str = str + "<div class=\"series-group\">";

            if (typeof series['series'] != 'undefined') {
              var seriesnames = Object.keys(series['series']);
              for (var j = 0; j < seriesnames.length; j++) {
	          str = str + "<div class=\"serie\">";
 	          if (typeof series['series'][seriesnames[j]]['raw'] != 'undefined' && series['series'][seriesnames[j]]['raw'] == 1) {
 		     str = str + "<div class=\"item raw\" title=\"raw "+ seriesnames[j] +"\">";
                  } else {
 		     str = str + "<div class=\"no-item\" title=\"raw "+ seriesnames[j] +"\">";
		  }
 	          if (typeof series['series'][seriesnames[j]]['quarantine'] != 'undefined' && series['series'][seriesnames[j]]['quarantine'] == 1) {
 		     str = str + "</div><div class=\"item quarantine\" title=\"quarantine "+ seriesnames[j] +"\">";
                  } else {
 		     str = str + "</div><div class=\"no-item\" title=\"quarantine "+ seriesnames[j] +"\">";
                  }
 	          if (typeof series['series'][seriesnames[j]]['outbox'] != 'undefined' && series['series'][seriesnames[j]]['outbox'] == 1) {
 		     str = str + "</div><div class=\"item outbox\" title=\"outbox "+ seriesnames[j] +"\">";
                  } else {
 		     str = str + "</div><div class=\"no-item\" title=\"outbox "+ seriesnames[j] +"\">";
                  }
 	          if (typeof series['series'][seriesnames[j]]['DAIC'] != 'undefined' && series['series'][seriesnames[j]]['DAIC'] == 1) {
 		     str = str + "</div><div class=\"item DAIC\" title=\"DAIC "+ seriesnames[j] +"\"></div>";
                  } else {
 		     str = str + "</div><div class=\"no-item\" title=\"DAIC "+ seriesnames[j] +"\"></div>";
                  }
	          str = str + "</div>";
              }
	    }

	    str = str + "</div>";


	    str = str + "</div>";
	    jQuery('#data-flow-container').append(str);
          }
          //jQuery('#data-flow-container').woodmark();
          var wookmark = new Wookmark('#data-flow-container');
      });
    });

    jQuery('#dialog-clean-quarantine-button').click(function() {
      var dialog = document.querySelector('#modal-clean-quarantine');
      jQuery('#cleanQuarantine').children().remove();
      jQuery('#show-suid-only').prop('checked', false);
      dialog.showModal();
      jQuery('#modal-clean-quarantine div.loading').show();
      jQuery.getJSON('php/quarantineData.php?action=getData', function(data) {
          jQuery('#modal-clean-quarantine div.loading').hide();
          quarantineDataTmp = data;
	  studies = Object.keys(data);
	  for (var i = 0; i < studies.length; i++) {
	      var it = "<tr data=\"" + studies[i] + "\">" +
		  "<td title=\""+data[studies[i]]['files'].join(", ")+"\">" + data[studies[i]]['files'].length + "</td>" +
                  "<td>" + "<button class=\"btn quarantine-delete-these\">Delete</button>" + 
		  "<button class=\"btn quarantine-move-these\" " + (goodHeader(data[studies[i]]['header'])?"":"disabled title=\"First send this study on Study Transfer to create proper parts\"") + ">Move to DAIC</button>" + "</td>" +
                  "<td class=\"mdl-data-table__cell--non-numeric\">" + data[studies[i]]['PatientName'] + "</td>" +
                  "<td>" + data[studies[i]]['StudyDate'] + "</td>" +
                  "<td>" + getReadableFileSizeString(data[studies[i]]['size']) + "</td>" +
                  "<td>" + data[studies[i]]['header'] + "</td>"
                  + "</tr>";
	      
             jQuery('#cleanQuarantine').append(it);
          }
      });
    });

    jQuery('#cleanQuarantine').on('click', '.quarantine-move-these', function() {
	// we got a click on a button that asks us to move data to DAIC
	var study = jQuery(this).parent().parent().attr('data');
	if (quarantineDataTmp[study] == 'undefined') {
	    console.log("Error: could not find this study in the quarantine data tmp: " + study);
	    return;
	}
        var files = JSON.stringify(quarantineDataTmp[study]['files']);
	console.log("Move these datasets: " + files);
	var row = jQuery(this).parent().parent();
	jQuery.ajax({
		      url: 'php/quarantineData.php?action=moveData', 
		      data: { "action": "moveData", "files": files, "header": quarantineDataTmp[study]['header'] }, 
		      dataType: 'json',
		      type: "POST",
		      success: function(data) {
			        // request that the files are moved to DAIC with the specified header
			        console.log("moving files returned: " + JSON.stringify(data));
			        row.hide(); // hide this column
		      }
	});
    });
    jQuery('#cleanQuarantine').on('click', '.quarantine-delete-these', function() {
	// we got a click on a button that asks us to move data to DAIC
	var study = jQuery(this).parent().parent().attr('data');
	if (quarantineDataTmp[study] == 'undefined') {
	    console.log("Error: could not find this study in the quarantine data tmp: " + study);
	    return;
	}
        var files = JSON.stringify(quarantineDataTmp[study]['files']);
	console.log("Delete these datasets: " + files);
	var row = jQuery(this).parent().parent();
	jQuery.ajax({
		      url: 'php/quarantineData.php?action=deleteData', 
		      data: { "action": "deleteData", "files": files }, 
		      dataType: 'json',
		      type: "POST",
		      success: function(data) {
			        // request that the files are moved to DAIC with the specified header
			        console.log("delete files: " + JSON.stringify(data));
			        row.hide(); // hide this column
		      }
	});
    });

    jQuery('#dialog-setup-button').click(function() {
      var dialog = document.querySelector('#modal-setup');
      dialog.showModal();
      jQuery.get('php/config.php', function(data) {
          if (editor == "") {
             editor = ace.edit("setup-text");
             //editor.setTheme("ace/theme/monokai");
             editor.setTheme("ace/theme/chrome");
             editor.getSession().setMode("ace/mode/javascript");
             editor.setValue("try to load settings...\n");
          }

 	  if (editor !== "") {
             editor.setValue(data);
          }
      });
    });

    jQuery('#setup-dialog-save').click(function() {
	jQuery.getJSON('php/config.php', { "action": "save", "value": editor.getValue() }, function(data) {
	    // did saving the data work?
            alert(data);
        });
    });

    jQuery('#dialog-about-button').click(function() {
       var dialog = document.querySelector('#modal-about');
       if (!dialog.showModal) {
         dialogPolyfill.registerDialog(dialog);
       }
       dialog.showModal();
    });
    var closeButton = dialog.querySelector('#setup-dialog-cancel');
    var closeClickHandler = function (event) {
       dialog.close();
    }
    closeButton.addEventListener('click', closeClickHandler);
    jQuery('.close-dialog').click(function() {
       var dialog = document.querySelector('#modal-about');
       dialog.close();
    });
    jQuery('#study-info-dialog-cancel').click(function() {
       var dialog = document.querySelector('#modal-study-info');
       jQuery('#list-of-subjects').children().each(function() { jQuery(this).removeClass('mark'); } );
       dialog.close();     
    });
    jQuery('#study-info-dialog-sendall').click(function() {
       
       jQuery('#list-of-subjects').children().each(function() { jQuery(this).removeClass('mark'); } );
       // check if we are allowed to send yet
       if (jQuery('#session-participant').val() == "" || 
           jQuery('#session-participant').val() == null || 
           jQuery('#session-run').val() == "" ||
           jQuery('#session-run').val() == null) {
   	  alert("Please select a valid (screened) participant, an event name, and a session type before uploading data!");
	  return;
       }	



       var suid =  "";
       var tripleId = "";

       var buttons = jQuery('#detected-scans .send-series-button');
       jQuery.each(buttons, function(index, value) {
          var StudyInstanceUID = jQuery(value).attr('StudyInstanceUID');
          var SeriesInstanceUID = jQuery(value).attr('SeriesInstanceUID');
          var filename = jQuery(value).attr('filename');
	  if ( jQuery('#session-participant').val() == "" || 
	       jQuery('#session-participant').val() == null || 
           //    jQuery('#session-name').val() == "" || 
           //    jQuery('#session-name').val() == null || 
               jQuery('#session-run').val() == "" ||
               jQuery('#session-run').val() == null) {
		alert("Please select a valid (screened) participant, an event name, and a session type before uploading data");
		return;
          }
         suid = StudyInstanceUID;
         tripleId = jQuery('#modify-participant-name').val();

         console.log(jQuery('#modify-participant-name').val())
         console.log(jQuery('#new-session-age').val())
         console.log(jQuery('#new-session-sex').val())
         console.log(jQuery('#new-session-dob').val())

				
          var options = {
             "filename": filename,
	     "id_redcap" : jQuery('#session-participant').val(),
             "run": jQuery('#session-run').val(),
             "modify_participant_name" : jQuery('#modify-participant-name').val(),
             "sex" : jQuery('#new-session-sex').val(),
             "age" : jQuery('#new-session-age').val(),
             "dob" : jQuery('#new-session-dob').val(),
	     "project": projname
          };
          jQuery.getJSON('/php/sendToDAIC.php', options, function(data) {
              //alert(JSON.stringify(data));
	      // collect the different messages before sending them
          });
       });

       alert('Sending ' + buttons.length + ' series to the DAIC.');
       
       var options = {
             "tripleId" : jQuery('#session-participant').val(),
             "suid": suid,
             "run": jQuery('#session-run').val(),
             "modify_participant_name" : jQuery('#modify-participant-name').val(),
             "age" : jQuery('#new-session-age').val(),
             "sex" : jQuery('#new-session-sex').val(),
             "project": projname
       };
       jQuery.getJSON('/php/packRawData.php', options, function(data) {
              alert(JSON.stringify(data));
              // collect the different messages before sending them
       });

       var dialog = document.querySelector('#modal-study-info');
       jQuery('#list-of-subjects').children().each(function() { jQuery(this).removeClass('mark'); } );
       dialog.close();
       jQuery.get('php/announceData.php', { 'suid' : suid, 'project': projname }, function(data) {
	  console.log("tried to announce data, got: " + data);
       });
    });

         // onClick
    jQuery('#detected-scans').on('click', '.send-series-button', function() {
         
         // TODO: Send Study Info
         var StudyInstanceUID = jQuery(this).attr('StudyInstanceUID');
         var SeriesInstanceUID = jQuery(this).attr('SeriesInstanceUID');
         var filename = jQuery(this).attr('filename');
         // alert("send-series-button: StudyInstanceUID: " + StudyInstanceUID + " SeriesInstanceUID: " + SeriesInstanceUID);
	 if (jQuery('#session-participant').val() == "" || 
             jQuery('#session-participant').val() == null || 
           //  jQuery('#session-name').val() == null || 
             jQuery('#session-run').val() == null) {
		alert("Please select a valid (screened) participant before uploading data");
		return;
         }
				
         var options = {
             "filename": filename,
	     "id_redcap" : jQuery('#session-participant').val(),
	    // "redcap_event_name": jQuery('#session-name').val(),
             "run": jQuery('#session-run').val(),
             "project": projname
         };
         jQuery.getJSON('/php/sendToDAIC.php', options, function(data) {
             alert(JSON.stringify(data));
         });
         //var dialog = document.querySelector('#modal-study-info');
         //dialog.close();     
     });
     jQuery('#series-info-dialog-cancel').click(function() {
         var dialog = document.querySelector('#modal-series-info');
         dialog.close();     
     });
     

     jQuery('#imaging-info-dialog-cancel').click(function() {
       var dialog = document.querySelector('#modal-study-info');
       jQuery('#list-of-subjects').children().each(function() { jQuery(this).removeClass('mark'); } );
       dialog.close();
     });

     jQuery('#imaging-info-recheck').click(function() {
       jQuery('#list-of-subjects').children().each(function() { jQuery(this).removeClass('mark'); } );
       // check if we are allowed to send yet
       if (jQuery('#new-session-participant').val() == "" ||
           jQuery('#new-session-participant').val() == null ) {
           alert("Please enter a PSCID_CANDID_VISIT for re-checking!");
           return;
       } else {
           var input = jQuery('#new-session-participant').val().split('_');
           if (input.length != 3) {
              alert("Please enter information with this format PSCID_CANDID_VISIT for re-checking!");
              return;
           }
       }

        var options = {
             "pscid": input[0],
             "candid" : input[1],
             "visit": input[2],
             "site": sites[0],
             "project": projname
          };
          
        jQuery.getJSON('/php/getLorisEvents.php?project=' + projname, function(data) {
           //jQuery('#session-name').children().remove();
           //jQuery('#session-name').append("<option></option>");

        console.log(data)

           for (var i = 0; i < data.length; i++) {
               val = "";
               if (i == 0) {
                  val = "selected=\"selected\"";
               }
           //    jQuery('#session-name').append("<option " + val + " value=\"" + data[i] + "\">" + data[i] + "</option>");
           }
           console.log("in re-echking " + studydate + " " + jQuery('#new-session-participant').val())
           getParticipantNamesFromLoris(jQuery('#new-session-participant').val(), studydate);
           // set the modify-participant name flag
           jQuery('#modify-participant-name').val(1);
     
       }); 
   });

        

});

</script>
    
  </body>
</html>
