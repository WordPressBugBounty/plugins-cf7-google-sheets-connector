<div class="cd-faq-items">
	<ul id="basics" class="cd-faq-group">
		<li class="content-visible">
			<a class="cd-faq-trigger" data-id="1" href="#0"><?php echo esc_html( __( 'Miscellaneous Features - ', 'gsconnector' ) ); ?><span class="pro"><?php echo esc_html( __( 'Pro', 'gsconnector' ) ); ?></span></a>
			<div class="cd-faq-content cd-faq-content1" style="display: block;">
				<div class="gs-demo-fields gs-second-block">
                     <ul class="gs-field-list">
                    <li>
                  <div class="input-field">
                   
                       <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>
                  </div>
                  <div class="label"><?php echo __('Freeze Header Row', 'gsconnector'); ?>  

                 </div>
                 <div class="field-input">
                 	 <span class="tooltip" style="display: inline !important;">
                        <img src="<?php echo GS_CONNECTOR_URL; ?>assets/img/help.png" class="help-icon">
                        <span
                            class="tooltiptext tooltip-right-msg"><?php echo __("Freeze First Header Row.", "gsconnector"); ?>
                            	
                         </span>
                    </span>
                </div>
                 </li>

                  <li>
                 <div class="input-field">
                   
                       <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>
                  </div>
                  <div class="label"><?php echo __('Colors', 'gsconnector'); ?>

                 </div>
                 <div class="field-input">
                 	<span class="tooltip" style="display: inline !important;">
                        <img src="<?php echo GS_CONNECTOR_URL; ?>assets/img/help.png" class="help-icon">
                        <span
                            class="tooltiptext tooltip-right-msg"><?php echo __("Coloring of header, odd, even rows", "gsconnector"); ?></span>
                    </span>
                 </div>
                 </li>

                 <li>
                  <div class="input-field">
                   
                       <label for="enable-sorting-option" class="button-woo-toggle-cf7" id="sorting-toggle"></label>
                  </div>
                  <div class="label"><?php echo __('Sheet Sorting', 'gsconnector'); ?>  
                 </div>
                <div class="field-input">
                 	<span class="tooltip" style="display: inline !important;">
                        <img src="<?php echo GS_CONNECTOR_URL; ?>assets/img/help.png" class="help-icon">
                        <span
                            class="tooltiptext tooltip-right-msg"><?php echo __("Sort sheet by column name.", "gsconnector"); ?></span>
                    </span>

                 </div>
                 </li>



                     </ul>
					


				</div>
			</div>
		</li>
	</ul>
</div>


<script>
jQuery(document).ready(function($){
	//update these values if you change these breakpoints in the style.css file (or _layout.scss if you use SASS)
	var MqM= 768,
		MqL = 1024;

	var faqsSections = $('.cd-faq-group'),
		faqTrigger = $('.cd-faq-trigger'),
		faqsContainer = $('.cd-faq-items'),
		faqsCategoriesContainer = $('.cd-faq-categories'),
		faqsCategories = faqsCategoriesContainer.find('a'),
		closeFaqsContainer = $('.cd-close-panel');
	
	//select a faq section 
	faqsCategories.on('click', function(event){
		event.preventDefault();
		var selectedHref = $(this).attr('href'),
			target= $(selectedHref);
		if( $(window).width() < MqM) {
			faqsContainer.scrollTop(0).addClass('slide-in').children('ul').removeClass('selected').end().children(selectedHref).addClass('selected');
			closeFaqsContainer.addClass('move-left');
			$('body').addClass('cd-overlay');
		} else {
	        $('body,html').animate({ 'scrollTop': target.offset().top - 19}, 200); 
		}
	});

	//close faq lateral panel - mobile only
	$('body').bind('click touchstart', function(event){
		if( $(event.target).is('body.cd-overlay') || $(event.target).is('.cd-close-panel')) { 
			closePanel(event);
		}
	});
	

	//show faq content clicking on faqTrigger
	faqTrigger.on('click', function(event){
		event.preventDefault();
		var dataid = $(this).attr('data-id');
		//$(this).next('.cd-faq-content').slideToggle(200).end().parent('li').toggleClass('content-visible');
		for (var i = 1 ; i <= 5; i++) {
			if(i!=dataid && i!=5)
			{
				$('.cd-faq-content'+i).hide(200).end().parent('li').removeClass('content-visible');
			}
		}
		$(this).next('.cd-faq-content'+dataid).slideToggle(200).end().parent('li').toggleClass('content-visible');
	});

});
</script>