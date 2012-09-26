<div class="cat-filters clearingfix" style="margin-top:20px;">
	<strong><?php echo Kohana::lang('sharing_two.site_filter');?>
		<span>[<a href="javascript:toggleLayer('sharing_switch_link','sharing_switch')" id="sharing_switch_link">
			<?php echo Kohana::lang('ui_main.hide'); ?></a>]
		</span>
	</strong>
</div>
		<ul id="sharing_switch" class="category-filters">
			<li><a href="#" id="share_all" <?php if (Kohana::config('sharing_two.default_sharing_filter') == 'all') echo' class="active"'; ?>>
				<div class="swatch" style="background-color:#<?php echo Kohana::config('settings.default_map_all'); ?>"></div>
				<div><?php echo Kohana::lang('sharing_two.all_sites') ?></div>
			</a></li>
			<li><a href="#" id="share_main"<?php if (Kohana::config('sharing_two.default_sharing_filter') == 'main') echo' class="active"'; ?>>
				<div class="swatch" style="background-color:#<?php echo Kohana::config('settings.default_map_all'); ?>"></div>
				<div><?php echo Kohana::config('settings.site_name') ?></div>
			</a></li>
			<?php
			
			foreach ($sites as $site)
			{
				$class = (Kohana::config('sharing_two.default_sharing_filter') == $site->id) ? "active" : '';
				echo '<li><a href="#" id="share_'. $site->id .'" class="'.$class.'"><div class="swatch" style="background-color:#'.$site->site_color.'"></div>
				<div>'.$site->site_name.'</div></a></li>';
			}
			?>
		</ul>
