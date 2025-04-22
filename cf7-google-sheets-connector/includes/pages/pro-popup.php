<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<div id="popup-gs" class="popup-gs">
	<div class="popup-outer-gs" id="popup-outer-gs"></div>
    
    <div class="popup-container">
     <a id="closeButton" class="popup-close"><i class="fa fa-times" aria-hidden="true"></i></a>
	<div class="popup-content align-center">
    <i class="fa fa-lock popup-icon" aria-hidden="true"></i>
	<h2 class="inner-title"><?php echo esc_html( __( 'GSheetConnector for CF7 GSheetConnector PRO Features', 'gsconnector' ) ); ?></h2>
	<p><?php echo __('This features is available in the PRO version of the plugin. To <strong>Enable the options Upgrade to the PRO</strong> version to unlock all these awesome features.', 'gsconnector'); ?>
    </p>
    <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="popup-btn-normal">
	<?php echo esc_html( __( 'Upgrade To PRO', 'gsconnector' ) ); ?></a>
    </div>
    <p class="note"><?php echo __('Bonus: GSheetConnector for CF7 Lite users will get <strong>Special Discounts</strong> for unlimited site licence, automatically applied at checkout.', 'gsconnector'); ?>
    </p>
    </div>
</div>



<div id="popup-gs2" class="popup-gs">
	<div class="popup-outer-gs" id="popup-outer-gs2"></div>
    
    <div class="popup-container">
     <a id="closeButton2" class="popup-close"><i class="fa fa-times" aria-hidden="true"></i></a>
	<div class="popup-content align-center">
    <i class="fa fa-lock popup-icon" aria-hidden="true"></i>
	<h2 class="inner-title"><?php echo esc_html( __( 'GSheetConnector for CF7 GSheetConnector PRO Features', 'gsconnector' ) ); ?></h2>
	<p><?php echo __('This features is available in the PRO version of the plugin. To <strong>Enable the options Upgrade to the PRO</strong> version to unlock all these awesome features.', 'gsconnector'); ?>
    </p>
    <a href="https://www.gsheetconnector.com/cf7-google-sheet-connector-pro" target="_blank" class="popup-btn-normal">
	<?php echo esc_html( __( 'Upgrade To PRO', 'gsconnector' ) ); ?></a>
    </div>
    <p class="note"><?php echo __('Bonus: GSheetConnector for CF7 Lite users will get <strong>Special Discounts</strong> for unlimited site licence, automatically applied at checkout.', 'gsconnector'); ?>
    </p>
    </div>
</div>




<script>
document.addEventListener('DOMContentLoaded', function() {
    var opener = document.getElementById('opener');
    var opener2 = document.getElementById('opener2'); // New opener
    var popup = document.getElementById('popup-gs');
    var popup2 = document.getElementById('popup-gs2'); // New popup
    var closeButton = document.getElementById('closeButton');
    var closeButton2 = document.getElementById('closeButton2'); // New closeButton
    var popupOuter = document.getElementById('popup-outer-gs');
    var popupOuter2 = document.getElementById('popup-outer-gs2'); // New popupOuter

    opener.addEventListener('click', function() {
        fadeIn(popup);
    });

    opener2.addEventListener('click', function() { // New event listener
        fadeIn(popup2);
    });

    closeButton.addEventListener('click', function() {
        fadeOut(popup);
    });

    closeButton2.addEventListener('click', function() { // New closeButton event listener
        fadeOut(popup2);
    });

    popupOuter.addEventListener('click', function(event) {
        if (event.target === popupOuter) {
            fadeOut(popup);
        }
    });

    popupOuter2.addEventListener('click', function(event) { // New popupOuter event listener
        if (event.target === popupOuter2) {
            fadeOut(popup2);
        }
    });

    function fadeIn(element) {
        var opacity = 0;
        element.style.opacity = opacity;
        element.style.display = 'block';
        var fadeInInterval = setInterval(function() {
            if (opacity < 1) {
                opacity += 0.1;
                element.style.opacity = opacity;
            } else {
                clearInterval(fadeInInterval);
            }
        }, 50);
    }

    function fadeOut(element) {
        var opacity = 1;
        var fadeOutInterval = setInterval(function() {
            if (opacity > 0) {
                opacity -= 0.1;
                element.style.opacity = opacity;
            } else {
                clearInterval(fadeOutInterval);
                element.style.display = 'none';
            }
        }, 50);
    }
});

</script>