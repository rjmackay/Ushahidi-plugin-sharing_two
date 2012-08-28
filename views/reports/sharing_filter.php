
						<h3>
							<a href="#" class="small-link-button f-clear reset" onclick="removeParameterKey('sharing', 'fl-sharing');">
								<?php echo Kohana::lang('ui_main.clear'); ?>
							</a>
							<a class="f-title" href="#"><?php echo Kohana::lang('sharing_two.site_filter'); ?></a>
						</h3>
						<div class="f-sharing-box">
							<ul class="filter-list fl-sharing">
								<li><a href="#" id="share_all" <?php if (Kohana::config('sharing_two.default_sharing_filter') == 'all') echo' class="selected"'; ?>>
									<div class="swatch" style="background-color:#<?php echo Kohana::config('settings.default_map_all'); ?>"></div>
									<div><?php echo Kohana::lang('sharing_two.all_sites') ?></div>
								</a></li>
								<li><a href="#" id="share_main"<?php if (Kohana::config('sharing_two.default_sharing_filter') == 'main') echo' class="selected"'; ?>>
									<div class="swatch" style="background-color:#<?php echo Kohana::config('settings.default_map_all'); ?>"></div>
									<div><?php echo Kohana::config('settings.site_name') ?></div>
								</a></li>
								<?php
								
								foreach ($sites as $site)
								{
									$class = (Kohana::config('sharing_two.default_sharing_filter') == $site->id) ? "selected" : '';
									echo '<li><a href="#" id="share_'. $site->id .'" class="'.$class.'"><div class="swatch" style="background-color:#'.$site->site_color.'"></div>
									<div>'.$site->site_name.'</div></a></li>';
								}
								?>
							</ul>
						</div>