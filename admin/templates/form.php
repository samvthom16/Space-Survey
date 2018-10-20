<h1><?php _e( $this->getPageTitle() );?></h1>
<form action="" method="post">
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2">
			
			<div id="post-body-content" style="position: relative;">
				<?php $this->do_action('body-div');?>
			</div><!-- /post-body-content -->

			<div id="postbox-container-1" class="postbox-container">
				<div id="side-sortables" class="meta-box-sortables ui-sortable ui-sortable-disabled" style="">
					<div id="submitdiv" class="postbox ">
						<h2 class="hndle ui-sortable-handle"><span>Settings</span></h2>
						<div class="inside">
							<div class="submitbox" id="submitpost">
								<div style='padding:0 15px 15px 15px;'>
									<?php $this->do_action('settings-div');?>
								</div>
								<div id="major-publishing-actions">
									<div id="delete-action">
										<?php $this->do_action('delete-div');?>
									</div>
									<div id="publishing-action">
										<?php $this->do_action('publish-div');?>
									</div>
									<div class="clear"></div>
								</div>	
							</div>
						</div>
					</div>
				</div>
			</div>
			
		</div><!-- /post-body -->
		<br class="clear">
	</div>
</form>
<style>
	.big-text{
		padding: 3px 8px;
		font-size: 1.7em;
		line-height: 100%;
		height: 1.7em;
		width: 100%;
		outline: 0;
	}
	#post-body-content input{
		margin-bottom: 20px;
	}
</style>